<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
$code = trim($body['code'] ?? '');
$remember = !empty($body['remember']);
if($code === '') exit(json_encode(['success'=>false,'error'=>'Missing code']));
if(empty($_SESSION['pending_2fa_user'])) exit(json_encode(['success'=>false,'error'=>'No pending 2FA session']));
$uid = $_SESSION['pending_2fa_user'];
$user = find_user('id', $uid);
if(!$user) exit(json_encode(['success'=>false,'error'=>'User not found']));

if(verify_totp($user['totp_secret'], $code, 1, 30)){
    // success: set session and remember duration if requested
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    unset($_SESSION['pending_2fa_user']);
    touch_session();
    if($remember){
        // remember for 30 days
        set_remember_duration(30 * 24 * 3600);
    } else {
        // default 7 days inactivity
        set_remember_duration(7 * 24 * 3600);
    }
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>'Invalid 2FA code']);
}
