<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle='Terms of Use | '.setting('program_name');
require __DIR__ . '/inc/header.php';
?>
<section class="section">
  <div class="container legal"><?= nl2br(h(setting('terms_text'))) ?></div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
