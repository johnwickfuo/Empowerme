<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'Beneficiary Testimonials | ' . setting('program_name');
$testimonials = $db->query("SELECT * FROM testimonials WHERE COALESCE(beneficiary_name,'')<>'' OR COALESCE(story,'')<>'' OR COALESCE(image_stored_name,'')<>'' ORDER BY created_at DESC, id DESC")->fetchAll();
require __DIR__ . '/inc/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Beneficiary experiences</span><h1>Testimonials</h1><p>Stories shared by people whose grant experiences have been added to the EmpowerME program website.</p></div></section>
<section class="section"><div class="container">
<?php if (!$testimonials): ?>
<div class="empty">No testimonials have been published yet.</div>
<?php else: ?>
<div class="testimonial-grid">
<?php foreach ($testimonials as $t): ?>
<article class="testimonial-card">
<?php if ($t['image_stored_name']): ?><div class="testimonial-photo"><img loading="lazy" src="<?= h(app_url('media.php?type=testimonial&id='.(int)$t['id'])) ?>" alt="<?= h($t['beneficiary_name'] ?: 'Grant beneficiary') ?>"></div><?php endif; ?>
<div class="testimonial-body">
<?php if ($t['story']): ?><p class="testimonial-story">“<?= nl2br(h($t['story'])) ?>”</p><?php endif; ?>
<?php if ($t['beneficiary_name']): ?><strong class="testimonial-name"><?= h($t['beneficiary_name']) ?></strong><?php endif; ?>
</div></article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></section>
<?php require __DIR__ . '/inc/footer.php'; ?>
