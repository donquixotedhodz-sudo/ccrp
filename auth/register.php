<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

function scheme_host(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
  return $scheme . '://' . $host;
}
function base_path(): string {
  // e.g., '/auth/register.php' -> '' (root)
  // e.g., '/ccrp/auth/register.php' -> '/ccrp'
  $script = $_SERVER['SCRIPT_NAME'] ?? '/auth/register.php';
  $dir = dirname($script);            // '/auth'
  $base = dirname($dir);              // '' or '/ccrp'
  if ($base === '.' || $base === DIRECTORY_SEPARATOR) {
    return '';
  }
  return rtrim($base, '/');
}
function to_url(string $path): string { return scheme_host() . base_path() . $path; }

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Basic validation
if ($email === '' || $full_name === '' || $password === '' || $confirm === '') {
  header('Location: ' . to_url('/index.php?error=' . urlencode('All fields are required.')));
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Invalid email address.')));
  exit;
}
if ($password !== $confirm) {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Passwords do not match.')));
  exit;
}
if (strlen($password) < 8) {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Password must be at least 8 characters.')));
  exit;
}

try {
  $pdo = get_db();
  // Check for existing account
  $check = $pdo->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
  $check->execute([':email' => $email]);
  if ($check->fetch()) {
    header('Location: ' . to_url('/index.php?error=' . urlencode('Email is already registered.')));
    exit;
  }

  // Create account
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO students (email, full_name, password_hash) VALUES (:email, :name, :hash)');
  $stmt->execute([':email' => $email, ':name' => $full_name, ':hash' => $hash]);

  header('Location: ' . to_url('/index.php?success=' . urlencode('Registration successful. Please login.')));
  exit;
} catch (Throwable $e) {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Registration failed. Please try again.')));
  exit;
}
