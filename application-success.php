<?php
require __DIR__.'/inc/bootstrap.php';
$code=strtoupper(trim((string)($_GET['code']??'')));
$stmt=$db->prepare('SELECT full_name,email,tracking_code,created_at FROM applications WHERE tracking_code=? LIMIT 1'); $stmt->execute([$code]); $app=$stmt->fetch();
if(!$app){http_response_code(404);$pageTitle='Application not found';require __DIR__.'/inc/header.php';echo '<section class="section"><div class="container form-shell"><div class="empty">Application not found.</div></div></section>';require __DIR__.'/inc/footer.php';exit;}
$pageTitle='Application Received | '.setting('program_name'); require __DIR__.'/inc/header.php';
?>
<section class="section section-soft"><div class="container form-shell"><div class="panel" style="text-align:center"><div style="font-size:54px;color:var(--success)">✓</div><h1>Application successfully submitted</h1><p class="muted">Thank you, <?= h($app['full_name']) ?>. Your application has been received.</p><div class="tracking-box"><div class="muted">Your tracking code</div><div class="tracking-code"><?= h($app['tracking_code']) ?></div><button class="btn btn-outline" data-copy="<?= h($app['tracking_code']) ?>" type="button">Copy code</button></div><p><strong>Keep this code private.</strong> It is the credential used to access your application progress and submit requested information.</p><p>A confirmation email has been sent if email delivery is configured.</p><a class="btn btn-primary" href="<?= h(app_url('track.php?code='.urlencode($app['tracking_code']))) ?>">Track Application</a></div></div></section>
<?php require __DIR__.'/inc/footer.php'; ?>
