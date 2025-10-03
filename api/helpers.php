<?php
// Simple JSON-based user storage (for demo; use a real DB in production)
define('USERS_FILE', __DIR__ . '/users.json');

function read_users() {
    if (file_exists(USERS_FILE)) {
        $data = file_get_contents(USERS_FILE);
        return json_decode($data, true) ?: [];
    }
    return [];
}

function write_users($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function get_user_by_email($email) {
    $users = read_users();
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return $user;
        }
    }
    return null;
}

function update_user($email, $new) {
    $users = read_users();
    foreach ($users as $i => $u) {
        if (strtolower($u['email']) === strtolower($email)) {
            $users[$i] = array_merge($u, $new);
            write_users($users);
            return true;
        }
    }
    return false;
}

function add_user($user) {
    $users = read_users();
    $users[] = $user;
    write_users($users);
}

function hash_password($pw) {
    return password_hash($pw, PASSWORD_DEFAULT);
}

function verify_password($pw, $hash) {
    return password_verify($pw, $hash);
}

function gen_token($len = 40) {
    $bytes = random_bytes($len);
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function gen_6digit() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// New function for Google Auth (TOTP) secret generation
function gen_totp_secret($length = 16) {
    $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $base32[random_int(0, 31)];
    }
    return $secret;
}

?>
