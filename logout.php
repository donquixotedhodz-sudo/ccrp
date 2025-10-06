<?php
session_start();
session_unset();
session_destroy();
// Delete session cookie if set
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}

function scheme_host(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
  return $scheme . '://' . $host;
}

function base_path(): string {
  $script = $_SERVER['SCRIPT_NAME'] ?? '/logout.php';
  // Use the directory of the script as the base path.
  // Examples:
  //  - '/logout.php' -> '' (root)
  //  - '/ccrp/logout.php' -> '/ccrp'
  //  - '/ccrp/sub/logout.php' -> '/ccrp/sub'
  $dir = dirname($script);
  if ($dir === '.' || $dir === DIRECTORY_SEPARATOR) {
    return '';
  }
  return rtrim($dir, '/');
}

function to_url(string $path): string {
  return scheme_host() . base_path() . $path;
}

header('Location: ' . to_url('/index.php?success=' . urlencode('You have been logged out.')));
exit;