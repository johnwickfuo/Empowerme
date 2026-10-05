<?php
require __DIR__ . '/inc/bootstrap.php';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $phone=trim((string)($_POST['phone']??'')); $message=trim((string)($_POST['message']??''));
    if (!$name) $errors[]='Enter your name.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Enter a valid email address.';
    if (!$message) $errors[]='Enter your message.';
    if (!$errors) {
        $stmt=$db->prepare('INSERT INTO contact_messages (name,email,phone,message) VALUES (?,?,?,?)'); $stmt->execute([$name,$email,$phone,$message]);
        $notify=setting('public_email');
        if ($notify) mail_html($notify,'New EmpowerME website inquiry',email_shell('New contact inquiry','<p><strong>From:</strong> '.h($name).' ('.h($email).')</p><p><strong>Phone:</strong> '.h($phone).'</p><p>'.nl2br(h($message)).'</p>'),null,'contact_alert');
        flash('success','Thank you. Your message has been received.'); redirect('contact.php');
    }
}
$pageTitle='Contact Us | '.setting('program_name'); require __DIR__.'/inc/header.php';
?>
<section class="page-hero"><div class="container"><h1>Contact Us</h1><p>Questions about the program, eligibility, or the application process? Send us a message.</p></div></section>
<section class="section"><div class="container split"><div><span class="eyebrow" style="color:var(--brand)">Get in touch</span><h2 style="font-size:39px;line-height:1.12">We are available to provide program information and application guidance.</h2><p><strong>Email</strong><br><a href="mailto:<?= h(setting('public_email')) ?>"><?= h(setting('public_email')) ?></a></p><p><strong>Phone / WhatsApp</strong><br><a href="tel:<?= h(preg_replace('/[^+0-9]/','',setting('public_phone'))) ?>"><?= h(setting('public_phone')) ?></a></p><?php if(setting('whatsapp_url')):?><p><a class="btn btn-outline" target="_blank" rel="noopener" href="<?= h(setting('whatsapp_url')) ?>">Open WhatsApp inquiry</a></p><?php endif; ?><p class="muted">Location: <?= h(setting('organization_location','United States')) ?></p></div><div class="panel"><?php if($errors): ?><div class="alert alert-error"><?= h(implode(' ', $errors)) ?></div><?php endif; ?><form method="post"><div class="form-grid"><?= csrf_field() ?><div class="field"><label>Full Name</label><input name="name" value="<?= h($_POST['name']??'') ?>" required></div><div class="field"><label>Email Address</label><input name="email" type="email" value="<?= h($_POST['email']??'') ?>" required></div><div class="field full"><label>Phone / WhatsApp <span class="muted">(optional)</span></label><input name="phone" value="<?= h($_POST['phone']??'') ?>"></div><div class="field full"><label>Message</label><textarea name="message" required><?= h($_POST['message']??'') ?></textarea></div><div class="field full"><button class="btn btn-primary">Send message</button></div></div></form></div></div></section>
<?php require __DIR__.'/inc/footer.php'; ?>
