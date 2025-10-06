<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: /index.php?error=' . urlencode('Please login as Admin.'));
  exit;
}
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$flash_success = $_GET['success'] ?? null;
$flash_error = $_GET['error'] ?? null;

// Fetch all student requests joined with student info
$rows = [];
try {
  $stmt = $pdo->query('SELECT c.id, c.request_type, c.status, c.submitted_at, c.updated_at, c.notes, s.full_name AS student_name, s.email AS student_email FROM clarcrequest c JOIN students s ON s.id = c.student_id ORDER BY c.submitted_at DESC');
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $flash_error = 'Failed to load requests.';
}

function status_badge_class(string $s): string {
  switch ($s) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'in_progress': return 'bg-blue-100 text-blue-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'denied': return 'bg-red-100 text-red-800';
    case 'fulfilled': return 'bg-emerald-100 text-emerald-800';
    case 'cancelled': return 'bg-gray-100 text-gray-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin • Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-white text-xs font-bold">AD</span>
          <span class="text-sm font-semibold text-slate-800">Admin</span>
        </div>
        <div class="text-sm text-slate-600">
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
          <span class="mx-2 text-slate-300">|</span>
          <a href="../logout.php" class="text-indigo-700 hover:text-indigo-900">Logout</a>
        </div>
      </div>
    </header>

    <!-- Layout -->
    <div class="max-w-6xl mx-auto px-4 py-6 grid grid-cols-1 md:grid-cols-12 gap-6">
      <!-- Sidebar -->
      <aside class="md:col-span-3">
        <nav class="bg-white rounded-lg shadow-sm p-3 sticky top-4">
          <p class="px-2 pb-2 text-xs font-semibold text-slate-500">Menu</p>
          <ul class="space-y-1">
            <li><a href="dashboard.php" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Dashboard</a></li>
            <li><a href="requests.php" class="block rounded px-3 py-2 text-sm text-slate-700 bg-slate-100">Requests</a></li>
            <li><a href="settings.php" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Settings</a></li>
          </ul>
        </nav>
      </aside>

      <!-- Main content -->
      <main class="md:col-span-9 space-y-6">
        <?php if ($flash_success): ?>
          <div class="rounded-md bg-green-50 p-3 text-sm text-green-700"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
          <div class="rounded-md bg-red-50 p-3 text-sm text-red-700"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm p-4">
          <h1 class="text-lg font-semibold text-slate-800">Student Requests</h1>
          <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-slate-600">
                  <th class="py-2 pr-4">ID</th>
                  <th class="py-2 pr-4">Student</th>
                  <th class="py-2 pr-4">Email</th>
                  <th class="py-2 pr-4">Type</th>
                  <th class="py-2 pr-4">Status</th>
                  <th class="py-2 pr-4">Submitted</th>
                  <th class="py-2 pr-4">Updated</th>
                  <th class="py-2 pr-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr class="border-t">
                    <td class="py-2 pr-4"><?= htmlspecialchars($r['id']) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($r['student_name']) ?></td>
                    <td class="py-2 pr-4 text-slate-600"><?= htmlspecialchars($r['student_email']) ?></td>
                    <td class="py-2 pr-4 capitalize"><?= htmlspecialchars(str_replace('_',' ', $r['request_type'])) ?></td>
                    <td class="py-2 pr-4">
                      <?php $cls = status_badge_class($r['status']); ?>
                      <span class="inline-block rounded px-2 py-0.5 text-xs font-medium <?= $cls ?>">
                        <?= htmlspecialchars(str_replace('_',' ', $r['status'])) ?>
                      </span>
                    </td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($r['submitted_at']) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($r['updated_at'] ?? '-') ?></td>
                    <td class="py-2 pr-4">
                      <form action="../registrar/request_update.php" method="post" class="flex flex-wrap gap-2 items-center">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                        <select name="action" class="rounded-md border-slate-300 text-xs p-1">
                          <option value="in_progress">Mark In Progress</option>
                          <option value="approved">Approve</option>
                          <option value="denied">Deny</option>
                          <option value="fulfilled">Fulfill</option>
                          <option value="cancelled">Cancel</option>
                        </select>
                        <button type="submit" class="inline-flex justify-center rounded-md bg-slate-800 px-3 py-1.5 text-white text-xs font-medium hover:bg-slate-900">Apply</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                  <tr><td colspan="8" class="py-3 text-center text-slate-600">No requests found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>