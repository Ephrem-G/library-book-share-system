<?php

require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', 'localhost');
    $port = env_value('DB_PORT', '3306');
    $name = env_value('DB_NAME', 'library_book_share');
    $user = env_value('DB_USER', 'root');
    $pass = env_value('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

