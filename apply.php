<?php
require __DIR__ . '/inc/bootstrap.php';
$location = strtolower(trim((string)($_GET['location'] ?? $_POST['location'] ?? '')));
$validLocations = locations();
$errors = [];
$form = null;
if ($location && isset($validLocations[$location])) $form = active_form($db, $location);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_application') {
    verify_csrf();
    $formId = (int)($_POST['form_id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM forms WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$formId]);
    $form = $stmt->fetch();
    if (!$form) $errors[] = 'This application form is no longer active. Please restart your application.';
    else {
        $location = $form['location'];
        $fieldStmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order,id');
        $fieldStmt->execute([$formId]);
        $form['fields'] = $fieldStmt->fetchAll();
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $custom = is_array($_POST['custom'] ?? null) ? $_POST['custom'] : [];
        if (strlen($fullName) < 2) $errors[] = 'Enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!$errors) {
            $dup = $db->prepare('SELECT tracking_code FROM applications WHERE email = ? LIMIT 1');
            $dup->execute([$email]);
            if ($dup->fetchColumn()) $errors[] = 'An application has already been submitted using this email address.';
        }
        $answers = [];
        foreach ($form['fields'] as $field) {
            if ($field['field_type'] === 'heading') continue;
            $condition = $field['condition_json'] ? json_decode($field['condition_json'], true) : null;
            if (!condition_met($condition, $custom)) continue;
            $key = $field['field_key'];
            if ($field['field_type'] === 'file') {
                $upload = $_FILES['upload_' . $field['id']] ?? null;
                $has = $upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if ($field['is_required'] && !$has) $errors[] = $field['label'] . ' is required.';
                if ($has && (int)($upload['size'] ?? 0) > MAX_UPLOAD_BYTES) $errors[] = $field['label'] . ' must be 20 MB or smaller.';
                continue;
            }
            $value = $custom[$key] ?? '';
            if (is_array($value)) $value = array_values(array_filter(array_map(fn($v)=>trim((string)$v), $value), fn($v)=>$v!==''));
            else $value = trim((string)$value);
            if ($field['is_required'] && (($value === '') || (is_array($value) && !$value))) $errors[] = $field['label'] . ' is required.';
            $answers[$key] = $value;
        }

        if (!$errors) {
            try {
                $db->beginTransaction();
                $code = generate_tracking_code($db);
                $snapshot = form_snapshot($form);
                $insert = $db->prepare('INSERT INTO applications (tracking_code,form_id,form_version,form_snapshot_json,location,full_name,email,answers_json) VALUES (?,?,?,?,?,?,?,?)');
                $insert->execute([$code,$formId,$form['version'],json_encode($snapshot,JSON_UNESCAPED_UNICODE),$location,$fullName,$email,json_encode($answers,JSON_UNESCAPED_UNICODE)]);
                $appId = (int)$db->lastInsertId();
                $up = $db->prepare("INSERT INTO application_updates (application_id,actor_type,title,message) VALUES (?,'system','Application received','Your application was successfully submitted and is awaiting review.')");
                $up->execute([$appId]);
                foreach ($form['fields'] as $field) {
                    if ($field['field_type'] !== 'file') continue;
                    $condition = $field['condition_json'] ? json_decode($field['condition_json'], true) : null;
                    if (!condition_met($condition, $custom)) continue;
                    $upload = $_FILES['upload_' . $field['id']] ?? null;
                    if ($upload) store_uploaded_file($db, $appId, null, $field['field_key'], $upload);
                }
                $db->commit();
                $app = ['id'=>$appId,'tracking_code'=>$code,'full_name'=>$fullName,'email'=>$email];
                send_application_confirmation($app);
                redirect('application-success.php?code=' . urlencode($code));
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                if (($e->errorInfo[1] ?? 0) === 1062) $errors[] = 'An application has already been submitted using this email address.';
                else $errors[] = 'We could not save your application. Please try again.';
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}
$pageTitle='Apply for a Grant | '.setting('program_name');
require __DIR__.'/inc/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Grant application</div><h1>Apply for funding</h1><p>Select where you are applying from. The application questions shown next are configured for that location.</p></div></section>
<section class="section section-soft"><div class="container form-shell">
<?php if ($errors): ?><div class="alert alert-error"><?= h(implode(' ', $errors)) ?></div><?php endif; ?>
<?php if (!$location || !isset($validLocations[$location])): ?>
<div class="section-title"><h2>Where are you applying from?</h2><p>Choose your location to continue.</p></div><div class="location-grid"><?php foreach($validLocations as $key=>$label): ?><a class="location-card" href="<?= h(app_url('apply.php?location='.$key)) ?>"><?= h($label) ?></a><?php endforeach; ?></div>
<?php elseif (!$form): ?>
<div class="panel"><h2>No application form is currently open for <?= h(location_label($location)) ?></h2><p class="muted">Please check back later or contact the program for guidance.</p><a class="btn btn-outline" href="<?= h(app_url('apply.php')) ?>">Choose another location</a></div>
<?php else: ?>
<div style="margin-bottom:22px"><a href="<?= h(app_url('apply.php')) ?>">← Change location</a><h2 style="margin-bottom:4px"><?= h($form['title']) ?></h2><p class="muted"><?= h(location_label($location)) ?> application<?= $form['description'] ? ' · '.h($form['description']) : '' ?></p></div>
<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="submit_application"><input type="hidden" name="location" value="<?= h($location) ?>"><input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>"><?= csrf_field() ?>
<div class="form-grid"><div class="field"><label>Full Name *</label><input name="full_name" value="<?= h($_POST['full_name']??'') ?>" required autocomplete="name"></div><div class="field"><label>Email Address *</label><input type="email" name="email" value="<?= h($_POST['email']??'') ?>" required autocomplete="email"><small>Only one application is allowed per email address.</small></div>
<?php foreach($form['fields'] as $field): $opts=$field['options_json']?json_decode($field['options_json'],true):[]; $cond=$field['condition_json']?json_decode($field['condition_json'],true):null; $conditionAttr=$cond?' data-condition="'.h(json_encode($cond)).'"':''; $key=$field['field_key']; $old=$_POST['custom'][$key]??''; ?>
<?php if($field['field_type']==='heading'): ?><div class="field full"<?= $conditionAttr ?>><h3 style="margin:18px 0 0"><?= h($field['label']) ?></h3><?php if($field['help_text']): ?><p class="muted"><?= h($field['help_text']) ?></p><?php endif; ?></div>
<?php else: ?><div class="field <?= in_array($field['field_type'],['textarea','radio','checkbox','file'])?'full':'' ?>"<?= $conditionAttr ?>><label><?= h($field['label']) ?><?= $field['is_required']?' *':'' ?></label>
<?php if(in_array($field['field_type'],['text','tel','number','date'],true)): ?><input type="<?= h($field['field_type']) ?>" name="custom[<?= h($key) ?>]" value="<?= h(is_array($old)?'':$old) ?>" placeholder="<?= h($field['placeholder']) ?>" <?= $field['is_required']?'required':'' ?>>
<?php elseif($field['field_type']==='textarea'): ?><textarea name="custom[<?= h($key) ?>]" placeholder="<?= h($field['placeholder']) ?>" <?= $field['is_required']?'required':'' ?>><?= h(is_array($old)?'':$old) ?></textarea>
<?php elseif($field['field_type']==='select'): ?><select name="custom[<?= h($key) ?>]" <?= $field['is_required']?'required':'' ?>><option value="">Select an option</option><?php foreach($opts as $opt): ?><option value="<?= h($opt) ?>" <?= $old===$opt?'selected':'' ?>><?= h($opt) ?></option><?php endforeach; ?></select>
<?php elseif($field['field_type']==='radio'): ?><div class="choice-row"><?php foreach($opts as $opt): ?><label><input type="radio" name="custom[<?= h($key) ?>]" value="<?= h($opt) ?>" <?= $old===$opt?'checked':'' ?> <?= $field['is_required']?'required':'' ?>> <?= h($opt) ?></label><?php endforeach; ?></div>
<?php elseif($field['field_type']==='checkbox'): ?><div class="choice-row"><?php foreach($opts as $opt): ?><label><input type="checkbox" name="custom[<?= h($key) ?>][]" value="<?= h($opt) ?>" <?= is_array($old)&&in_array($opt,$old,true)?'checked':'' ?>> <?= h($opt) ?></label><?php endforeach; ?></div>
<?php elseif($field['field_type']==='file'): ?><input type="file" name="upload_<?= (int)$field['id'] ?>" <?= $field['is_required']?'required':'' ?>><small>Maximum file size: 20 MB.</small>
<?php endif; ?><?php if($field['help_text']): ?><small><?= h($field['help_text']) ?></small><?php endif; ?></div><?php endif; ?>
<?php endforeach; ?>
<div class="field full"><p class="muted" style="font-size:13px">By submitting this form, you confirm that the information provided is accurate to the best of your knowledge. Submission does not guarantee funding.</p><button class="btn btn-primary" type="submit">Submit Application</button></div></div></form></div>
<?php endif; ?></div></section>
<?php require __DIR__.'/inc/footer.php'; ?>
