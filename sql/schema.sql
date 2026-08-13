-- Library Book Share System - PHP + MySQL schema
-- Fresh install script for XAMPP/phpMyAdmin.

CREATE DATABASE IF NOT EXISTS library_book_share CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_book_share;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id                     INT AUTO_INCREMENT PRIMARY KEY,
  full_name              VARCHAR(120) NOT NULL,
  email                  VARCHAR(160) NOT NULL UNIQUE,
  password_hash          VARCHAR(255) NOT NULL,
  phone                  VARCHAR(30) NULL,
  location_text          VARCHAR(160) NOT NULL,
  latitude               DECIMAL(10,7) NULL,
  longitude              DECIMAL(10,7) NULL,
  avatar_url             VARCHAR(255) NULL,
  is_verified            TINYINT(1) NOT NULL DEFAULT 0,
  verification_token     VARCHAR(128) NULL,
  verification_sent_at   DATETIME NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_verification_token (verification_token)
) ENGINE=InnoDB;

CREATE TABLE books (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  owner_id     INT NOT NULL,
  title        VARCHAR(200) NOT NULL,
  author       VARCHAR(160) NOT NULL,
  category     VARCHAR(40) NOT NULL,
  description  TEXT NULL,
  `condition`  ENUM('New','Good','Fair','Worn') NOT NULL DEFAULT 'Good',
  status       ENUM('available','borrowed') NOT NULL DEFAULT 'available',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_owner
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_books_owner (owner_id),
  INDEX idx_books_category (category),
  INDEX idx_books_status (status)
) ENGINE=InnoDB;

CREATE TABLE requests (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  book_id       INT NOT NULL,
  requester_id  INT NOT NULL,
  owner_id      INT NOT NULL,
  status        ENUM('pending','approved','rejected','cancelled','completed')
                  NOT NULL DEFAULT 'pending',
  due_date              DATE NULL,          -- return/due date set by the owner on approval
  returned_by_borrower  TINYINT(1) NOT NULL DEFAULT 0,  -- borrower clicked "Mark as Returned"
  return_confirmed      TINYINT(1) NOT NULL DEFAULT 0,  -- owner clicked "Confirm Return"
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_req_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_req_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_req_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_req_book (book_id),
  INDEX idx_req_requester (requester_id),
  INDEX idx_req_owner (owner_id)
) ENGINE=InnoDB;

CREATE TABLE messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  from_id     INT NOT NULL,
  to_id       INT NOT NULL,
  body        TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_from FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_to FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_msg_from (from_id),
  INDEX idx_msg_to (to_id)
) ENGINE=InnoDB;

-- Demo password for all users: demo123
-- Hash generated with PHP password_hash().
SET @demo_hash = '$2y$12$alD28yotMaliuPoWw/EfBuGX85Jx8jOA/S5CO7l7AswP1OWTWZW/6';

INSERT INTO users
  (full_name, email, password_hash, phone, location_text, latitude, longitude, is_verified)
VALUES
  ('Jane Mwangi', 'jane@example.com', @demo_hash, '0712000001', 'Nairobi, Westlands', -1.2670, 36.8060, 1),
  ('Kevin Otieno', 'kevin@example.com', @demo_hash, '0712000002', 'Nairobi, Langata', -1.3590, 36.7350, 1),
  ('Mary Wambui', 'mary@example.com', @demo_hash, '0712000003', 'Nairobi, Karen', -1.3190, 36.7080, 1);

INSERT INTO books (owner_id, title, author, category, description, `condition`, status) VALUES
  (1, 'Introduction to Algorithms', 'Thomas Cormen', 'Academic', 'A comprehensive textbook on algorithms.', 'Good', 'available'),
  (1, 'The Lean Startup', 'Eric Ries', 'Non-Fiction', 'How entrepreneurs use continuous innovation.', 'New', 'available'),
  (2, 'Things Fall Apart', 'Chinua Achebe', 'Literature', 'Classic African novel about colonial impact.', 'Good', 'available'),
  (2, 'A Brief History of Time', 'Stephen Hawking', 'Science', 'From the Big Bang to Black Holes.', 'Good', 'available'),
  (3, 'Clean Code', 'Robert C. Martin', 'Technology', 'A handbook of agile software craftsmanship.', 'Fair', 'available'),
  (3, 'Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', 'Fiction', 'A story set during the Nigerian Civil War.', 'New', 'available');

