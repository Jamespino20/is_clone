<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$actorEmail = $_SESSION['user_email'] ?? null;
if (!$actorEmail) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
$actor = get_user_by_email($actorEmail);
if (!$actor || $actor['role'] !== 'Administrator') { echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$role = trim($_POST['role'] ?? 'Student');
$password = $_POST['password'] ?? '';
$enable2FA = isset($_POST['enable_2fa']) && ($_POST['enable_2fa'] === 'true' || $_POST['enable_2fa'] === '1' || $_POST['enable_2fa'] === 'on');

if ($name === '' || $email === '' || $password === '') { echo json_encode(['ok'=>false,'error'=>'Missing required fields']); exit; }

// Prevent duplicates
$existing = get_user_by_email($email);
if ($existing) { echo json_encode(['ok'=>false,'error'=>'User already exists']); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);

$new = [
  'name' => $name,
  'email' => $email,
  'role' => in_array($role, array_keys(get_role_hierarchy()), true) ? $role : 'Student',
  'password' => $hash,
  'created' => time(),
];

if ($enable2FA) {
  // Placeholder 2FA flag field, actual secret should be set elsewhere if needed
  $new['totp_secret'] = 'PENDING';
}

add_user($new);
// Log activity
$ds = DataStructuresManager::getInstance();
$ds->logActivity($actorEmail, 'user_create', 'email=' . $email . ', role=' . $new['role']);
echo json_encode(['ok'=>true]);

