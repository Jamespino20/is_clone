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

$email = strtolower(trim($_POST['email'] ?? ''));
if ($email === '') { echo json_encode(['ok'=>false,'error'=>'Missing email']); exit; }

$fields = [];
$name = trim($_POST['name'] ?? '');
if ($name !== '') $fields['name'] = $name;

$role = trim($_POST['role'] ?? '');
if ($role !== '') {
  // Prevent downgrading last super admin? For now, do basic validation
  $hier = get_role_hierarchy();
  if (!isset($hier[$role])) { echo json_encode(['ok'=>false,'error'=>'Invalid role']); exit; }
  $fields['role'] = $role;
}

$password = $_POST['password'] ?? '';
if ($password !== '') { $fields['password'] = password_hash($password, PASSWORD_DEFAULT); }

// 2FA enable/disable without destroying existing secret
if (isset($_POST['enable_2fa'])) {
  $enable2FA = $_POST['enable_2fa'] === 'true' || $_POST['enable_2fa'] === '1' || $_POST['enable_2fa'] === 'on';
  $fields['twofa_enabled'] = $enable2FA;
  // If enabling and no secret yet, generate one; if disabling, keep secret intact
  if ($enable2FA) {
    // only set secret if missing
    $current = get_user_by_email($email);
    if (empty($current['totp_secret'])) {
      $fields['totp_secret'] = gen_totp_secret(16);
    }
  }
}

$ok = update_user($email, $fields);
if ($ok) {
  $ds = DataStructuresManager::getInstance();
  $summary = [];
  foreach (['name','role','twofa_enabled'] as $k) if (array_key_exists($k, $fields)) $summary[] = "$k=" . (is_bool($fields[$k]) ? ($fields[$k]?'true':'false') : $fields[$k]);
  if (array_key_exists('password', $fields)) $summary[] = 'password=updated';
  $ds->logActivity($actorEmail, 'user_update', 'email=' . $email . (empty($summary)?'':', ' . implode(', ', $summary)));
}
echo json_encode(['ok'=>$ok]);

