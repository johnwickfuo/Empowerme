<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle='About Us | '.setting('program_name');
require __DIR__ . '/inc/header.php';
?>
<section class="section">
  <div class="container legal"><?= nl2br(h(setting('about_text'))) ?></div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
