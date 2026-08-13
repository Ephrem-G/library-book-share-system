<?php

// Shared application helpers. Keep this file small and beginner-friendly.

function load_env(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : $value;
}

function base_url(): string
{
    return rtrim(env_value('APP_URL', 'http://localhost/library-book-share'), '/');
}

function json_success(mixed $data = null): void
{
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Method not allowed', 405);
    }
}

load_env(dirname(__DIR__) . '/.env');

