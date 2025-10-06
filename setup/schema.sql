-- CCRP (Credentials Request Portal) Database Schema
-- MySQL 8+, InnoDB, UTF8MB4
-- Import via: mysql -u root -p < setup/schema.sql

CREATE DATABASE IF NOT EXISTS `ccrp_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ccrp_db`;

-- Students table (separate from registrars)
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `full_name` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrars table
CREATE TABLE IF NOT EXISTS `registrars` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `full_name` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types of credential requests
CREATE TABLE IF NOT EXISTS `request_types` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(64) NOT NULL UNIQUE,
  `name` VARCHAR(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `request_types` (`code`, `name`) VALUES
  ('diploma', 'Diploma'),
  ('tor', 'Transcript of Records (TOR)'),
  ('certificate_of_grades', 'Certificate of Grades'),
  ('transfer_credentials', 'Transfer Credentials')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Requests made by students
CREATE TABLE IF NOT EXISTS `requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `type_id` TINYINT UNSIGNED NOT NULL,
  `status` ENUM('pending','in_progress','approved','denied','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  `reference_no` VARCHAR(64) NOT NULL UNIQUE,
  `notes` TEXT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_requests_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_requests_type` FOREIGN KEY (`type_id`) REFERENCES `request_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_requests_student_status` ON `requests` (`student_id`, `status`);

-- Registrar actions log (audit trail)
CREATE TABLE IF NOT EXISTS `request_actions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_id` BIGINT UNSIGNED NOT NULL,
  `registrar_id` INT UNSIGNED NULL,
  `action` ENUM('create','approve','deny','update_status','comment','fulfill','cancel') NOT NULL,
  `message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_actions_request` FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_actions_registrar` FOREIGN KEY (`registrar_id`) REFERENCES `registrars`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_actions_request` ON `request_actions` (`request_id`);

-- Student requests table per your naming: clarcrequest
CREATE TABLE IF NOT EXISTS `clarcrequest` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `request_type` ENUM('tor','certificate_of_grades','diploma') NOT NULL,
  `status` ENUM('pending','in_progress','approved','denied','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_clarcrequest_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_clarcrequest_student_status` ON `clarcrequest` (`student_id`, `status`);

-- Optional: seed users (password hashes should be generated via PHP)
-- Uncomment and replace the hash values if you want direct seeding via SQL.
-- INSERT INTO `students` (`email`,`full_name`,`password_hash`) VALUES
--   ('student@example.com','Test Student','<replace_with_password_hash>')
-- ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);
-- INSERT INTO `registrars` (`email`,`full_name`,`password_hash`) VALUES
--   ('registrar@example.com','Test Registrar','<replace_with_password_hash>')
-- ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- End of schema