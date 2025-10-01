<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
if(!$body) exit(json_encode(['success'=>false,'error'=>'Invalid input']));
$userq = trim($body['user'] ?? '');
$pass = $body['password'] ?? '';
if($userq === '' || $pass === '') exit(json_encode(['success'=>false,'error'=>'Missing credentials']));

$users = load_users();
$found = null;
foreach($users as $u){
    if($u['email'] === $userq || $u['username'] === $userq) { $found = $u; break; }
}
if(!$found) exit(json_encode(['success'=>false,'error'=>'User not found']));
if(!authenticate_password($found, $pass)) exit(json_encode(['success'=>false,'error'=>'Invalid password']));

// password ok -> require 2FA if enabled
if(!empty($found['2fa_enabled'])){
    // store pending id
    $_SESSION['pending_2fa_user'] = $found['id'];
    echo json_encode(['success'=>true,'needs2fa'=>true]);
    exit;
}

// else directly set session
$_SESSION['user_id'] = $found['id'];
$_SESSION['role'] = $found['role'];
echo json_encode(['success'=>true,'needs2fa'=>false]);
