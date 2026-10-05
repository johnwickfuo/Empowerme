<?php
$pageTitle = $pageTitle ?? setting('program_name');
$current = basename($_SERVER['PHP_SELF'] ?? '');
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h(setting('homepage_subtitle')) ?>">
<link rel="icon" href="<?= h(app_url('assets/images/logo.png')) ?>">
<link rel="stylesheet" href="<?= h(app_url('assets/css/style.css')) ?>">
</head><body>
<header class="site-header"><div class="container nav-wrap">
<a class="brand" href="<?= h(app_url()) ?>"><img src="<?= h(app_url('assets/images/logo.png')) ?>" alt="EmpowerME Grant Program"></a>
<button class="nav-toggle" aria-label="Open menu" data-nav-toggle>☰</button>
<nav class="main-nav" data-nav>
<a class="<?= $current==='index.php'?'active':'' ?>" href="<?= h(app_url()) ?>">Home</a>
<a class="<?= $current==='about.php'?'active':'' ?>" href="<?= h(app_url('about.php')) ?>">About Us</a>
<a class="<?= $current==='apply.php'?'active':'' ?>" href="<?= h(app_url('apply.php')) ?>">Apply</a>
<a class="<?= $current==='track.php'?'active':'' ?>" href="<?= h(app_url('track.php')) ?>">Track</a>
<a class="<?= $current==='testimonials.php'?'active':'' ?>" href="<?= h(app_url('testimonials.php')) ?>">Testimonials</a>
<a class="<?= $current==='sponsorship.php'?'active':'' ?>" href="<?= h(app_url('sponsorship.php')) ?>">Sponsors</a>
<a class="<?= $current==='contact.php'?'active':'' ?>" href="<?= h(app_url('contact.php')) ?>">Contact Us</a>
</nav></div></header>
<?php foreach (get_flashes() as $f): ?><div class="container"><div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div></div><?php endforeach; ?>
<main>
