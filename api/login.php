<?php
session_start();

define('USERS_FILE', __DIR__ . '/users.json');

function read_users() {
    if(file_exists(USERS_FILE)){
        $u = json_decode(file_get_contents(USERS_FILE), true);
        return is_array($u) ? $u : [];
    }
    return [];
}

function get_user_by_email($email) {
    $users = read_users();
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) return $user;
    }
    return null;
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function gen_token($len=12) {
    return bin2hex(random_bytes($len));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Invalid method']);
    http_response_code(405);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$pw = $_POST['password'] ?? '';

if (!$email || !$pw) {
    echo json_encode(['ok'=>false,'error'=>'Missing fields']);
    exit;
}

// Restrict email domain here if needed:
if (!preg_match('/@slssr\.edu\.ph)?$/i', $email|!preg_match('/@gmail\.com)?$/i', $email)) {
    echo json_encode(['ok'=>false,'error'=>'Invalid email domain']);
    exit;
}

$user = get_user_by_email($email);

if (!$user) {
    echo json_encode(['ok'=>false,'error'=>'User not found']);
    exit;
}

if (!verify_password($pw, $user['password'])) {
    echo json_encode(['ok'=>false,'error'=>'Invalid password']);
    exit;
}

if (!empty($user['totp_secret'])) {
    // Needs 2FA
    $temp = gen_token();
    $_SESSION['temp_user_email'] = $email;
    $_SESSION['temp_token'] = $temp;
    echo json_encode(['ok'=>true,'twofa_required'=>true,'temp_token'=>$temp]);
    exit;
}

// Logged in without 2FA
$_SESSION['user_email'] = $email;
echo json_encode(['ok'=>true]);
exit;
?>
