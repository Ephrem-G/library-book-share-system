<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/JwtService.php';
require_once __DIR__ . '/../services/EmailService.php';

class AuthController
{
    public static function register(): void
    {
        require_method('POST');
        $body = read_json_body();

        $fullName = trim($body['fullName'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $location = trim($body['location'] ?? '');
        $phone = trim($body['phone'] ?? '');

        if ($fullName === '' || $email === '' || $password === '' || $location === '') {
            json_error('Name, email, password and location are required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Please enter a valid email address');
        }
        if (strlen($password) < 6) {
            json_error('Password must be at least 6 characters');
        }
        // Kenyan mobile: exactly 10 digits, starts 07 or 01, digits only.
        if ($phone === '' || !preg_match('/^0[17][0-9]{8}$/', $phone)) {
            json_error('Phone number must be exactly 10 digits and start with 07 or 01');
        }
        if (User::findByEmail($email)) {
            json_error('Email already registered', 409);
        }

        $token = bin2hex(random_bytes(32));
        $userId = User::create([
            'fullName' => $fullName,
            'email' => $email,
            'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone,
            'location' => $location,
            'latitude' => $body['latitude'] ?? null,
            'longitude' => $body['longitude'] ?? null,
            'verificationToken' => $token,
        ]);

        EmailService::sendVerificationEmail($email, $fullName, $token);

        json_success([
            'message' => 'Account created. Please verify your email before logging in.',
            'userId' => $userId,
        ]);
    }

    public static function login(): void
    {
        require_method('POST');
        $body = read_json_body();

        $email = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        if ($email === '' || $password === '') {
            json_error('Email and password are required');
        }

        $userRow = User::findByEmail($email);
        if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
            json_error('Invalid email or password', 401);
        }
        if ((int) ($userRow['is_verified'] ?? 0) !== 1) {
            json_error('Please verify your email before logging in.', 403);
        }

        $user = User::publicUser($userRow);
        $token = JwtService::sign(['id' => $user['id'], 'email' => $user['email'], 'role' => 'user']);
        json_success(['token' => $token, 'user' => $user]);
    }

    public static function me(array $authUser): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $user = User::findById((int) $authUser['id']);
            if (!$user) {
                json_error('User not found', 404);
            }
            json_success(User::publicUser($user));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $body = read_json_body();
            if (trim($body['fullName'] ?? '') === '' || trim($body['location'] ?? '') === '') {
                json_error('Name and location are required');
            }
            // Same Kenyan mobile rule as registration: 10 digits, starts 07 or 01.
            $phone = trim($body['phone'] ?? '');
            if ($phone === '' || !preg_match('/^0[17][0-9]{8}$/', $phone)) {
                json_error('Phone number must be exactly 10 digits and start with 07 or 01');
            }
            User::updateProfile((int) $authUser['id'], $body);
            json_success(User::publicUser(User::findById((int) $authUser['id'])));
        }

        json_error('Method not allowed', 405);
    }

    public static function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';
        if ($token === '') {
            header('Location: ../../public/verification-failed.php');
            exit;
        }

        $user = User::findByVerificationToken($token);
        if (!$user) {
            header('Location: ../../public/verification-failed.php');
            exit;
        }

        User::verifyEmail((int) $user['id']);
        header('Location: ../../public/verification-success.php');
        exit;
    }

    public static function resendVerification(): void
    {
        require_method('POST');
        $body = read_json_body();
        $email = strtolower(trim($body['email'] ?? ''));
        if ($email === '') {
            json_error('Email is required');
        }

        $user = User::findByEmail($email);
        if (!$user) {
            json_error('Email not found', 404);
        }
        if ((int) ($user['is_verified'] ?? 0) === 1) {
            json_error('This account is already verified');
        }

        $token = bin2hex(random_bytes(32));
        User::updateVerificationToken((int) $user['id'], $token);
        EmailService::sendVerificationEmail($user['email'], $user['full_name'], $token);
        json_success(['message' => 'Verification email sent again.']);
    }
}

