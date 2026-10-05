<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = 'Sponsors | ' . setting('program_name');
$sponsors = $db->query('SELECT * FROM sponsors ORDER BY created_at DESC, id DESC')->fetchAll();
require __DIR__ . '/inc/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Program support</span><h1>Our Sponsors</h1><p>Organizations and partners supporting the EmpowerME Grant Program.</p></div></section>
<section class="section"><div class="container">
<?php if (!$sponsors): ?>
<div class="empty">No sponsors have been published yet.</div>
<?php else: ?>
<div class="sponsor-page-grid">
<?php foreach ($sponsors as $s): ?>
<article class="sponsor-card"><div class="sponsor-logo"><img loading="lazy" src="<?= h(app_url('media.php?type=sponsor&id='.(int)$s['id'])) ?>" alt="<?= h($s['name']) ?> logo"></div><strong><?= h($s['name']) ?></strong></article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></section>
<?php require __DIR__ . '/inc/footer.php'; ?>
