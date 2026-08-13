<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/AdminMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/BookController.php';
require_once __DIR__ . '/../controllers/RequestController.php';
require_once __DIR__ . '/../controllers/MessageController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

try {
    $path = trim($_GET['path'] ?? '', '/');
    $parts = $path === '' ? [] : explode('/', $path);

    if ($path === 'health') {
        json_success(['ok' => true, 'time' => date('c')]);
    }

    if (($parts[0] ?? '') === 'auth') {
        $action = $parts[1] ?? '';
        if ($action === 'register') AuthController::register();
        if ($action === 'login') AuthController::login();
        if ($action === 'verify-email') AuthController::verifyEmail();
        if ($action === 'resend-verification') AuthController::resendVerification();
        if ($action === 'me') AuthController::me(require_auth());
    }

    if (($parts[0] ?? '') === 'books') {
        $authUser = require_auth();
        if (count($parts) === 1 || $parts[1] === '') {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') BookController::index($authUser);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') BookController::create($authUser);
        }
        if (($parts[1] ?? '') === 'mine') BookController::mine($authUser);
        if (isset($parts[1]) && is_numeric($parts[1])) {
            $id = (int) $parts[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') BookController::show($id);
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') BookController::update($authUser, $id);
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') BookController::delete($authUser, $id);
        }
    }

    if (($parts[0] ?? '') === 'requests') {
        $authUser = require_auth();
        if (count($parts) === 1 || $parts[1] === '') {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') RequestController::index($authUser);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') RequestController::create($authUser);
        }
        if (isset($parts[1]) && is_numeric($parts[1])) {
            RequestController::update($authUser, (int) $parts[1]);
        }
    }

    if (($parts[0] ?? '') === 'messages') {
        $authUser = require_auth();
        if (count($parts) === 1 || $parts[1] === '') {
            MessageController::index($authUser);
        }
        if (isset($parts[1]) && is_numeric($parts[1])) {
            MessageController::thread($authUser, (int) $parts[1]);
        }
    }

    if (($parts[0] ?? '') === 'admin') {
        $action = $parts[1] ?? '';
        if ($action === 'login') AdminController::login();

        require_admin();
        if ($action === 'stats' && count($parts) === 2) AdminController::stats();
        if ($action === 'users' && count($parts) === 2) AdminController::users();
        if ($action === 'users' && isset($parts[2]) && is_numeric($parts[2])) {
            AdminController::deleteUser((int) $parts[2]);
        }
        if ($action === 'books' && count($parts) === 2) AdminController::books();
        if ($action === 'books' && isset($parts[2]) && is_numeric($parts[2])) {
            AdminController::deleteBook((int) $parts[2]);
        }
    }

    json_error('API route not found', 404);
} catch (Throwable $e) {
    json_error('Server error: ' . $e->getMessage(), 500);
}

