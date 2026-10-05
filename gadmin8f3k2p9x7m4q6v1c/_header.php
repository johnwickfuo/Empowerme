<?php
$adminTitle=$adminTitle??'Admin';
$adminCurrent=basename($_SERVER['PHP_SELF']??'');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($adminTitle)?> | EmpowerME Admin</title><link rel="stylesheet" href="<?=h(app_url('assets/css/admin.css'))?>"></head><body><div class="admin-shell"><aside class="sidebar"><a class="admin-brand" href="index.php"><img src="<?=h(app_url('assets/images/logo.png'))?>" alt="EmpowerME Grant Program"></a><nav>
<a class="<?= $adminCurrent==='index.php'?'active':''?>" href="index.php">Dashboard</a>
<a class="<?= in_array($adminCurrent,['applications.php','application.php'])?'active':''?>" href="applications.php">Applications</a>
<a class="<?= in_array($adminCurrent,['forms.php','form-edit.php'])?'active':''?>" href="forms.php">Forms</a>
<a class="<?= $adminCurrent==='statuses.php'?'active':''?>" href="statuses.php">Statuses</a>
<a class="<?= $adminCurrent==='testimonials.php'?'active':''?>" href="testimonials.php">Testimonials</a>
<a class="<?= $adminCurrent==='sponsors.php'?'active':''?>" href="sponsors.php">Sponsors</a>
<a class="<?= $adminCurrent==='contacts.php'?'active':''?>" href="contacts.php">Contact Messages</a>
<a class="<?= $adminCurrent==='settings.php'?'active':''?>" href="settings.php">Settings & Email</a>
<a href="<?=h(app_url())?>" target="_blank">View Website ↗</a>
</nav><div class="private-note">Private administration URL<br><strong>Do not publish this address.</strong></div></aside><div class="admin-main"><header class="topbar"><button class="menu-btn" data-admin-menu>☰</button><div><strong><?=h($adminTitle)?></strong></div><div class="top-code">EmpowerME</div></header><main class="admin-content">
<?php foreach(get_flashes() as $f):?><div class="notice <?=h($f['type'])?>"><?=h($f['message'])?></div><?php endforeach;?>
