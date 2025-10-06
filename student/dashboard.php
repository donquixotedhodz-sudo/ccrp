<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
  header('Location: /index.php?error=' . urlencode('Please login as Student.'));
  exit;
}
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$flash_success = $_GET['success'] ?? null;
$flash_error = $_GET['error'] ?? null;

// Fetch existing requests for this student
$requests = [];
try {
  $stmt = $pdo->prepare('SELECT id, request_type, status, submitted_at, updated_at, notes FROM clarcrequest WHERE student_id = :sid ORDER BY submitted_at DESC');
  $stmt->execute([':sid' => $_SESSION['user_id']]);
  $requests = $stmt->fetchAll();
} catch (Throwable $e) {
  $flash_error = 'Failed to load requests.';
}

// Fetch unread notifications for this student
$notifications = [];
$unread_count = 0;
try {
  $ns = $pdo->prepare('SELECT id, message, created_at FROM notifications WHERE student_id = :sid AND read_at IS NULL ORDER BY created_at DESC LIMIT 20');
  $ns->execute([':sid' => $_SESSION['user_id']]);
  $notifications = $ns->fetchAll();
  $unread_count = is_array($notifications) ? count($notifications) : 0;
} catch (Throwable $e) {
  // ignore notification load errors silently
}

// Helper: map status to Tailwind badge classes
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
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-indigo-600 text-white text-xs font-bold">CR</span>
          <span class="text-sm font-semibold text-slate-800">Credentials Request • Student</span>
        </div>
        <div class="text-sm text-slate-600 relative">
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
          <span class="mx-2 text-slate-300">|</span>
          <button id="notif-btn" type="button" class="relative inline-flex items-center justify-center rounded-full p-1 hover:bg-slate-100" aria-label="Notifications">
            <!-- Bell icon (Heroicons) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5 text-slate-700">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.657A9 9 0 0 0 18 10V9a6 6 0 1 0-12 0v1a9 9 0 0 0 3.143 7.657m5.714 0A3 3 0 0 1 12 20.25a3 3 0 0 1-2.857-2.593m5.714 0H9.143" />
            </svg>
            <?php if ($unread_count > 0): ?>
              <span class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-full bg-red-600 text-white text-[10px] px-1.5 py-0.5">
                <?= htmlspecialchars((string)$unread_count) ?>
              </span>
            <?php endif; ?>
          </button>
          <a href="../logout.php" class="ml-2 text-indigo-700 hover:text-indigo-900">Logout</a>

          <!-- Notifications dropdown -->
          <div id="notif-panel" class="hidden absolute right-0 mt-2 w-80 rounded-lg border border-slate-200 bg-white shadow-lg">
            <div class="p-3 border-b border-slate-200">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-800">Notifications</span>
                <form action="mark_notifications.php" method="post">
                  <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all as read</button>
                </form>
              </div>
            </div>
            <div class="max-h-64 overflow-y-auto">
              <?php if (empty($notifications)): ?>
                <p class="p-3 text-sm text-slate-600">No new notifications.</p>
              <?php else: ?>
                <ul class="divide-y divide-slate-200">
                  <?php foreach ($notifications as $n): ?>
                    <li class="p-3">
                      <p class="text-sm text-slate-800"><?= htmlspecialchars($n['message']) ?></p>
                      <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($n['created_at']) ?></p>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
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
            <li>
              <a href="#" data-target="request-form" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Request Form</a>
            </li>
            <li>
              <a href="#" data-target="request-table" class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Request Table</a>
            </li>
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

        <!-- Shared Container for Sections (ensures same position/layout) -->
        <div class="bg-white rounded-lg shadow-sm p-4">
          <!-- Request Form Section -->
          <section id="request-form" class="js-section">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">Request a Credential</h2>
            <form action="submit_request.php" method="post" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-700">Credential Type</label>
                <select name="request_type" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="tor">Transcript of Records (TOR)</option>
                  <option value="certificate_of_grades">Certificate of Grades</option>
                  <option value="diploma">Diploma</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Notes (optional)</label>
                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Any additional details..."></textarea>
              </div>
              <button type="submit" class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Submit Request</button>
            </form>
          </section>

          <!-- Request Table Section -->
          <section id="request-table" class="js-section hidden">
            <h2 class="text-sm font-semibold text-slate-800 mb-4">My Requests</h2>
            <?php if (empty($requests)): ?>
              <p class="mt-2 text-sm text-slate-600">No requests yet. <a href="#" data-target="request-form" class="text-indigo-600 hover:text-indigo-500">Make your first request</a>.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left text-slate-600 border-b">
                      <th class="py-3 pr-4 font-medium">ID</th>
                      <th class="py-3 pr-4 font-medium">Type</th>
                      <th class="py-3 pr-4 font-medium">Status</th>
                      <th class="py-3 pr-4 font-medium">Notes</th>
                      <th class="py-3 pr-4 font-medium">Submitted</th>
                      <th class="py-3 pr-4 font-medium">Updated</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    <?php foreach ($requests as $r): ?>
                      <tr class="hover:bg-slate-50">
                        <td class="py-3 pr-4 font-mono text-xs"><?= htmlspecialchars($r['id']) ?></td>
                        <td class="py-3 pr-4 capitalize">
                          <?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['request_type']))) ?>
                        </td>
                        <td class="py-3 pr-4">
                          <?php $cls = status_badge_class($r['status']); ?>
                          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $cls ?>">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['status']))) ?>
                          </span>
                        </td>
                        <td class="py-3 pr-4 max-w-xs">
                          <?php $n = $r['notes'] ?? ''; ?>
                          <?php if ($n !== ''): ?>
                            <span class="text-slate-900" title="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars(mb_strimwidth($n, 0, 80, '…')) ?></span>
                          <?php else: ?>
                            <span class="text-slate-400">—</span>
                          <?php endif; ?>
                        </td>
                        <td class="py-3 pr-4 text-xs text-slate-600"><?= date('M j, Y', strtotime($r['submitted_at'])) ?></td>
                        <td class="py-3 pr-4 text-xs text-slate-600"><?= ($r['updated_at'] ?? '-') ? date('M j, Y', strtotime($r['updated_at'])) : '—' ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
        </div>
      </main>
    </div>

    <script>
      // Sidebar toggles: show one section at a time (ensures same position in shared container)
      const links = document.querySelectorAll('aside nav a[data-target]');
      const sections = document.querySelectorAll('.js-section');
      function showSection(id) {
        sections.forEach(s => {
          if (s.id === id) {
            s.classList.remove('hidden');
          } else {
            s.classList.add('hidden');
          }
        });
        // Update active link highlight
        links.forEach(link => link.classList.remove('bg-slate-100', 'text-indigo-700', 'border'));
        const activeLink = document.querySelector(`a[data-target="${id}"]`);
        if (activeLink) {
          activeLink.classList.add('bg-slate-100', 'text-indigo-700');
        }
      }
      links.forEach(a => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          const target = a.getAttribute('data-target');
          showSection(target);
        });
      });
      // Default: show request form and highlight its link
      showSection('request-form');
      const firstLink = document.querySelector('aside nav a[data-target="request-form"]');
      if (firstLink) {
        firstLink.classList.add('bg-slate-100', 'text-indigo-700');
      }
    </script>
    <script>
      // Notifications dropdown toggle
      (function(){
        const btn = document.getElementById('notif-btn');
        const panel = document.getElementById('notif-panel');
        if (!btn || !panel) return;
        btn.addEventListener('click', function(e){
          e.stopPropagation();
          panel.classList.toggle('hidden');
        });
        document.addEventListener('click', function(){
          panel.classList.add('hidden');
        });
      })();
    </script>
  </body>
  </html>
