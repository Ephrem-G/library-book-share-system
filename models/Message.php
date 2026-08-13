<?php

require_once __DIR__ . '/../config/database.php';

class Message
{
    private static function tableColumns(): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $stmt = db()->prepare(
            "SELECT COLUMN_NAME
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'messages'"
        );
        $stmt->execute();
        $columns = array_column($stmt->fetchAll(), 'COLUMN_NAME');
        return $columns;
    }

    private static function firstExistingColumn(array $choices, string $fallback): string
    {
        $columns = self::tableColumns();
        foreach ($choices as $choice) {
            if (in_array($choice, $columns, true)) {
                return $choice;
            }
        }
        return $fallback;
    }

    private static function fromColumn(): string
    {
        return self::firstExistingColumn(['from_id', 'sender_id'], 'from_id');
    }

    private static function toColumn(): string
    {
        return self::firstExistingColumn(['to_id', 'receiver_id', 'recipient_id'], 'to_id');
    }

    private static function bodyColumn(): string
    {
        return self::firstExistingColumn(['body', 'message', 'text', 'content'], 'body');
    }

    private static function timeColumn(): string
    {
        return self::firstExistingColumn(['created_at', 'sent_at', 'timestamp'], 'created_at');
    }

    private static function mapRow(array $m): array
    {
        $fromColumn = self::fromColumn();
        $toColumn = self::toColumn();
        $bodyColumn = self::bodyColumn();
        $timeColumn = self::timeColumn();

        return [
            'id' => (int) $m['id'],
            'fromId' => (int) $m[$fromColumn],
            'toId' => (int) $m[$toColumn],
            'text' => (string) ($m[$bodyColumn] ?? ''),
            'timestamp' => $m[$timeColumn] ?? date('Y-m-d H:i:s'),
        ];
    }

    public static function conversations(int $userId): array
    {
        $fromColumn = self::fromColumn();
        $toColumn = self::toColumn();
        $bodyColumn = self::bodyColumn();
        $timeColumn = self::timeColumn();

        $sql = "SELECT m.*, uf.full_name AS from_name, ut.full_name AS to_name
                  FROM messages m
                  JOIN users uf ON uf.id = m.{$fromColumn}
                  JOIN users ut ON ut.id = m.{$toColumn}
                 WHERE m.{$fromColumn} = ? OR m.{$toColumn} = ?
                 ORDER BY m.{$timeColumn} DESC, m.id DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute([$userId, $userId]);

        $seen = [];
        $result = [];
        foreach ($stmt->fetchAll() as $m) {
            $partnerId = (int) ($m[$fromColumn] == $userId ? $m[$toColumn] : $m[$fromColumn]);
            if (isset($seen[$partnerId])) {
                continue;
            }
            $seen[$partnerId] = true;

            $result[] = [
                'partnerId' => $partnerId,
                'partnerName' => $m[$fromColumn] == $userId ? $m['to_name'] : $m['from_name'],
                'lastMessage' => (string) ($m[$bodyColumn] ?? ''),
                'lastTime' => $m[$timeColumn] ?? null,
            ];
        }
        return $result;
    }

    public static function thread(int $me, int $other): array
    {
        $fromColumn = self::fromColumn();
        $toColumn = self::toColumn();
        $timeColumn = self::timeColumn();

        $sql = "SELECT * FROM messages
                 WHERE ( {$fromColumn} = ? AND {$toColumn} = ? )
                    OR ( {$fromColumn} = ? AND {$toColumn} = ? )
                 ORDER BY {$timeColumn} ASC, id ASC";

        $stmt = db()->prepare($sql);
        $stmt->execute([$me, $other, $other, $me]);

        return array_map(fn(array $m): array => self::mapRow($m), $stmt->fetchAll());
    }

    public static function create(int $fromId, int $toId, string $body): array
    {
        $body = trim($body);
        $fromColumn = self::fromColumn();
        $toColumn = self::toColumn();
        $bodyColumn = self::bodyColumn();

        $sql = "INSERT INTO messages ({$fromColumn}, {$toColumn}, {$bodyColumn}) VALUES (?, ?, ?)";
        $stmt = db()->prepare($sql);
        $stmt->execute([$fromId, $toId, $body]);

        $id = (int) db()->lastInsertId();
        $stmt = db()->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::mapRow($row) : [
            'id' => $id,
            'fromId' => $fromId,
            'toId' => $toId,
            'text' => $body,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
