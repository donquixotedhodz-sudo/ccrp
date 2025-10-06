<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['registrar','admin'], true)) {
  header('Location: /index.php?error=' . urlencode('Please login as Registrar or Admin.'));
  exit;
}
require_once __DIR__ . '/../includes/db.php';

function scheme_host(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
  return $scheme . '://' . $host;
}
function base_path(): string {
  $script = $_SERVER['SCRIPT_NAME'] ?? '/registrar/request_update.php';
  $base = dirname(dirname($script)); // '' or '/ccrp'
  if ($base === '.' || $base === DIRECTORY_SEPARATOR) { return ''; }
  return rtrim($base, '/');
}
function to_url(string $path): string { return scheme_host() . base_path() . $path; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$allowed = ['in_progress','approved','denied','fulfilled','cancelled'];
if ($id <= 0 || !in_array($action, $allowed, true)) {
  header('Location: ' . to_url('/registrar/requests.php?error=' . urlencode('Invalid request or action.')));
  exit;
}

try {
  $pdo = get_db();
  // Update status
  $stmt = $pdo->prepare('UPDATE clarcrequest SET status = :status WHERE id = :id');
  $stmt->execute([':status' => $action, ':id' => $id]);

  // If fulfilled, create a notification for the student
  if ($action === 'fulfilled') {
    // Fetch student_id and request details
    $get = $pdo->prepare('SELECT student_id, request_type FROM clarcrequest WHERE id = :id LIMIT 1');
    $get->execute([':id' => $id]);
    $req = $get->fetch();
    if ($req && isset($req['student_id'])) {
      $studentId = (int)$req['student_id'];
      $type = (string)($req['request_type'] ?? 'request');
      $typeLabel = str_replace('_', ' ', $type);
      $message = "Your {$typeLabel} request (#{$id}) is fulfilled and ready to be claimed.";

      // Insert notification (ignore if table missing)
      try {
        $ins = $pdo->prepare('INSERT INTO notifications (student_id, request_id, message) VALUES (:sid, :rid, :msg)');
        $ins->execute([':sid' => $studentId, ':rid' => $id, ':msg' => $message]);
      } catch (Throwable $e) {
        // Skip silently if notifications table not present
      }
    }
  }
  $dest = ($_SESSION['user_role'] === 'admin') ? '/admin/requests.php' : '/registrar/requests.php';
  header('Location: ' . to_url($dest . '?success=' . urlencode('Status updated.')));
  exit;
} catch (Throwable $e) {
  $dest = ($_SESSION['user_role'] === 'admin') ? '/admin/requests.php' : '/registrar/requests.php';
  header('Location: ' . to_url($dest . '?error=' . urlencode('Update failed.')));
  exit;
}