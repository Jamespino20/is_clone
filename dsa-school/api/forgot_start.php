<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/helpers.php';

$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
if ($email === '') { echo json_encode(['ok'=>false,'error'=>'Missing email']); exit; }
$user = get_user_by_email($email);
if (!$user) { echo json_encode(['ok'=>false,'error'=>'Account not found']); exit; }

// If user has TOTP and 2FA enabled, require 2FA
$needs = !empty($user['totp_secret']) && (($user['twofa_enabled'] ?? true) === true);
if ($needs) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['temp_user_email'] = $email;
}
echo json_encode(['ok'=>true,'twofa_required'=>$needs]);
?>


