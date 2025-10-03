<?php
declare(strict_types=1);

// Set cookie scope site-wide, then start the session
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
  session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Invalid method']);
  exit;
}

$code        = trim($_POST['code'] ?? '');
$clientToken = trim($_POST['temp_token'] ?? '');

if ($code === '' || $clientToken === '') {
  echo json_encode(['ok'=>false,'error'=>'Missing code or token']);
  exit;
}

$sessionEmail = $_SESSION['temp_user_email'] ?? null;
$sessionToken = $_SESSION['temp_token'] ?? null;

if (!$sessionEmail || !$sessionToken || !hash_equals($sessionToken, $clientToken)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid or expired token']);
  exit;
}

$user = get_user_by_email($sessionEmail);
if (!$user || empty($user['totp_secret'])) {
  echo json_encode(['ok'=>false,'error'=>'2FA not configured']);
  exit;
}

if (!preg_match('/^\d{6}$/', $code)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid code format']);
  exit;
}

if (!totp_verify($user['totp_secret'], $code, 1, 30, 6)) {
  echo json_encode(['ok'=>false,'error'=>'Invalid 2FA code']);
  exit;
}

// Promote temp session to full login
unset($_SESSION['temp_user_email'], $_SESSION['temp_token']);
$_SESSION['user_email'] = $sessionEmail;

echo json_encode(['ok'=>true]);
