<?php

require_once __DIR__ . '/app.php';

return [
    'enabled' => env_value('MAIL_ENABLED', 'false') === 'true',
    'host' => env_value('SMTP_HOST', 'smtp.gmail.com'),
    'port' => (int) env_value('SMTP_PORT', '587'),
    'username' => env_value('SMTP_USERNAME', ''),
    'password' => env_value('SMTP_PASSWORD', ''),
    'encryption' => env_value('SMTP_ENCRYPTION', 'tls'),
    'from_email' => env_value('MAIL_FROM_EMAIL', 'noreply@library.local'),
    'from_name' => env_value('MAIL_FROM_NAME', 'Library Book Share'),
];

