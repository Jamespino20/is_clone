<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/helpers.php';

$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$password = $_POST['password'] ?? '';
if ($email === '' || $password === '') { echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }

// Update user password
function set_user_password(string $email, string $password): bool {
  $users = read_users();
  foreach ($users as $i => $u) {
    if (strtolower($u['email']) === strtolower($email)) {
      $u['password'] = hash_password($password);
      $users[$i] = $u;
      write_users($users);
      return true;
    }
  }
  return false;
}

if (!set_user_password($email, $password)) {
  echo json_encode(['ok'=>false,'error'=>'Account not found']);
  exit;
}

echo json_encode(['ok'=>true]);
?>


