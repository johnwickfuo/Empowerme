<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/storage/uploads');
define('MEDIA_PATH', ROOT_PATH . '/storage/media');

define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    $setup = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'setup.php';
    if (!$setup) {
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/setup.php');
        exit;
    }
    return;
}

$config = require $configFile;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/upgrades.php';

$db = db_connect($config['db']);
run_schema_upgrades($db);
