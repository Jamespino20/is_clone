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

// Prevent self-delete
if ($email === strtolower($actorEmail)) { echo json_encode(['ok'=>false,'error'=>'Cannot delete your own account']); exit; }

$list = read_users();
$new = [];
$found = false;
foreach ($list as $u) {
  if (strtolower($u['email']) === $email) { $found = true; continue; }
  $new[] = $u;
}
if (!$found) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }
write_users($new);
// Log activity
$ds = DataStructuresManager::getInstance();
$ds->logActivity($actorEmail, 'user_delete', 'email=' . $email);
echo json_encode(['ok'=>true]);

