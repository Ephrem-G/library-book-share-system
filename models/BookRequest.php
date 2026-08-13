<?php

require_once __DIR__ . '/../config/database.php';

class BookRequest
{
    private static function selectSql(): string
    {
        return 'SELECT r.*,
                       b.title AS book_title,
                       ru.full_name AS requester_name,
                       ow.full_name AS owner_name
                  FROM requests r
                  JOIN books b ON b.id = r.book_id
                  JOIN users ru ON ru.id = r.requester_id
                  JOIN users ow ON ow.id = r.owner_id';
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare(
            self::selectSql() . ' WHERE r.owner_id = ? OR r.requester_id = ? ORDER BY r.created_at DESC'
        );
        $stmt->execute([$userId, $userId]);
        return array_map([self::class, 'shape'], $stmt->fetchAll());
    }

    public static function create(int $bookId, int $requesterId, int $ownerId): int
    {
        $stmt = db()->prepare(
            "INSERT INTO requests (book_id, requester_id, owner_id, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([$bookId, $requesterId, $ownerId]);
        return (int) db()->lastInsertId();
    }

    public static function hasPendingDuplicate(int $bookId, int $requesterId): bool
    {
        $stmt = db()->prepare(
            "SELECT id FROM requests WHERE book_id = ? AND requester_id = ? AND status = 'pending'"
        );
        $stmt->execute([$bookId, $requesterId]);
        return (bool) $stmt->fetch();
    }

    public static function findRaw(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM requests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE r.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::shape($row) : null;
    }

    public static function updateStatus(int $requestId, string $status): void
    {
        $stmt = db()->prepare('UPDATE requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, $requestId]);
    }

    public static function rejectOtherPending(int $bookId, int $approvedRequestId): void
    {
        $stmt = db()->prepare(
            "UPDATE requests
                SET status = 'rejected'
              WHERE book_id = ? AND id <> ? AND status = 'pending'"
        );
        $stmt->execute([$bookId, $approvedRequestId]);
    }

    public static function updateBookStatus(int $bookId, string $status): void
    {
        $stmt = db()->prepare('UPDATE books SET status = ? WHERE id = ?');
        $stmt->execute([$status, $bookId]);
    }

    // Store the return/due date the owner picks when approving a request.
    public static function setDueDate(int $requestId, string $dueDate): void
    {
        $stmt = db()->prepare('UPDATE requests SET due_date = ? WHERE id = ?');
        $stmt->execute([$dueDate, $requestId]);
    }

    // Flag that the borrower has handed the book back (awaiting owner confirmation).
    public static function markReturnedByBorrower(int $requestId): void
    {
        $stmt = db()->prepare('UPDATE requests SET returned_by_borrower = 1 WHERE id = ?');
        $stmt->execute([$requestId]);
    }

    // Flag that the owner has confirmed the return (request is then marked completed).
    public static function confirmReturn(int $requestId): void
    {
        $stmt = db()->prepare('UPDATE requests SET return_confirmed = 1 WHERE id = ?');
        $stmt->execute([$requestId]);
    }

    private static function shape(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'bookId' => (int) $r['book_id'],
            'bookTitle' => $r['book_title'],
            'requesterId' => (int) $r['requester_id'],
            'requesterName' => $r['requester_name'],
            'ownerId' => (int) $r['owner_id'],
            'ownerName' => $r['owner_name'],
            'status' => $r['status'],
            // Return-tracking fields (added by migration 002). Guarded with ?? so the
            // code still works if the columns are missing on an un-migrated database.
            'dueDate' => $r['due_date'] ?? null,
            'returnedByBorrower' => (int) ($r['returned_by_borrower'] ?? 0),
            'returnConfirmed' => (int) ($r['return_confirmed'] ?? 0),
            'dateRequested' => $r['created_at'],
            'updatedAt' => $r['updated_at'],
        ];
    }
}
