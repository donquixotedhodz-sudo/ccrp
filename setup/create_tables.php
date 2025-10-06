<?php
// One-time setup script: create database, tables, and seed test users
require_once __DIR__ . '/../config.php';

function pdo_root(): PDO {
  $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
  return new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

try {
  $root = pdo_root();
  $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $root->exec("USE `" . DB_NAME . "`");

  $root->exec(<<<SQL
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

  $root->exec(<<<SQL
CREATE TABLE IF NOT EXISTS registrars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

  // Admins table for registrar authentication
  $root->exec(<<<SQL
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

  // Ensure username column exists in case table was created earlier without it
  try {
    $root->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS username VARCHAR(64) NOT NULL UNIQUE AFTER id");
  } catch (Throwable $e) {
    // Older MySQL versions don't support IF NOT EXISTS; try plain add and ignore errors if exists
    try { $root->exec("ALTER TABLE admins ADD COLUMN username VARCHAR(64) NOT NULL UNIQUE AFTER id"); } catch (Throwable $ignored) {}
  }

  // Seed test accounts if not existing
  $studentEmail = 'student@example.com';
  $registrarEmail = 'registrar@example.com';

  $checkStudent = $root->prepare('SELECT id FROM students WHERE email = :email');
  $checkStudent->execute([':email' => $studentEmail]);
  if (!$checkStudent->fetch()) {
    $stmt = $root->prepare('INSERT INTO students (email, full_name, password_hash) VALUES (:email, :name, :hash)');
    $stmt->execute([
      ':email' => $studentEmail,
      ':name' => 'Test Student',
      ':hash' => password_hash('student123', PASSWORD_DEFAULT),
    ]);
  }

  $checkRegistrar = $root->prepare('SELECT id FROM registrars WHERE email = :email');
  $checkRegistrar->execute([':email' => $registrarEmail]);
  if (!$checkRegistrar->fetch()) {
    $stmt = $root->prepare('INSERT INTO registrars (email, full_name, password_hash) VALUES (:email, :name, :hash)');
    $stmt->execute([
      ':email' => $registrarEmail,
      ':name' => 'Test Registrar',
      ':hash' => password_hash('registrar123', PASSWORD_DEFAULT),
    ]);
  }

  // Seed admin (for registrar login) if not existing
  $checkAdmin = $root->prepare('SELECT id FROM admins WHERE username = :username');
  $checkAdmin->execute([':username' => 'admin']);
  if (!$checkAdmin->fetch()) {
    $stmt = $root->prepare('INSERT INTO admins (username, email, full_name, password_hash) VALUES (:username, :email, :name, :hash)');
    $stmt->execute([
      ':username' => 'admin',
      ':email' => 'admin@example.com',
      ':name' => 'System Admin',
      ':hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ]);
  }

  // Requests table
  $root->exec(<<<SQL
CREATE TABLE IF NOT EXISTS clarcrequest (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  request_type ENUM('tor','certificate_of_grades','diploma') NOT NULL,
  status ENUM('pending','in_progress','approved','denied','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_clarcrequest_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

  // Notifications table for student alerts
  $root->exec(<<<SQL
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  request_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_notifications_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notifications_request FOREIGN KEY (request_id) REFERENCES clarcrequest(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

  echo "Setup complete. Database and tables are ready.\n";
  echo "Seeded accounts:\n";
  echo " - Student: student@example.com / student123\n";
  echo " - Admin: username 'admin' / password 'admin123'\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Setup failed: ' . $e->getMessage();
}