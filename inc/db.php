<?php
declare(strict_types=1);

function db_connect(array $cfg): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $cfg['host'] ?? 'localhost',
        (int)($cfg['port'] ?? 3306),
        $cfg['name'] ?? ''
    );
    return new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
