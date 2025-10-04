<?php
// verify_2fa.php — validates a 6-digit TOTP code
// Responds JSON: { ok:true } on success, { ok:false, error:"..." } on failure

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
  session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/helpers.php';

$code = isset($_POST['code']) ? trim((string)$_POST['code']) : '';
if ($code === '') {
  echo json_encode(['ok' => false, 'error' => 'No code provided']);
  exit;
}

// Email captured during password auth step
$email = $_SESSION['temp_user_email'] ?? null;
if (!$email) {
  echo json_encode(['ok' => false, 'error' => 'No active 2FA session']);
  exit;
}

$user = get_user_by_email((string)$email);
if (!$user) {
  echo json_encode(['ok' => false, 'error' => 'User not found']);
  exit;
}

$secret = $user['totp_secret'] ?? '';
if ($secret === '') {
  // If no secret present, treat as not requiring 2FA anymore
  $_SESSION['user_email'] = $user['email'];
  unset($_SESSION['temp_user_email'], $_SESSION['temp_token']);
  echo json_encode(['ok' => true]);
  exit;
}

if (totp_verify($secret, $code, 1, 30, 6)) {
  // Promote temp session to authenticated session
  session_regenerate_id(true);
  $_SESSION['user_email'] = $user['email'];
  unset($_SESSION['temp_user_email'], $_SESSION['temp_token']);
  echo json_encode(['ok' => true]);
  exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid or expired code']);