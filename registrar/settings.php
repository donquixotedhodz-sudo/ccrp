<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'registrar') {
  header('Location: /index.php?error=' . urlencode('Please login as Registrar.'));
  exit;
}
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$flash_success = $_GET['success'] ?? null;
$flash_error = $_GET['error'] ?? null;

// Handle display name update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name'] ?? '');
  if ($name === '') {
    header('Location: settings.php?error=' . urlencode('Name cannot be empty.'));
    exit;
  }
  try {
    $stmt = $pdo->prepare('UPDATE registrars SET full_name = :name WHERE id = :id');
    $stmt->execute([':name' => $name, ':id' => $_SESSION['user_id']]);
    $_SESSION['user_name'] = $name;
    header('Location: settings.php?success=' . urlencode('Profile updated.'));
    exit;
  } catch (Throwable $e) {
    header('Location: settings.php?error=' . urlencode('Update failed.'));
    exit;
  }
}

// Fetch current profile
$profile = ['full_name' => $_SESSION['user_name'] ?? 'Registrar', 'email' => $_SESSION['user_email'] ?? ''];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar • Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-white text-xs font-bold">RG</span>
          <span class="text-sm font-semibold text-slate-800">Registrar</span>
        </div>
        <div class="text-sm text-slate-600">
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Registrar') ?>
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
            <li><a href="requests.php" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Requests</a></li>
            <li><a href="settings.php" class="block rounded px-3 py-2 text-sm text-slate-700 bg-slate-100">Settings</a></li>
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
          <h1 class="text-lg font-semibold text-slate-800">Profile Settings</h1>
          <form method="post" class="mt-4 space-y-4 max-w-md">
            <div>
              <label class="block text-sm font-medium text-slate-700">Full Name</label>
              <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Email</label>
              <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled class="mt-1 block w-full rounded-md border-slate-200 bg-slate-50 text-slate-500">
            </div>
            <button type="submit" class="inline-flex justify-center rounded-md bg-slate-800 px-4 py-2 text-white text-sm font-medium hover:bg-slate-900">Save Changes</button>
          </form>
        </div>
      </main>
    </div>
  </body>
  </html>