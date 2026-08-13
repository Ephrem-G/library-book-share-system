<?php

require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/BookRequest.php';
require_once __DIR__ . '/../models/Message.php'; // reused to notify the other party on return events

class RequestController
{
    public static function index(array $authUser): void
    {
        require_method('GET');
        json_success(BookRequest::forUser((int) $authUser['id']));
    }

    public static function create(array $authUser): void
    {
        require_method('POST');
        $body = read_json_body();
        $bookId = (int) ($body['bookId'] ?? 0);
        if ($bookId <= 0) {
            json_error('bookId is required');
        }

        $book = Book::find($bookId);
        if (!$book) {
            json_error('Book not found', 404);
        }
        if ((int) $book['ownerId'] === (int) $authUser['id']) {
            json_error("You can't request your own book");
        }
        if ($book['status'] !== 'available') {
            json_error('Book is not available');
        }
        if (BookRequest::hasPendingDuplicate($bookId, (int) $authUser['id'])) {
            json_error('You already have a pending request for this book', 409);
        }

        $id = BookRequest::create($bookId, (int) $authUser['id'], (int) $book['ownerId']);
        json_success(BookRequest::find($id));
    }

    public static function update(array $authUser, int $id): void
    {
        require_method('PATCH');
        $body = read_json_body();
        $action = $body['action'] ?? '';
        if (!in_array($action, ['approve', 'reject', 'cancel', 'complete', 'mark-returned', 'confirm-return'], true)) {
            json_error('Invalid action');
        }

        $request = BookRequest::findRaw($id);
        if (!$request) {
            json_error('Request not found', 404);
        }

        $isOwner = (int) $request['owner_id'] === (int) $authUser['id'];
        $isRequester = (int) $request['requester_id'] === (int) $authUser['id'];
        $newStatus = $request['status'];
        $newBookStatus = null;

        if ($action === 'approve') {
            if (!$isOwner) json_error('Only owner can approve', 403);
            if ($request['status'] !== 'pending') json_error('Request is not pending');
            $newStatus = 'approved';
            $newBookStatus = 'borrowed';
            // Owner may set a return/due date when approving. Optional for back-compat:
            // an approve with no dueDate still works exactly as before.
            $dueDate = trim((string) ($body['dueDate'] ?? ''));
            if ($dueDate !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                    json_error('Invalid due date');
                }
                BookRequest::setDueDate($id, $dueDate);
            }
        } elseif ($action === 'mark-returned') {
            // Borrower hands the book back; awaits the owner's confirmation.
            if (!$isRequester) json_error('Only the borrower can mark the book returned', 403);
            if ($request['status'] !== 'approved') json_error('Only a borrowed book can be marked returned');
            BookRequest::markReturnedByBorrower($id);
            // Notify the owner via the existing messaging system.
            Message::create(
                (int) $request['requester_id'],
                (int) $request['owner_id'],
                'I have marked the book as returned. Please confirm the return when you receive it.'
            );
            // Status and book availability stay unchanged until the owner confirms.
        } elseif ($action === 'confirm-return') {
            // Owner confirms receipt; this completes the loan and frees the book.
            if (!$isOwner) json_error('Only the owner can confirm the return', 403);
            if ($request['status'] !== 'approved') json_error('Request must be approved first');
            if ((int) $request['returned_by_borrower'] !== 1) {
                json_error('Borrower has not marked this as returned yet');
            }
            BookRequest::confirmReturn($id);
            $newStatus = 'completed';
            $newBookStatus = 'available';
            // Let the borrower know the return was confirmed.
            Message::create(
                (int) $request['owner_id'],
                (int) $request['requester_id'],
                'I have confirmed the return. The loan is now complete. Thank you!'
            );
        } elseif ($action === 'reject') {
            if (!$isOwner) json_error('Only owner can reject', 403);
            if ($request['status'] !== 'pending') json_error('Request is not pending');
            $newStatus = 'rejected';
        } elseif ($action === 'cancel') {
            if (!$isRequester) json_error('Only requester can cancel', 403);
            if ($request['status'] !== 'pending') json_error('Only pending requests can be cancelled');
            $newStatus = 'cancelled';
        } elseif ($action === 'complete') {
            if (!$isOwner) json_error('Only owner can mark completed', 403);
            if ($request['status'] !== 'approved') json_error('Request must be approved first');
            $newStatus = 'completed';
            $newBookStatus = 'available';
        }

        BookRequest::updateStatus($id, $newStatus);
        if ($newBookStatus) {
            BookRequest::updateBookStatus((int) $request['book_id'], $newBookStatus);
        }
        if ($action === 'approve') {
            BookRequest::rejectOtherPending((int) $request['book_id'], $id);
        }
        json_success(BookRequest::find($id));
    }
}
