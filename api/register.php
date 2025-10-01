<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$pw = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'student';
if(!$name||!$email||!$pw){ echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }
if(find_user_by_email($email)){ echo json_encode(['ok'=>false,'error'=>'User exists']); exit; }

$totp_secret = totp_secret();
$otpauth_url = totp_otpauth_url($email, $totp_secret, 'DSA-School');
$user = [
	'name'=>$name,
	'email'=>$email,
	'password'=>hash_password($pw),
	'role'=>$role,
	'created'=>time(),
	'totp_secret'=>$totp_secret,
	'sessions'=>[]
];
add_user($user);
echo json_encode(['ok'=>true,'totp_secret'=>$totp_secret,'otpauth_url'=>$otpauth_url]);
?>
