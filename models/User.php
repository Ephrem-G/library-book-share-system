<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByVerificationToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE verification_token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users
              (full_name, email, password_hash, phone, location_text, latitude, longitude,
               verification_token, verification_sent_at, is_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)'
        );
        $stmt->execute([
            trim($data['fullName']),
            strtolower(trim($data['email'])),
            $data['passwordHash'],
            $data['phone'] ?: null,
            trim($data['location']),
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['verificationToken'],
        ]);
        return (int) db()->lastInsertId();
    }

    public static function verifyEmail(int $id): void
    {
        $stmt = db()->prepare(
            'UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function updateVerificationToken(int $id, string $token): void
    {
        $stmt = db()->prepare(
            'UPDATE users SET verification_token = ?, verification_sent_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$token, $id]);
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE users
                SET full_name = ?, location_text = ?, phone = ?, latitude = ?, longitude = ?
              WHERE id = ?'
        );
        $stmt->execute([
            trim($data['fullName']),
            trim($data['location']),
            $data['phone'] ?: null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $id,
        ]);
    }

    public static function publicUser(array $u): array
    {
        return [
            'id' => (int) $u['id'],
            'fullName' => $u['full_name'],
            'email' => $u['email'],
            'phone' => $u['phone'],
            'location' => $u['location_text'],
            'latitude' => $u['latitude'] !== null ? (float) $u['latitude'] : null,
            'longitude' => $u['longitude'] !== null ? (float) $u['longitude'] : null,
            'avatarUrl' => $u['avatar_url'] ?? null,
            'joinDate' => $u['created_at'],
            'isVerified' => (int) ($u['is_verified'] ?? 0),
        ];
    }
}

