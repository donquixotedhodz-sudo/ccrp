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
  // Determine app base (supports subfolder deployments like /ccrp)
  $script = $_SERVER['SCRIPT_NAME'] ?? '/student/submit_request.php';
  $base = dirname(dirname($script)); // '' or '/ccrp'
  if ($base === '.' || $base === DIRECTORY_SEPARATOR) { return ''; }
  return rtrim($base, '/');
}
function to_url(string $path): string { return scheme_host() . base_path() . $path; }

$type = $_POST['request_type'] ?? '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

$allowed = ['tor','certificate_of_grades','diploma'];
if (!in_array($type, $allowed, true)) {
  header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Invalid request type.')));
  exit;
}

try {
  $pdo = get_db();
  $stmt = $pdo->prepare('INSERT INTO clarcrequest (student_id, request_type, status, notes) VALUES (:sid, :type, :status, :notes)');
  $stmt->execute([
    ':sid' => $_SESSION['user_id'],
    ':type' => $type,
    ':status' => 'pending',
    ':notes' => $notes !== '' ? $notes : null,
  ]);
  header('Location: ' . to_url('/student/dashboard.php?success=' . urlencode('Request submitted successfully.')));
  exit;
} catch (Throwable $e) {
  header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Failed to submit request.')));
  exit;
}