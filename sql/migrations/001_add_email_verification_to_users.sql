-- Migration: add email verification fields to existing users table.
-- Run this once if your database already exists.

ALTER TABLE users
  ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar_url,
  ADD COLUMN verification_token VARCHAR(128) NULL AFTER is_verified,
  ADD COLUMN verification_sent_at DATETIME NULL AFTER verification_token;

CREATE INDEX idx_users_verification_token ON users (verification_token);

-- Optional for existing demo accounts: mark them verified so demo login keeps working.
UPDATE users SET is_verified = 1 WHERE email IN (
  'jane@example.com',
  'kevin@example.com',
  'mary@example.com'
);

