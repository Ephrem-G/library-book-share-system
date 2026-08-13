# ERD Changes

The original ERD already had four main entities:

- `users`
- `books`
- `requests`
- `messages`

Email verification adds three attributes to `users`:

- `is_verified`: stores whether the user can login.
- `verification_token`: stores the secure random token sent by email.
- `verification_sent_at`: stores when the latest verification email was sent.

No new relationship is required. Verification belongs directly to the user account.

Relationships remain:

- One `user` owns many `books`.
- One `book` has many borrowing `requests`.
- One `user` can send many `requests`.
- One `user` can receive many `requests` as book owner.
- One `user` can send and receive many `messages`.

