<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
session_start();
$email = $_POST['email'] ?? '';
$pw = $_POST['password'] ?? '';
if(!$email || !$pw){ echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }
$user = find_user_by_email($email);
if(!$user || !verify_password($pw,$user['password'])){ echo json_encode(['ok'=>false,'error'=>'Invalid credentials']); exit; }
// If user has TOTP secret, require 2FA
if(!empty($user['totp_secret'])){
	$temp = gen_token(12);
	$_SESSION['temp_user_email'] = $email;
	$_SESSION['temp_token'] = $temp;
	echo json_encode(['ok'=>true,'twofa_required'=>true,'temp_token'=>$temp]);
	exit;
}
// otherwise create session
$_SESSION['user_email'] = $email;
echo json_encode(['ok'=>true]);

?>