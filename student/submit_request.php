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

// Collect multi-select request types and per-type copies
$selected_types = isset($_POST['request_types']) && is_array($_POST['request_types']) ? array_values(array_unique(array_map('strval', $_POST['request_types']))) : [];
$copies = isset($_POST['copies']) && is_array($_POST['copies']) ? $_POST['copies'] : [];

// Collect applicant and academic fields
$fields = [
  'last_name' => trim($_POST['last_name'] ?? ''),
  'first_name' => trim($_POST['first_name'] ?? ''),
  'middle_name' => trim($_POST['middle_name'] ?? ''),
  'student_number' => trim($_POST['student_number'] ?? ''),
  'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
  'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
  'parent_guardian' => trim($_POST['parent_guardian'] ?? ''),
  'date_of_application' => trim($_POST['date_of_application'] ?? ''),
  'permanent_address' => trim($_POST['permanent_address'] ?? ''),
  'course_major' => trim($_POST['course_major'] ?? ''),
  'student_type' => trim($_POST['student_type'] ?? ''),
  'semester_year_admitted' => trim($_POST['semester_year_admitted'] ?? ''),
  'last_term_enrolled' => trim($_POST['last_term_enrolled'] ?? ''),
  'classification' => trim($_POST['classification'] ?? ''),
  'elementary_school' => trim($_POST['elementary_school'] ?? ''),
  'elementary_degree' => trim($_POST['elementary_degree'] ?? ''),
  'elementary_grad_date' => trim($_POST['elementary_grad_date'] ?? ''),
  'high_school' => trim($_POST['high_school'] ?? ''),
  'high_school_degree' => trim($_POST['high_school_degree'] ?? ''),
  'high_school_grad_date' => trim($_POST['high_school_grad_date'] ?? ''),
  'senior_high' => trim($_POST['senior_high'] ?? ''),
  'senior_high_degree' => trim($_POST['senior_high_degree'] ?? ''),
  'senior_high_grad_date' => trim($_POST['senior_high_grad_date'] ?? ''),
  'college' => trim($_POST['college'] ?? ''),
  'college_degree' => trim($_POST['college_degree'] ?? ''),
  'college_grad_date' => trim($_POST['college_grad_date'] ?? ''),
  'purpose' => trim($_POST['purpose'] ?? ''),
];

$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

// Validate request types against database-driven list (accept all codes present)
$allowed = [];
try {
  $pdo = get_db();
  $q = $pdo->query('SELECT code FROM request_types');
  $rows = $q->fetchAll();
  if (is_array($rows) && count($rows) > 0) {
    $allowed = array_map(function($r){ return (string)$r['code']; }, $rows);
  }
} catch (Throwable $e) {
  // If DB lookup fails, allow the request to proceed using submitted types
  $allowed = $selected_types;
}

// Require at least one request type
if (empty($selected_types)) {
  header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Please select at least one credential type.')));
  exit;
}

// Validate required applicant fields
$required_keys = [
  'last_name','first_name','middle_name','student_number','date_of_birth','place_of_birth','parent_guardian','date_of_application',
  'permanent_address','course_major','student_type','semester_year_admitted','last_term_enrolled','classification',
  'elementary_school','elementary_degree','elementary_grad_date',
  'high_school','high_school_degree','high_school_grad_date',
  'senior_high','senior_high_degree','senior_high_grad_date',
  'college','college_degree','college_grad_date','purpose',
];
foreach ($required_keys as $k) {
  if ($fields[$k] === '') {
    header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Please fill all required fields.')));
    exit;
  }
}

// Ensure each selected type is allowed
foreach ($selected_types as $t) {
  if (!in_array($t, $allowed, true)) {
    header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Invalid request type selected.')));
    exit;
  }
}

try {
  // $pdo may already be set above; get if not
  if (!isset($pdo)) { $pdo = get_db(); }

  // Prepare inserts for clarcrequest and details/education
  $stmt = $pdo->prepare('INSERT INTO clarcrequest (student_id, request_type, copies, status, notes) VALUES (:sid, :type, :copies, :status, :notes)');
  $detailsStmt = $pdo->prepare('INSERT INTO clarcrequest_details (request_id, last_name, first_name, middle_name, student_number, date_of_birth, place_of_birth, parent_guardian, date_of_application, permanent_address, course_major, student_type, semester_year_admitted, last_term_enrolled, classification, purpose) VALUES (:rid, :last_name, :first_name, :middle_name, :student_number, :date_of_birth, :place_of_birth, :parent_guardian, :date_of_application, :permanent_address, :course_major, :student_type, :semester_year_admitted, :last_term_enrolled, :classification, :purpose)');
  $eduStmt = $pdo->prepare('INSERT INTO clarcrequest_education (request_id, level, school, degree, graduation_date) VALUES (:rid, :level, :school, :degree, :grad_date)');

  $inserted = 0;
  foreach ($selected_types as $t) {
    // copies per type (default 1)
    $count = isset($copies[$t]) ? (int)$copies[$t] : 1;
    if ($count < 1) { $count = 1; }

    // Build details payload to store in notes as JSON
    $payload = $fields;
    $payload['request_type'] = $t;
    $payload['copies'] = $count;
    if ($notes !== null && $notes !== '') { $payload['notes'] = $notes; }

    $stmt->execute([
      ':sid' => $_SESSION['user_id'],
      ':type' => $t,
      ':copies' => $count,
      ':status' => 'pending',
      ':notes' => $notes !== null && $notes !== '' ? $notes : null,
    ]);
    $rid = (int)$pdo->lastInsertId();

    // Insert details
    $detailsStmt->execute([
      ':rid' => $rid,
      ':last_name' => $fields['last_name'],
      ':first_name' => $fields['first_name'],
      ':middle_name' => $fields['middle_name'],
      ':student_number' => $fields['student_number'],
      ':date_of_birth' => $fields['date_of_birth'],
      ':place_of_birth' => $fields['place_of_birth'],
      ':parent_guardian' => $fields['parent_guardian'],
      ':date_of_application' => $fields['date_of_application'],
      ':permanent_address' => $fields['permanent_address'],
      ':course_major' => $fields['course_major'],
      ':student_type' => $fields['student_type'],
      ':semester_year_admitted' => $fields['semester_year_admitted'],
      ':last_term_enrolled' => $fields['last_term_enrolled'],
      ':classification' => $fields['classification'],
      ':purpose' => $fields['purpose'],
    ]);

    // Insert educational background levels
    $eduLevels = [
      ['level' => 'elementary', 'school' => $fields['elementary_school'], 'degree' => $fields['elementary_degree'], 'grad' => $fields['elementary_grad_date']],
      ['level' => 'high_school', 'school' => $fields['high_school'], 'degree' => $fields['high_school_degree'], 'grad' => $fields['high_school_grad_date']],
      ['level' => 'senior_high', 'school' => $fields['senior_high'], 'degree' => $fields['senior_high_degree'], 'grad' => $fields['senior_high_grad_date']],
      ['level' => 'college', 'school' => $fields['college'], 'degree' => $fields['college_degree'], 'grad' => $fields['college_grad_date']],
    ];
    foreach ($eduLevels as $row) {
      $eduStmt->execute([
        ':rid' => $rid,
        ':level' => $row['level'],
        ':school' => $row['school'],
        ':degree' => $row['degree'],
        ':grad_date' => $row['grad'],
      ]);
    }
    $inserted++;
  }

  $msg = $inserted > 1 ? "Submitted {$inserted} requests successfully." : 'Request submitted successfully.';
  header('Location: ' . to_url('/student/dashboard.php?success=' . urlencode($msg)));
  exit;
} catch (Throwable $e) {
  header('Location: ' . to_url('/student/dashboard.php?error=' . urlencode('Failed to submit request.')));
  exit;
}