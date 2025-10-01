<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
session_start();
$code = $_POST['code'] ?? '';
$temp = $_POST['temp_token'] ?? '';
if(empty($_SESSION['temp_token']) || empty($_SESSION['temp_user_email']) || $_SESSION['temp_token']!==$temp){ echo json_encode(['ok'=>false,'error'=>'Invalid temp token']); exit; }
$email = $_SESSION['temp_user_email'];
$user = find_user_by_email($email);
if(!$user){ echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }
if(empty($user['totp_secret'])){ echo json_encode(['ok'=>false,'error'=>'2FA not set up for this user']); exit; }
if(!totp_verify($user['totp_secret'], $code)){
	echo json_encode(['ok'=>false,'error'=>'Invalid or expired 2FA code']); exit;
}
// Success: promote to session
$_SESSION['user_email'] = $email;
unset($_SESSION['temp_token'], $_SESSION['temp_user_email']);
echo json_encode(['ok'=>true]);

?>