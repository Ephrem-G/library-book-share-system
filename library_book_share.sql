-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2026 at 05:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_book_share`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(160) NOT NULL,
  `category` varchar(40) NOT NULL,
  `description` text DEFAULT NULL,
  `condition` enum('New','Good','Fair','Worn') NOT NULL DEFAULT 'Good',
  `status` enum('available','borrowed') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `owner_id`, `title`, `author`, `category`, `description`, `condition`, `status`, `created_at`) VALUES
(1, 1, 'Introduction to Algorithms', 'Thomas Cormen', 'Academic', 'A comprehensive textbook on algorithms.', 'Good', 'available', '2026-06-28 00:35:56'),
(2, 1, 'The Lean Startup', 'Eric Ries', 'Non-Fiction', 'How entrepreneurs use continuous innovation.', 'New', 'available', '2026-06-28 00:35:56'),
(3, 2, 'Things Fall Apart', 'Chinua Achebe', 'Literature', 'Classic African novel about colonial impact.', 'Good', 'available', '2026-06-28 00:35:56'),
(4, 2, 'A Brief History of Time', 'Stephen Hawking', 'Science', 'From the Big Bang to Black Holes.', 'Good', 'available', '2026-06-28 00:35:56'),
(5, 3, 'Clean Code', 'Robert C. Martin', 'Technology', 'A handbook of agile software craftsmanship.', 'Fair', 'available', '2026-06-28 00:35:56'),
(6, 3, 'Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', 'Fiction', 'A story set during the Nigerian Civil War.', 'New', 'available', '2026-06-28 00:35:56'),
(7, 2, 'kevin example', 'kevin', 'Fiction', '', 'Good', 'available', '2026-06-28 01:36:30'),
(8, 2, 'example 3', 'kevin', 'Fiction', 'an example', 'Good', 'available', '2026-07-17 09:32:31'),
(9, 2, 'example 5', 'kevin', 'Technology', 'anything', 'Fair', 'available', '2026-07-17 09:33:39'),
(10, 6, '5', 'me', 'Non-Fiction', 'trdytr', 'New', 'available', '2026-07-18 07:24:59'),
(11, 1, 'GFHCGF', 'GGFG', 'Science', 'FGXH', 'Fair', 'available', '2026-07-21 14:13:57');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_id`, `to_id`, `body`, `created_at`) VALUES
(1, 2, 1, 'erwst46', '2026-07-02 16:24:45'),
(2, 1, 2, 'fhfjgik', '2026-07-02 16:25:18'),
(3, 1, 2, 'fjk', '2026-07-02 16:50:08'),
(4, 1, 3, 'iuyoij', '2026-07-16 12:29:19'),
(5, 1, 2, 'jl;kl', '2026-07-16 12:29:26'),
(6, 1, 3, 'lkhl', '2026-07-16 12:29:32'),
(7, 1, 3, 'uity', '2026-07-16 17:15:55'),
(10, 2, 1, 'tyt54r', '2026-07-18 06:46:10'),
(11, 2, 1, 'rtyhrt', '2026-07-18 06:46:12'),
(12, 1, 2, 'I have marked the book as returned. Please confirm the return when you receive it.', '2026-07-18 06:51:59'),
(13, 2, 1, 'I have confirmed the return. The loan is now complete. Thank you!', '2026-07-18 06:52:14'),
(14, 2, 1, 'I have marked the book as returned. Please confirm the return when you receive it.', '2026-07-18 06:52:31'),
(15, 1, 2, 'I have confirmed the return. The loan is now complete. Thank you!', '2026-07-18 06:52:48'),
(16, 6, 2, 'hey kevin i was wondering if we could chat about the book', '2026-07-18 07:30:15'),
(17, 2, 6, 'hey how are you doing', '2026-07-18 07:31:27'),
(20, 6, 2, 'gfh', '2026-07-18 07:58:00'),
(21, 1, 2, 'I have marked the book as returned. Please confirm the return when you receive it.', '2026-07-18 12:21:32'),
(22, 2, 1, 'I have confirmed the return. The loan is now complete. Thank you!', '2026-07-18 12:22:17'),
(23, 2, 6, 'y5j65w7i,3eydjm', '2026-07-18 15:23:24'),
(24, 2, 1, 'WHEN WILL BE AVAILABLE', '2026-07-21 14:15:49'),
(25, 1, 2, 'I have marked the book as returned. Please confirm the return when you receive it.', '2026-07-21 14:16:41'),
(26, 2, 1, 'I have confirmed the return. The loan is now complete. Thank you!', '2026-07-21 14:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `returned_by_borrower` tinyint(1) NOT NULL DEFAULT 0,
  `return_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `book_id`, `requester_id`, `owner_id`, `status`, `due_date`, `returned_by_borrower`, `return_confirmed`, `created_at`, `updated_at`) VALUES
(1, 5, 2, 3, 'pending', NULL, 0, 0, '2026-06-28 01:36:57', '2026-06-28 01:36:57'),
(2, 7, 1, 2, 'rejected', NULL, 0, 0, '2026-06-28 01:38:03', '2026-06-28 01:38:27'),
(3, 1, 2, 1, 'completed', NULL, 0, 0, '2026-06-28 01:42:51', '2026-06-28 01:43:41'),
(4, 7, 1, 2, 'rejected', NULL, 0, 0, '2026-06-28 01:53:03', '2026-07-02 16:44:37'),
(5, 1, 2, 1, 'completed', NULL, 1, 1, '2026-06-29 09:43:42', '2026-07-18 06:52:48'),
(6, 2, 2, 1, 'completed', NULL, 0, 0, '2026-07-02 16:11:37', '2026-07-02 16:50:35'),
(7, 2, 2, 1, 'completed', NULL, 0, 0, '2026-07-03 10:24:38', '2026-07-18 06:49:26'),
(8, 7, 1, 2, 'completed', NULL, 0, 0, '2026-07-03 10:28:34', '2026-07-03 10:29:01'),
(9, 7, 1, 2, 'completed', '2026-07-17', 1, 1, '2026-07-03 10:29:26', '2026-07-18 06:52:14'),
(14, 9, 1, 2, 'completed', NULL, 0, 0, '2026-07-18 06:36:53', '2026-07-18 06:37:39'),
(15, 9, 6, 2, 'rejected', NULL, 0, 0, '2026-07-18 07:29:50', '2026-07-21 14:16:23'),
(17, 7, 1, 2, 'completed', '2026-07-17', 1, 1, '2026-07-18 12:20:23', '2026-07-18 12:22:17'),
(18, 7, 1, 2, 'pending', NULL, 0, 0, '2026-07-21 07:56:54', '2026-07-21 07:56:54'),
(19, 6, 1, 3, 'pending', NULL, 0, 0, '2026-07-21 14:14:19', '2026-07-21 14:14:19'),
(20, 9, 1, 2, 'completed', '2026-07-20', 1, 1, '2026-07-21 14:15:05', '2026-07-21 14:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `location_text` varchar(160) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(128) DEFAULT NULL,
  `verification_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `location_text`, `latitude`, `longitude`, `avatar_url`, `is_verified`, `verification_token`, `verification_sent_at`, `created_at`) VALUES
(1, 'Jane Mwangi', 'jane@example.com', '$2y$12$alD28yotMaliuPoWw/EfBuGX85Jx8jOA/S5CO7l7AswP1OWTWZW/6', '0712000001', 'Nairobi, Westlands', NULL, NULL, NULL, 1, NULL, NULL, '2026-06-28 00:35:56'),
(2, 'Kevin Otieno', 'kevin@example.com', '$2y$12$alD28yotMaliuPoWw/EfBuGX85Jx8jOA/S5CO7l7AswP1OWTWZW/6', '0712000002', 'Nairobi, thika', -1.3590000, 36.7350000, NULL, 1, NULL, NULL, '2026-06-28 00:35:56'),
(3, 'Mary Wambui', 'mary@example.com', '$2y$12$alD28yotMaliuPoWw/EfBuGX85Jx8jOA/S5CO7l7AswP1OWTWZW/6', '0712000003', 'Nairobi, Karen', -1.3190000, 36.7080000, NULL, 1, NULL, NULL, '2026-06-28 00:35:56'),
(4, 'Ephrem Gichuhi Mbico', 'ephrem01gichuhi@gmail.com', '$2y$10$6MJ2jPQJse2b6K02M6CMSuGeaUPR6.qmgAM2/EDZB.fniRLlFWSN.', '0713581385', 'Nairobi, eastlands', -1.2921000, 36.8219000, NULL, 1, NULL, '2026-06-29 11:42:35', '2026-06-28 01:56:51'),
(5, 'ken', 'ken2@example.com', '$2y$10$n2eYot1AwldvKf210.t5z.sbToFzQYRMJICcixtuWDrWQ/AaqHMG.', '12', 'nairobi, langata', -1.2921000, 36.8219000, NULL, 0, '2115eade78329cdb68c20246f26384c0a958fe326fb4fd2e50758d99067a48bc', '2026-07-03 10:23:21', '2026-07-03 10:23:21'),
(6, 'jay', 'kendi@gmail.com', '$2y$10$nsEiPYcNiaLhDHsRPDKVleE0C/HeS1A9.kW6iLo3okFiwMW4Zji7m', '0711111111', 'rongai, kajiado', -1.4033790, 36.7623580, NULL, 1, NULL, '2026-07-18 07:23:32', '2026-07-17 00:09:30'),
(7, 'ftrrshr', 'kevin343@gmail.com', '$2y$10$d2GbUxsUGqU3m8wUyM7lzuLPpr7f/g0PKcL3sBHJLS4VVAnyiSV1u', 'fdrtgrgttttt', 'nairobi, langata', -1.2921000, 36.8219000, NULL, 0, '1e1cbfd61d94c0e72b0cde7586691db435726aedd3f2ef8753f98fabd2d1909f', '2026-07-17 09:09:55', '2026-07-17 09:09:55'),
(10, 'hjhgjh', 'jane23@example.com', '$2y$10$hsoUymR4tLgCG4r/Nt5bHutSflEcwnlNLRoODe5nMyMHjI/ZgOoGK', '0745454555', 'langata', -1.2921000, 36.8219000, NULL, 0, '02799952347fd8c5c66d8820f15f388463ff9359a91c07c732341ff1d276fc04', '2026-07-18 12:23:49', '2026-07-18 12:23:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_books_owner` (`owner_id`),
  ADD KEY `idx_books_category` (`category`),
  ADD KEY `idx_books_status` (`status`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_from` (`from_id`),
  ADD KEY `idx_msg_to` (`to_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_req_book` (`book_id`),
  ADD KEY `idx_req_requester` (`requester_id`),
  ADD KEY `idx_req_owner` (`owner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_verification_token` (`verification_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `fk_books_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_from` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_to` FOREIGN KEY (`to_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_req_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
