<?php

require_once __DIR__ . '/../config/app.php';

class JwtService
{
    public static function sign(array $payload, int $days = 7): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['exp'] = time() + ($days * 24 * 60 * 60);

        $baseHeader = self::base64UrlEncode(json_encode($header));
        $basePayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $baseHeader . '.' . $basePayload, self::secret(), true);

        return $baseHeader . '.' . $basePayload . '.' . self::base64UrlEncode($signature);
    }

    public static function verify(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$baseHeader, $basePayload, $baseSignature] = $parts;
        $expected = self::base64UrlEncode(
            hash_hmac('sha256', $baseHeader . '.' . $basePayload, self::secret(), true)
        );

        if (!hash_equals($expected, $baseSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($basePayload), true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private static function secret(): string
    {
        return env_value('JWT_SECRET', 'dev_secret_change_me');
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

