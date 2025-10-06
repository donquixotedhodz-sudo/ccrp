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

// Simple metrics
$counts = [
  'total' => 0,
  'pending' => 0,
  'in_progress' => 0,
  'approved' => 0,
  'fulfilled' => 0,
  'denied' => 0,
  'cancelled' => 0,
];
try {
  $rows = $pdo->query('SELECT status, COUNT(*) AS c FROM clarcrequest GROUP BY status')->fetchAll();
  $total = $pdo->query('SELECT COUNT(*) AS c FROM clarcrequest')->fetch();
  $counts['total'] = (int)($total['c'] ?? 0);
  foreach ($rows as $r) { $counts[$r['status']] = (int)$r['c']; }
} catch (Throwable $e) {
  $flash_error = 'Failed to load dashboard metrics.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin • Dashboard</title>
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
            <li><a href="dashboard.php" class="block rounded px-3 py-2 text-sm text-slate-700 bg-slate-100">Dashboard</a></li>
            <li><a href="requests.php" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Requests</a></li>
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
          <h1 class="text-lg font-semibold text-slate-800">Overview</h1>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-3">
            <?php
              $cards = [
                ['label' => 'Total', 'key' => 'total', 'color' => 'bg-slate-100 text-slate-800'],
                ['label' => 'Pending', 'key' => 'pending', 'color' => 'bg-yellow-100 text-yellow-800'],
                ['label' => 'In Progress', 'key' => 'in_progress', 'color' => 'bg-blue-100 text-blue-800'],
                ['label' => 'Approved', 'key' => 'approved', 'color' => 'bg-green-100 text-green-800'],
                ['label' => 'Fulfilled', 'key' => 'fulfilled', 'color' => 'bg-emerald-100 text-emerald-800'],
                ['label' => 'Denied', 'key' => 'denied', 'color' => 'bg-red-100 text-red-800'],
              ];
            ?>
            <?php foreach ($cards as $c): ?>
              <div class="rounded p-3 <?= $c['color'] ?>">
                <div class="text-xs font-medium"><?= htmlspecialchars($c['label']) ?></div>
                <div class="mt-1 text-2xl font-semibold"><?= htmlspecialchars((string)($counts[$c['key']] ?? 0)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>