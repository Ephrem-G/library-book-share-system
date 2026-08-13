<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/JwtService.php';

class AdminController
{
    public static function login(): void
    {
        require_method('POST');
        $body = read_json_body();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === env_value('ADMIN_EMAIL', 'admin@library.local')
            && $password === env_value('ADMIN_PASSWORD', 'admin123')) {
            $token = JwtService::sign(['id' => 0, 'email' => $email, 'role' => 'admin']);
            json_success(['token' => $token]);
        }

        json_error('Invalid admin credentials', 401);
    }

    public static function users(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = db()->query(
                'SELECT id, full_name, email, phone, location_text, created_at, is_verified
                   FROM users ORDER BY id ASC'
            )->fetchAll();

            json_success(array_map(function (array $u): array {
                return [
                    'id' => (int) $u['id'],
                    'fullName' => $u['full_name'],
                    'email' => $u['email'],
                    'phone' => $u['phone'],
                    'location' => $u['location_text'],
                    'joinDate' => $u['created_at'],
                    'isVerified' => (int) $u['is_verified'],
                ];
            }, $rows));
        }

        json_error('Method not allowed', 405);
    }

    public static function deleteUser(int $id): void
    {
        require_method('DELETE');
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        json_success(null);
    }

    public static function books(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = db()->query(
                'SELECT b.id, b.title, b.author, b.category, b.status, b.created_at,
                        u.full_name AS owner_name
                   FROM books b
                   JOIN users u ON u.id = b.owner_id
                  ORDER BY b.created_at DESC'
            )->fetchAll();

            json_success(array_map(function (array $b): array {
                return [
                    'id' => (int) $b['id'],
                    'title' => $b['title'],
                    'author' => $b['author'],
                    'category' => $b['category'],
                    'status' => $b['status'],
                    'ownerName' => $b['owner_name'],
                    'dateAdded' => $b['created_at'],
                ];
            }, $rows));
        }

        json_error('Method not allowed', 405);
    }

    public static function deleteBook(int $id): void
    {
        require_method('DELETE');
        $stmt = db()->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$id]);
        json_success(null);
    }

    // System Summary Report: live counts drawn straight from existing tables.
    // Nothing is stored separately — every number is generated on request.
    public static function stats(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $db = db();
            json_success([
                'totalUsers'         => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'totalBooks'         => (int) $db->query('SELECT COUNT(*) FROM books')->fetchColumn(),
                'availableBooks'     => (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetchColumn(),
                'borrowedBooks'      => (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'borrowed'")->fetchColumn(),
                'pendingRequests'    => (int) $db->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn(),
                'approvedRequests'   => (int) $db->query("SELECT COUNT(*) FROM requests WHERE status = 'approved'")->fetchColumn(),
                'completedExchanges' => (int) $db->query("SELECT COUNT(*) FROM requests WHERE status = 'completed'")->fetchColumn(),
                // Overdue = still on loan (approved, not yet confirmed returned) and past its due date.
                'overdueBooks'       => (int) $db->query(
                    "SELECT COUNT(*) FROM requests
                      WHERE status = 'approved' AND due_date IS NOT NULL
                        AND due_date < CURDATE() AND return_confirmed = 0"
                )->fetchColumn(),
            ]);
        }

        json_error('Method not allowed', 405);
    }
}

