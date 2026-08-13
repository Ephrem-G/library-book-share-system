<?php

require_once __DIR__ . '/../services/JwtService.php';
require_once __DIR__ . '/../config/app.php';

function bearer_token(): ?string
{
    $possibleHeaders = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['Authorization'] ?? '',
    ];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $possibleHeaders[] = $headers['Authorization'] ?? '';
        $possibleHeaders[] = $headers['authorization'] ?? '';
    }

    foreach ($possibleHeaders as $header) {
        $header = trim((string) $header);
        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
    }

    return null;
}

function require_auth(): array
{
    $user = JwtService::verify(bearer_token());
    if (!$user) {
        json_error('Invalid or expired token', 401);
    }
    if (($user['role'] ?? 'user') !== 'user') {
        json_error('User account required', 403);
    }
    return $user;
}
