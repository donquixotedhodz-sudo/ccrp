<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
  header('Location: /index.php?error=' . urlencode('Please login as Student.'));
  exit;
}

require_once __DIR__ . '/../includes/db.php';

function scheme_host(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
  return $scheme . '://' . $host;
}
function base_path(): string {
  $script = $_SERVER['SCRIPT_NAME'] ?? '/student/mark_notifications.php';
  $base = dirname(dirname($script));
  if ($base === '.' || $base === DIRECTORY_SEPARATOR) { return ''; }
  return rtrim($base, '/');
}
function to_url(string $path): string { return scheme_host() . base_path() . $path; }

try {
  $pdo = get_db();
  $stmt = $pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE student_id = :sid AND read_at IS NULL');
  $stmt->execute([':sid' => $_SESSION['user_id']]);
  header('Location: ' . to_url('/student/dashboard.php?success=' . urlencode('All notifications marked as read.')));
  exit;
} catch (Throwable $e) {
  header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Failed to mark notifications as read.')));
  exit;
}
?>