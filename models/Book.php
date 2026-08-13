<?php

require_once __DIR__ . '/../config/database.php';

class Book
{
    private static function selectSql(): string
    {
        return 'SELECT b.*,
                       u.full_name AS owner_name,
                       u.location_text AS owner_location,
                       u.latitude AS owner_latitude,
                       u.longitude AS owner_longitude
                  FROM books b
                  JOIN users u ON u.id = b.owner_id';
    }

    public static function all(array $filters, int $currentUserId): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(b.title LIKE ? OR b.author LIKE ? OR u.location_text LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }

        if (!empty($filters['category'])) {
            $where[] = 'b.category = ?';
            $params[] = $filters['category'];
        }

        $sql = self::selectSql() . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

        if (($filters['nearby'] ?? '') === '1') {
            $stmt = db()->prepare('SELECT latitude, longitude FROM users WHERE id = ?');
            $stmt->execute([$currentUserId]);
            $me = $stmt->fetch();
            if ($me && $me['latitude'] !== null && $me['longitude'] !== null) {
                $sql .= ' ORDER BY (ABS(IFNULL(u.latitude,0) - ?) + ABS(IFNULL(u.longitude,0) - ?)) ASC';
                array_push($params, $me['latitude'], $me['longitude']);
            } else {
                $sql .= ' ORDER BY b.created_at DESC';
            }
        } else {
            $sql .= ' ORDER BY b.created_at DESC';
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'shape'], $stmt->fetchAll());
    }

    public static function mine(int $ownerId): array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE b.owner_id = ? ORDER BY b.created_at DESC');
        $stmt->execute([$ownerId]);
        return array_map([self::class, 'shape'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE b.id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        return $book ? self::shape($book) : null;
    }

    public static function rawOwner(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, owner_id FROM books WHERE id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        return $book ?: null;
    }

    public static function create(int $ownerId, array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO books (owner_id, title, author, category, description, `condition`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $ownerId,
            trim($data['title']),
            trim($data['author']),
            $data['category'],
            trim($data['description'] ?? ''),
            $data['condition'],
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE books SET title = ?, author = ?, category = ?, description = ?, `condition` = ? WHERE id = ?'
        );
        $stmt->execute([
            trim($data['title']),
            trim($data['author']),
            $data['category'],
            trim($data['description'] ?? ''),
            $data['condition'],
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$id]);
    }

    private static function shape(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'author' => $row['author'],
            'category' => $row['category'],
            'description' => $row['description'] ?? '',
            'condition' => $row['condition'],
            'status' => $row['status'],
            'ownerId' => (int) $row['owner_id'],
            'ownerName' => $row['owner_name'],
            'ownerLocation' => $row['owner_location'],
            'ownerLatitude' => $row['owner_latitude'] !== null ? (float) $row['owner_latitude'] : null,
            'ownerLongitude' => $row['owner_longitude'] !== null ? (float) $row['owner_longitude'] : null,
            'dateAdded' => $row['created_at'],
        ];
    }
}

