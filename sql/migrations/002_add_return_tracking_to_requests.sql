-- Migration: add return-tracking fields to the requests table.
-- Run this once if your database already exists (phpMyAdmin > library_book_share > SQL).
-- Fresh installs get these columns from sql/schema.sql automatically.

ALTER TABLE requests
  -- The return/due date the owner sets when approving a request.
  ADD COLUMN due_date DATE NULL AFTER status,
  -- Set to 1 when the borrower clicks "Mark as Returned".
  ADD COLUMN returned_by_borrower TINYINT(1) NOT NULL DEFAULT 0 AFTER due_date,
  -- Set to 1 when the owner clicks "Confirm Return" (request then becomes 'completed').
  ADD COLUMN return_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER returned_by_borrower;
