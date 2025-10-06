-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 06:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ccrp_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `full_name`, `password_hash`, `created_at`) VALUES
(2, 'admin', 'admin@example.com', 'System Admin', '$2y$10$lagrUb9kiWLisrmXx5c0aeKmNTkg.jjd6ESz4klwPBQn0xY8b6o5K', '2025-10-06 04:34:35');

-- --------------------------------------------------------

--
-- Table structure for table `clarcrequest`
--

CREATE TABLE `clarcrequest` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `request_type` enum('tor','certificate_of_grades','diploma') NOT NULL,
  `status` enum('pending','in_progress','approved','denied','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clarcrequest`
--

INSERT INTO `clarcrequest` (`id`, `student_id`, `request_type`, `status`, `notes`, `submitted_at`, `updated_at`) VALUES
(1, 1, 'certificate_of_grades', 'fulfilled', NULL, '2025-10-06 03:15:56', '2025-10-06 03:54:11'),
(2, 1, 'diploma', 'approved', NULL, '2025-10-06 03:16:45', '2025-10-06 04:05:03'),
(3, 1, 'diploma', 'pending', NULL, '2025-10-06 03:28:15', NULL),
(4, 1, 'diploma', 'pending', NULL, '2025-10-06 03:28:36', NULL),
(5, 1, 'tor', 'pending', NULL, '2025-10-06 03:36:31', NULL),
(6, 2, 'certificate_of_grades', 'fulfilled', NULL, '2025-10-06 04:06:00', '2025-10-06 04:06:12'),
(7, 2, 'diploma', 'fulfilled', NULL, '2025-10-06 04:22:33', '2025-10-06 04:23:06'),
(8, 2, 'certificate_of_grades', 'fulfilled', NULL, '2025-10-06 04:23:26', '2025-10-06 04:23:32');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `request_id`, `message`, `created_at`, `read_at`) VALUES
(1, 2, 7, 'Your diploma request (#7) is fulfilled and ready to be claimed.', '2025-10-06 04:23:06', NULL),
(2, 2, 8, 'Your certificate of grades request (#8) is fulfilled and ready to be claimed.', '2025-10-06 04:23:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registrars`
--

CREATE TABLE `registrars` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrars`
--

INSERT INTO `registrars` (`id`, `email`, `full_name`, `password_hash`, `created_at`) VALUES
(1, 'registrar@example.com', 'Test Registrar', '$2y$10$wWjH6Ftb0W6LgqeWB2P86euo26XGzEdx8A0TbPvwNxUIK9pY.mGY6', '2025-10-06 03:08:24');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `type_id` tinyint(3) UNSIGNED NOT NULL,
  `status` enum('pending','in_progress','approved','denied','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  `reference_no` varchar(64) NOT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_actions`
--

CREATE TABLE `request_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `registrar_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('create','approve','deny','update_status','comment','fulfill','cancel') NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_types`
--

CREATE TABLE `request_types` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `request_types`
--

INSERT INTO `request_types` (`id`, `code`, `name`) VALUES
(1, 'diploma', 'Diploma'),
(2, 'tor', 'Transcript of Records (TOR)'),
(3, 'certificate_of_grades', 'Certificate of Grades'),
(4, 'transfer_credentials', 'Transfer Credentials');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `email`, `full_name`, `password_hash`, `created_at`) VALUES
(1, 'student@example.com', 'Test Student', '$2y$10$TR5wZ8lBXAZcR0R3nIw1aOudl6tAykHXAEn5rsH8D0dMM3xF.iA8C', '2025-10-06 03:08:24'),
(2, 'joshmcdowelltrapal@gmail.com', 'Josh McDowell Trapal', '$2y$10$n7ChrOGEtbUQGrwetHkDU.3.7u2N7b3Ag94z96AI8alHkgHfb5z9S', '2025-10-06 03:53:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `clarcrequest`
--
ALTER TABLE `clarcrequest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clarcrequest_student_status` (`student_id`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_student` (`student_id`),
  ADD KEY `fk_notifications_request` (`request_id`);

--
-- Indexes for table `registrars`
--
ALTER TABLE `registrars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `fk_requests_type` (`type_id`),
  ADD KEY `idx_requests_student_status` (`student_id`,`status`);

--
-- Indexes for table `request_actions`
--
ALTER TABLE `request_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_actions_registrar` (`registrar_id`),
  ADD KEY `idx_actions_request` (`request_id`);

--
-- Indexes for table `request_types`
--
ALTER TABLE `request_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clarcrequest`
--
ALTER TABLE `clarcrequest`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `registrars`
--
ALTER TABLE `registrars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_actions`
--
ALTER TABLE `request_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_types`
--
ALTER TABLE `request_types`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clarcrequest`
--
ALTER TABLE `clarcrequest`
  ADD CONSTRAINT `fk_clarcrequest_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_request` FOREIGN KEY (`request_id`) REFERENCES `clarcrequest` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_requests_type` FOREIGN KEY (`type_id`) REFERENCES `request_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `request_actions`
--
ALTER TABLE `request_actions`
  ADD CONSTRAINT `fk_actions_registrar` FOREIGN KEY (`registrar_id`) REFERENCES `registrars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_actions_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
