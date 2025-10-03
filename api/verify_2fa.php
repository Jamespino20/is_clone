<?php
require_once 'vendor/autoload.php';
require_once 'helpers.php';

use OTPHP\TOTP;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = $_POST['code'] ?? '';
  $email = $_SESSION['email'] ?? '';

  if (!$email) {
    http_response_code(403);
    echo json_encode(['error' => 'No login session']);
    exit;
  }

  $user = get_user_by_email($email);

  if (!$user || !isset($user['totp_secret'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not found or 2FA not set']);
    exit;
  }

  $totp = TOTP::create($user['totp_secret']);

  if ($totp->verify($code)) {
    $_SESSION['logged_in'] = true;
    http_response_code(200);
    echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
    exit;
  } else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid 2FA code']);
    exit;
  }
}
?>
