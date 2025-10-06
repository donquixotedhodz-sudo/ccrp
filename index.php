<?php
session_start();
// Simple flash messaging
$flash_success = $_GET['success'] ?? null;
$flash_error = $_GET['error'] ?? null;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credentials Request Portal • Central Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/custom.css">
  </head>
  <body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200">
    <div class="min-h-screen flex items-center justify-center px-4">
      <div class="w-full max-w-md">
        <div class="text-center mb-6">
          <h1 class="text-2xl font-semibold text-slate-800">Credentials Request Portal</h1>
          <p class="text-sm text-slate-600">Login as Student, Registrar, or Admin</p>
        </div>

        <?php if ($flash_success): ?>
          <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
            <?= htmlspecialchars($flash_success) ?>
          </div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
          <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            <?= htmlspecialchars($flash_error) ?>
          </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <div class="p-6">
            <!-- Centralized Login Form -->
            <form id="login-form" action="auth/login.php" method="post" class="space-y-4">
              <div>
                <label for="login-identifier" class="block text-sm font-medium text-slate-700">Email or Username</label>
                <input id="login-identifier" name="identifier" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="you@example.com or admin">
              </div>
              <div>
                <label for="login-password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="login-password" name="password" type="password" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••••••">
              </div>
              <button id="login-submit" type="submit" class="w-full inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700 focus:outline-none">Login</button>
            </form>

            <!-- Registration Form (Hidden by Default) -->
            <div id="register-form" class="space-y-4 hidden mt-6">
              <h2 class="text-lg font-medium text-slate-800 text-center">Sign Up as Student</h2>
              <form action="auth/register.php" method="post">
                <input type="hidden" name="role" value="student">
                <div>
                  <label for="reg-name" class="block text-sm font-medium text-slate-700">Full Name</label>
                  <input id="reg-name" name="full_name" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="John Doe">
                </div>
                <div>
                  <label for="reg-email" class="block text-sm font-medium text-slate-700">Email</label>
                  <input id="reg-email" name="email" type="email" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="student@example.com">
                </div>
                <div>
                  <label for="reg-password" class="block text-sm font-medium text-slate-700">Password</label>
                  <input id="reg-password" name="password" type="password" minlength="8" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="At least 8 characters">
                </div>
                <div>
                  <label for="reg-confirm-password" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                  <input id="reg-confirm-password" name="confirm_password" type="password" minlength="8" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Repeat your password">
                </div>
                <button type="submit" class="w-full inline-flex justify-center rounded-md bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700 focus:outline-none">Sign Up</button>
              </form>
              <p class="text-center mt-4">
                <a href="#" id="show-login" class="text-indigo-600 hover:text-indigo-500">Back to Login</a>
              </p>
            </div>

            <!-- Sign Up Link (Shown by Default with Login Form) -->
            <p id="signup-link" class="text-center mt-4">
              <a href="#" id="show-register" class="text-indigo-600 hover:text-indigo-500">Don't have an account? Sign Up as Student</a>
            </p>

          

    <script>
      const identifierInput = document.getElementById('login-identifier');
      const loginForm = document.getElementById('login-form');
      const registerForm = document.getElementById('register-form');
      const signupLink = document.getElementById('signup-link');
      // Centralized login: identifier can be email or username
      identifierInput.addEventListener('input', () => {
        identifierInput.value = identifierInput.value.trimStart();
      });

      function showLogin() {
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        signupLink.classList.remove('hidden');
      }

      function showRegister() {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        signupLink.classList.add('hidden');
      }

      // No role selection; centralized login
      document.getElementById('show-register').addEventListener('click', function(e) {
        e.preventDefault();
        showRegister();
      });
      document.getElementById('show-login').addEventListener('click', function(e) {
        e.preventDefault();
        showLogin();
      });

      // Initialize defaults
      showLogin();
    </script>
  </body>
  </html>
