<?php

require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/User.php';

class MessageController
{
    public static function index(array $authUser): void
    {
        $me = (int) $authUser['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Supports both:
            // api/index.php?path=messages                  -> conversation list
            // api/index.php?path=messages&userId=2         -> thread with user 2
            // api/index.php?path=messages&with=2           -> same thread, clearer name
            $threadUserId = (int) ($_GET['userId'] ?? $_GET['with'] ?? 0);
            if ($threadUserId > 0) {
                self::thread($authUser, $threadUserId);
                return;
            }

            json_success(Message::conversations($me));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = read_json_body();
            $toId = (int) ($body['toId'] ?? $body['to_id'] ?? 0);
            $message = trim((string) ($body['body'] ?? $body['message'] ?? $body['text'] ?? ''));

            if ($toId <= 0 || $message === '') {
                json_error('toId and body are required');
            }
            if ($toId === $me) {
                json_error("You can't message yourself");
            }
            if (!User::findById($toId)) {
                json_error('Recipient not found', 404);
            }

            $saved = Message::create($me, $toId, $message);

            // Return both the saved message and the full thread.
            // This lets the frontend redraw the chat even if polling/reload is delayed.
            json_success([
                'message' => $saved,
                'thread' => Message::thread($me, $toId),
            ]);
        }

        json_error('Method not allowed', 405);
    }

    public static function thread(array $authUser, int $otherUserId): void
    {
        require_method('GET');
        if ($otherUserId <= 0) {
            json_error('Invalid user id');
        }
        if ((int) $authUser['id'] === $otherUserId) {
            json_error("You can't open a chat with yourself");
        }
        if (!User::findById($otherUserId)) {
            json_error('User not found', 404);
        }
        json_success(Message::thread((int) $authUser['id'], $otherUserId));
    }
}
