<?php

require_once __DIR__ . '/AuthMiddleware.php';

function require_admin(): array
{
    $user = JwtService::verify(bearer_token());
    if (!$user) {
        json_error('Invalid or expired token', 401);
    }
    if (($user['role'] ?? '') !== 'admin') {
        json_error('Admin only', 403);
    }
    return $user;
}

