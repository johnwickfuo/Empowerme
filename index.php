<?php
require __DIR__ . '/inc/bootstrap.php';
$pageTitle = setting('program_name') . ' | Funding Opportunities';
$recentTestimonials = $db->query("SELECT * FROM testimonials WHERE COALESCE(beneficiary_name,'')<>'' OR COALESCE(story,'')<>'' OR COALESCE(image_stored_name,'')<>'' ORDER BY created_at DESC,id DESC LIMIT 4")->fetchAll();
$homepageSponsors = $db->query('SELECT * FROM sponsors ORDER BY created_at DESC,id DESC LIMIT 12')->fetchAll();
require __DIR__ . '/inc/header.php';
?>
<section class="hero"><div class="container hero-grid"><div>
<div class="eyebrow">Applications are open</div>
<h1><?= h(setting('homepage_headline')) ?></h1>
<p><?= h(setting('homepage_subtitle')) ?></p>
<div class="hero-actions"><a class="btn btn-primary" href="<?= h(app_url('apply.php')) ?>">Apply for a Grant →</a><a class="btn btn-outline" href="<?= h(app_url('track.php')) ?>">Track Application</a></div>
</div><aside class="hero-card"><h3>Your application, clearly tracked</h3><p class="muted">Apply online and receive a unique tracking code immediately.</p>
<div class="hero-stat"><b>1</b><div><strong>Choose your location</strong><br><span class="muted">We show the application form configured for your region.</span></div></div>
<div class="hero-stat"><b>2</b><div><strong>Submit your information</strong><br><span class="muted">Complete the required questions and documents.</span></div></div>
<div class="hero-stat"><b>3</b><div><strong>Track every update</strong><br><span class="muted">Use your private tracking code to follow the review process.</span></div></div>
</aside></div></section>

<section class="section"><div class="container"><div class="section-title"><span class="eyebrow" style="color:var(--brand)">Who we support</span><h2>Funding opportunities for people building toward meaningful goals</h2><p>EmpowerME welcomes eligible applicants across several categories. Each application is considered according to the requirements of the relevant grant form and review process.</p></div>
<div class="card-grid"><article class="info-card"><div class="icon">01</div><h3>Entrepreneurs & startups</h3><p>Applicants developing new ventures, products, services, or early-stage businesses.</p></article><article class="info-card"><div class="icon">02</div><h3>Small-business owners</h3><p>Business owners seeking support for growth, equipment, capacity, or expansion plans.</p></article><article class="info-card"><div class="icon">03</div><h3>Students & individuals</h3><p>Eligible applicants pursuing education, personal development, or meaningful community-focused projects.</p></article></div></div></section>

<section class="section section-soft"><div class="container"><div class="section-title"><span class="eyebrow" style="color:var(--brand)">How it works</span><h2>A straightforward application process</h2></div><div class="steps"><div class="step"><h3>Select your location</h3><p class="muted">Choose USA, UK, Canada, Australia, or Others.</p></div><div class="step"><h3>Complete your form</h3><p class="muted">Provide the information and documents requested for your location.</p></div><div class="step"><h3>Get your code</h3><p class="muted">Receive a unique tracking code on screen and by email.</p></div><div class="step"><h3>Follow progress</h3><p class="muted">View status history and respond to information requests online.</p></div></div></div></section>

<section class="section"><div class="container split"><div class="photo"><img loading="lazy" src="https://images.pexels.com/photos/5999809/pexels-photo-5999809.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="Entrepreneur taking notes while working on a laptop"></div><div><span class="eyebrow" style="color:var(--brand)">Built around your next step</span><h2 style="font-size:42px;line-height:1.12;margin:16px 0">One program. Different kinds of ambition.</h2><p class="muted">Whether you are developing a business, pursuing your education, launching a startup, or working toward a community-focused initiative, the application process is designed to collect the information relevant to your location and circumstances.</p><div class="check-list"><div class="check">Open to eligible applicants worldwide</div><div class="check">Location-specific application requirements</div><div class="check">Clear application and review process</div><div class="check">Online status tracking and document responses</div></div><a class="btn btn-primary" href="<?= h(app_url('apply.php')) ?>">Start an application</a></div></div></section>

<?php if ($recentTestimonials): ?>
<section class="section section-soft"><div class="container"><div class="section-head-row"><div class="section-title"><span class="eyebrow" style="color:var(--brand)">Beneficiary experiences</span><h2>Recent testimonials</h2><p>Read experiences shared by beneficiaries featured by the program.</p></div><a class="text-link" href="<?=h(app_url('testimonials.php'))?>">View all testimonials →</a></div>
<div class="testimonial-grid home-testimonial-grid"><?php foreach($recentTestimonials as $t):?><article class="testimonial-card"><?php if($t['image_stored_name']):?><div class="testimonial-photo"><img loading="lazy" src="<?=h(app_url('media.php?type=testimonial&id='.(int)$t['id']))?>" alt="<?=h($t['beneficiary_name']?:'Grant beneficiary')?>"></div><?php endif;?><div class="testimonial-body"><?php if($t['story']):?><p class="testimonial-story">“<?=nl2br(h($t['story']))?>”</p><?php endif;?><?php if($t['beneficiary_name']):?><strong class="testimonial-name"><?=h($t['beneficiary_name'])?></strong><?php endif;?></div></article><?php endforeach;?></div>
</div></section>
<?php endif; ?>

<?php if ($homepageSponsors): ?>
<section class="section"><div class="container"><div class="section-head-row"><div class="section-title"><span class="eyebrow" style="color:var(--brand)">Program support</span><h2>Our sponsors</h2><p>Organizations and partners supporting the EmpowerME Grant Program.</p></div><a class="text-link" href="<?=h(app_url('sponsorship.php'))?>">View sponsors →</a></div>
<div class="sponsor-strip"><?php foreach($homepageSponsors as $s):?><div class="sponsor-strip-item"><img loading="lazy" src="<?=h(app_url('media.php?type=sponsor&id='.(int)$s['id']))?>" alt="<?=h($s['name'])?> logo"><span><?=h($s['name'])?></span></div><?php endforeach;?></div>
</div></section>
<?php endif; ?>

<section class="section section-soft"><div class="container"><div class="cta"><div><h2>Ready to take the first step?</h2><p>Review the application for your location and submit your information online.</p></div><div class="hero-actions" style="margin:0"><a class="btn" style="background:#fff;color:var(--brand)" href="<?= h(app_url('apply.php')) ?>">Apply now</a><a class="btn btn-outline" style="background:transparent;color:#fff;border-color:#8fb2c3" href="<?= h(app_url('contact.php')) ?>">Contact us</a></div></div></div></section>
<?php require __DIR__ . '/inc/footer.php'; ?>
