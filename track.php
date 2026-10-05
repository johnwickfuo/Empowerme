<?php
require __DIR__.'/inc/bootstrap.php';
$code=strtoupper(preg_replace('/[^A-Z0-9]/','',strtoupper((string)($_GET['code']??$_POST['tracking_code']??''))));
$errors=[];
$app=null;
if ($code) {
    $stmt=$db->prepare('SELECT a.*,s.name status_name,s.display_color status_color FROM applications a LEFT JOIN statuses s ON s.id=a.current_status_id WHERE a.tracking_code=? LIMIT 1');
    $stmt->execute([$code]); $app=$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='reply') {
    verify_csrf();
    if (!$app) $errors[]='We could not find an application using that tracking code.';
    $message=trim((string)($_POST['message']??''));
    $uploads=normalized_uploads($_FILES['attachments']??[]);
    $hasFiles=false;
    foreach($uploads as $u){ if(($u['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$hasFiles=true; if((int)$u['size']>MAX_UPLOAD_BYTES)$errors[]='Each uploaded file must be 20 MB or smaller.';} }
    if ($message==='' && !$hasFiles) $errors[]='Enter a response or attach at least one file.';
    if (!$errors) {
        try {
            $db->beginTransaction();
            $ins=$db->prepare("INSERT INTO application_updates (application_id,actor_type,title,message) VALUES (?,'applicant','Applicant response',?)");
            $ins->execute([$app['id'],$message]); $updateId=(int)$db->lastInsertId();
            foreach($uploads as $u) store_uploaded_file($db,(int)$app['id'],$updateId,null,$u);
            $db->commit();
            $notify=setting('public_email');
            if($notify) mail_html($notify,'Applicant response: '.$app['tracking_code'],email_shell('New applicant response','<p><strong>Tracking code:</strong> '.h($app['tracking_code']).'</p><p><strong>Applicant:</strong> '.h($app['full_name']).'</p><p>'.nl2br(h($message)).'</p>'),(int)$app['id'],'applicant_response_alert');
            flash('success','Your response has been added to the application.'); redirect('track.php?code='.urlencode($code));
        } catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); $errors[]=$e->getMessage(); }
    }
}

$updates=[];$filesByUpdate=[];$initialFiles=[];
if($app){
    $u=$db->prepare('SELECT u.*,COALESCE(u.status_name_snapshot,s.name) status_name,COALESCE(u.status_color_snapshot,s.display_color) status_color FROM application_updates u LEFT JOIN statuses s ON s.id=u.status_id WHERE u.application_id=? ORDER BY u.created_at DESC,u.id DESC');$u->execute([$app['id']]);$updates=$u->fetchAll();
    $f=$db->prepare('SELECT * FROM application_files WHERE application_id=? ORDER BY created_at,id');$f->execute([$app['id']]);
    foreach($f->fetchAll() as $file){ if($file['update_id'])$filesByUpdate[$file['update_id']][]=$file; else $initialFiles[]=$file; }
}
$pageTitle='Track Application | '.setting('program_name'); require __DIR__.'/inc/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Application tracker</div><h1>Track your application</h1><p>Enter the tracking code issued when your application was submitted.</p></div></section>
<section class="section section-soft"><div class="container form-shell">
<?php if($errors):?><div class="alert alert-error"><?=h(implode(' ',$errors))?></div><?php endif;?>
<?php if(!$app):?>
<div class="panel"><form method="get"><div class="field"><label>Tracking Code</label><input name="code" maxlength="32" style="text-transform:uppercase;letter-spacing:2px" value="<?=h($code)?>" placeholder="Enter your tracking code" required></div><p><button class="btn btn-primary">Track Application</button></p></form><?php if($code):?><div class="alert alert-error">No application was found with that tracking code. Check the code and try again.</div><?php endif;?></div>
<?php else:?>
<div class="panel" style="margin-bottom:22px"><div style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap"><div><div class="muted" style="font-size:13px">TRACKING CODE</div><div class="tracking-code" style="font-size:24px;text-align:left"><?=h($app['tracking_code'])?></div><div class="muted">Submitted <?=h(date('F j, Y',strtotime($app['created_at'])))?> · <?=h(location_label($app['location']))?></div></div><div><?php if($app['status_name']):?><span class="status-pill" style="background:<?=h($app['status_color'])?>"><?=h($app['status_name'])?></span><?php else:?><span class="muted">No status assigned yet</span><?php endif;?></div></div></div>
<div class="panel" style="margin-bottom:22px"><h2>Application history</h2><div class="timeline"><?php foreach($updates as $update):?><article class="timeline-item"><h3><?=h($update['title'])?></h3><div class="timeline-meta"><?=h(date('F j, Y · g:i A',strtotime($update['created_at'])))?><?= $update['actor_type']==='applicant'?' · Your response':''?><?php if($update['status_name']):?> · <span class="status-pill" style="background:<?=h($update['status_color'])?>"><?=h($update['status_name'])?></span><?php endif;?></div><?php if($update['message']):?><p><?=nl2br(h($update['message']))?></p><?php endif;?><?php if($update['link_url']&&safe_return_url($update['link_url'])):?><p><a class="btn btn-outline" target="_blank" rel="noopener noreferrer" href="<?=h($update['link_url'])?>"><?=h($update['link_label']?:'Open link')?> ↗</a></p><?php endif;?><?php if(!empty($filesByUpdate[$update['id']])):?><div><?php foreach($filesByUpdate[$update['id']] as $file):?><a href="<?=h(app_url('file.php?id='.$file['id'].'&code='.urlencode($code)))?>">📎 <?=h($file['original_name'])?></a><br><?php endforeach;?></div><?php endif;?></article><?php endforeach;?></div></div>
<div class="panel"><h2>Send additional information</h2><p class="muted">Use this area to respond when the program requests more information or documents.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="reply"><input type="hidden" name="tracking_code" value="<?=h($code)?>"><?=csrf_field()?><div class="field"><label>Your response</label><textarea name="message" placeholder="Write your response here..."></textarea></div><div class="field" style="margin-top:16px"><label>Attach files <span class="muted">(optional, 20 MB maximum per file)</span></label><input type="file" name="attachments[]" multiple></div><p><button class="btn btn-primary">Submit Response</button></p></form></div>
<?php endif;?></div></section>
<?php require __DIR__.'/inc/footer.php';?>
