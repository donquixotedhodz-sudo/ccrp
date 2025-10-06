<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'registrar') {
  header('Location: /index.php?error=' . urlencode('Please login as Registrar.'));
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar Dashboard</title>
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
            <li><a href="settings.php" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Settings</a></li>
          </ul>
        </nav>
      </aside>

      <!-- Main content -->
      <main class="md:col-span-9 space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <h1 class="text-lg font-semibold text-slate-800">Welcome</h1>
          <p class="mt-1 text-sm text-slate-600">Use the sidebar to review requests and adjust settings.</p>
        </div>
      </main>
    </div>
  </body>
  </html>