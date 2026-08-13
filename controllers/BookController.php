<?php

require_once __DIR__ . '/../models/Book.php';

class BookController
{
    public static function index(array $authUser): void
    {
        require_method('GET');
        json_success(Book::all($_GET, (int) $authUser['id']));
    }

    public static function mine(array $authUser): void
    {
        require_method('GET');
        json_success(Book::mine((int) $authUser['id']));
    }

    public static function show(int $id): void
    {
        require_method('GET');
        $book = Book::find($id);
        if (!$book) {
            json_error('Book not found', 404);
        }
        json_success($book);
    }

    public static function create(array $authUser): void
    {
        require_method('POST');
        $body = read_json_body();
        self::validate($body);

        $id = Book::create((int) $authUser['id'], $body);
        json_success(Book::find($id));
    }

    public static function update(array $authUser, int $id): void
    {
        require_method('PUT');
        $book = Book::rawOwner($id);
        if (!$book) {
            json_error('Book not found', 404);
        }
        if ((int) $book['owner_id'] !== (int) $authUser['id']) {
            json_error('Not your book', 403);
        }

        $body = read_json_body();
        self::validate($body);
        Book::update($id, $body);
        json_success(Book::find($id));
    }

    public static function delete(array $authUser, int $id): void
    {
        require_method('DELETE');
        $book = Book::rawOwner($id);
        if (!$book) {
            json_error('Book not found', 404);
        }
        if ((int) $book['owner_id'] !== (int) $authUser['id']) {
            json_error('Not your book', 403);
        }
        Book::delete($id);
        json_success(null);
    }

    private static function validate(array $data): void
    {
        foreach (['title', 'author', 'category', 'condition'] as $field) {
            if (trim($data[$field] ?? '') === '') {
                json_error('Title, author, category and condition are required');
            }
        }
    }
}

