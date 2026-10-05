</main>
<footer class="site-footer"><div class="container footer-grid">
<div><div class="footer-brand"><img src="<?= h(app_url('assets/images/logo.png')) ?>" alt="EmpowerME Grant Program"></div><p>Support and funding opportunities for eligible applicants working toward meaningful goals.</p></div>
<div><h3>Quick links</h3><a href="<?= h(app_url('apply.php')) ?>">Apply for a grant</a><a href="<?= h(app_url('track.php')) ?>">Track application</a><a href="<?= h(app_url('about.php')) ?>">About us</a><a href="<?= h(app_url('testimonials.php')) ?>">Testimonials</a><a href="<?= h(app_url('sponsorship.php')) ?>">Sponsors</a></div>
<div><h3>Contact</h3><a href="mailto:<?= h(setting('public_email')) ?>"><?= h(setting('public_email')) ?></a><a href="tel:<?= h(preg_replace('/[^+0-9]/','',setting('public_phone'))) ?>"><?= h(setting('public_phone')) ?></a><?php if(setting('whatsapp_url')): ?><a target="_blank" rel="noopener" href="<?= h(setting('whatsapp_url')) ?>">WhatsApp inquiry</a><?php endif; ?><span><?= h(setting('organization_location','United States')) ?></span></div>
<div><h3>Legal</h3><a href="<?= h(app_url('privacy.php')) ?>">Privacy Policy</a><a href="<?= h(app_url('terms.php')) ?>">Terms of Use</a></div>
</div><div class="container footer-bottom">© <?= date('Y') ?> <?= h(setting('program_name')) ?>. All rights reserved.</div></footer>
<script src="<?= h(app_url('assets/js/app.js')) ?>"></script>
</body></html>
