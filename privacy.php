<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle='Privacy Policy | '.setting('program_name');
require __DIR__ . '/inc/header.php';
?>
<section class="section">
  <div class="container legal"><?= nl2br(h(setting('privacy_text'))) ?></div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
