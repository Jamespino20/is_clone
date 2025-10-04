<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// If this lives at project root and helpers are in /api:
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Invalid method']);
    exit;
}

$name  = trim($_POST['name']  ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$pw    = $_POST['password'] ?? '';
$role  = trim($_POST['role'] ?? 'student');

if (!$name || !$email || $pw === '' || !$role) {
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

// Example domain rule (adjust as needed)
if (!preg_match('/@(slssr\.edu\.ph|gmail\.com)$/i', $email)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid email domain']);
    exit;
}

if (get_user_by_email($email)) {
    echo json_encode(['ok' => false, 'error' => 'Email already registered']);
    exit;
}

$user = [
    'name'        => $name,
    'email'       => $email,
    'password'    => hash_password($pw),
    'role'        => $role,
    'created'     => time(),
    // Pre-create a TOTP secret to require 2FA at login; remove if you want opt-in
    'totp_secret' => gen_totp_secret(16),
    'sessions'    => []
];

add_user($user);
echo json_encode(['ok' => true, 'totp_secret' => $user['totp_secret']]);
?>