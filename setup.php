<?php
declare(strict_types=1);

$configPath = __DIR__ . '/inc/config.php';
$lockPath = __DIR__ . '/storage/.installed';
if (file_exists($configPath) && file_exists($lockPath)) {
    http_response_code(403);
    exit('EmpowerME is already installed. Delete setup.php from the server.');
}

$error = '';
$success = false;
$adminFolder = 'gadmin8f3k2p9x7m4q6v1c';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? 'localhost'));
    $port = (int)($_POST['db_port'] ?? 3306);
    $name = trim((string)($_POST['db_name'] ?? ''));
    $user = trim((string)($_POST['db_user'] ?? ''));
    $pass = (string)($_POST['db_pass'] ?? '');
    $appUrl = rtrim(trim((string)($_POST['app_url'] ?? '')), '/');

    if (!$name || !$user || !filter_var($appUrl, FILTER_VALIDATE_URL)) {
        $error = 'Enter valid database details and the full website URL, including https://.';
    } else {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $schema = require __DIR__ . '/inc/schema.php';
            $pdo->exec($schema);

            $config = "<?php\nreturn " . var_export([
                'db' => ['host'=>$host,'port'=>$port,'name'=>$name,'user'=>$user,'pass'=>$pass],
                'app_url' => $appUrl,
                'app_key' => bin2hex(random_bytes(32)),
            ], true) . ";\n";
            if (file_put_contents($configPath, $config, LOCK_EX) === false) throw new RuntimeException('Could not write inc/config.php. Make the inc folder writable and try again.');
            @chmod($configPath, 0640);
            if (!is_dir(__DIR__ . '/storage')) mkdir(__DIR__ . '/storage', 0770, true);
            file_put_contents($lockPath, date(DATE_ATOM));
            @chmod($lockPath, 0640);
            $success = true;
        } catch (Throwable $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install EmpowerME</title>
<style>body{font-family:Arial,sans-serif;background:#eef4f7;color:#15313f;margin:0;padding:40px 16px}.box{max-width:720px;margin:auto;background:white;padding:32px;border-radius:18px;box-shadow:0 14px 50px #1232}.brand{color:#064f78}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}label{font-weight:700;font-size:14px}input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cbd8df;border-radius:8px;margin-top:6px}.full{grid-column:1/-1}button{background:#064f78;color:#fff;border:0;padding:13px 20px;border-radius:9px;font-weight:700;cursor:pointer}.note{background:#f4f8fa;padding:14px;border-radius:10px}.err{background:#feecec;color:#8f1d1d;padding:14px;border-radius:10px}@media(max-width:620px){.grid{grid-template-columns:1fr}}</style></head><body><div class="box">
<h1 class="brand">EmpowerME Grant Program</h1>
<?php if ($success): ?>
<h2>Installation complete</h2><p>Your database tables and configuration are ready.</p>
<div class="note"><strong>Private admin URL:</strong><br><code><?= htmlspecialchars(rtrim($_POST['app_url'],'/').'/'.$adminFolder.'/') ?></code></div>
<p><strong>Important:</strong> Delete <code>setup.php</code> from the server after confirming the site works. Your admin area has no login by design, so keep the admin URL private.</p>
<p><a href="<?= htmlspecialchars(rtrim($_POST['app_url'],'/')) ?>">Open website</a></p>
<?php else: ?>
<p>Enter the MySQL credentials created in HestiaCP. The installer will create the required tables.</p>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post"><div class="grid">
<label>Database host<input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required></label>
<label>Database port<input name="db_port" type="number" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required></label>
<label>Database name<input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required></label>
<label>Database username<input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required></label>
<label class="full">Database password<input name="db_pass" type="password"></label>
<label class="full">Website URL<input name="app_url" placeholder="https://example.com" value="<?= htmlspecialchars($_POST['app_url'] ?? '') ?>" required></label>
</div><p><button>Install EmpowerME</button></p></form>
<?php endif; ?>
</div></body></html>
