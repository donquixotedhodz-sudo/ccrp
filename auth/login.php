<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

function scheme_host(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
  return $scheme . '://' . $host;
}

function base_path(): string {
  // e.g., '/auth/login.php' -> '' (root)
  // e.g., '/ccrp/auth/login.php' -> '/ccrp'
  $script = $_SERVER['SCRIPT_NAME'] ?? '/auth/login.php';
  $dir = dirname($script);            // '/auth'
  $base = dirname($dir);              // '' or '/ccrp'
  if ($base === '.' || $base === DIRECTORY_SEPARATOR) {
    return '';
  }
  return rtrim($base, '/');
}

function to_url(string $path): string {
  return scheme_host() . base_path() . $path;
}

// Basic input validation
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Centralized input: identifier can be email or username
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
$password = $_POST['password'] ?? '';

if ($identifier === '') {
  $identifier = $username !== '' ? $username : $email;
}

if ($identifier === '' || $password === '') {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Identifier and password are required.')));
  exit;
}
try {
  $pdo = get_db();
  $role = null;
  $user = null;

  $isEmail = (strpos($identifier, '@') !== false);

  if (!$isEmail) {
    // Try admin by username
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, password_hash FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $identifier]);
    $candidate = $stmt->fetch();
    if ($candidate && password_verify($password, $candidate['password_hash'])) {
      $user = $candidate;
      $role = 'admin';
    }
  }

  if ($isEmail && !$user) {
    // Try registrar by email first
    $stmt = $pdo->prepare("SELECT id, email, full_name, password_hash FROM registrars WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $identifier]);
    $candidate = $stmt->fetch();
    if ($candidate && password_verify($password, $candidate['password_hash'])) {
      $user = $candidate;
      $role = 'registrar';
    }
  }

  if ($isEmail && !$user) {
    // Fallback to student by email
    $stmt = $pdo->prepare("SELECT id, email, full_name, password_hash FROM students WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $identifier]);
    $candidate = $stmt->fetch();
    if ($candidate && password_verify($password, $candidate['password_hash'])) {
      $user = $candidate;
      $role = 'student';
    }
  }

  if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: ' . to_url('/index.php?error=' . urlencode('Invalid email or password.')));
    exit;
  }

  // Success: set session and redirect to role dashboard
  session_regenerate_id(true);
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['user_email'] = $user['email'] ?? null;
  $_SESSION['user_name'] = $user['full_name'];
  $_SESSION['user_role'] = $role;

  if ($role === 'student') {
    $redirect = '/student/dashboard.php';
  } elseif ($role === 'registrar') {
    $redirect = '/registrar/dashboard.php';
  } else {
    $redirect = '/admin/dashboard.php';
  }
  header('Location: ' . to_url($redirect));
  exit;
} catch (Throwable $e) {
  header('Location: ' . to_url('/index.php?error=' . urlencode('Server error occurred.')));
  exit;
}