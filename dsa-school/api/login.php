<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
  session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Invalid method']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$pw    = $_POST['password'] ?? '';

if (!$email || $pw === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

// Domain rule (allow gmail + school)
if (!preg_match('/@slssr\.edu(\.ph)?$|@gmail\.com$/i', $email)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid email domain']);
    exit;
}

$user = get_user_by_email($email);
if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}


if (!verify_password($pw, $user['password'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid password']);
    exit;
}

// Require 2FA only if secret exists and it's enabled (default true if flag missing)
$twofaEnabled = $user['twofa_enabled'] ?? true;
if (!empty($user['totp_secret']) && $twofaEnabled) {
    $temp = gen_token(12);
    $_SESSION['temp_user_email'] = $email;
    $_SESSION['temp_token']      = $temp;
    echo json_encode(['ok' => true, 'twofa_required' => true, 'temp_token' => $temp]);
    exit;
}

// Direct login if no 2FA
$_SESSION['user_email'] = $email;
echo json_encode(['ok' => true]);
?>