<?php
// --- TOTP (Google Authenticator) helpers ---
function base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $c) $binary .= sprintf('%08b', ord($c));
    $pad = 5 - (strlen($binary) % 5);
    if ($pad < 5) $binary .= str_repeat('0', $pad);
    $out = '';
    foreach (str_split($binary, 5) as $chunk) $out .= $alphabet[bindec($chunk)];
    return $out;
}
function totp_secret() {
    return base32_encode(random_bytes(10));
}
function totp_otpauth_url($label, $secret, $issuer = 'DSA-School') {
    $label = rawurlencode($label);
    $issuer = rawurlencode($issuer);
    return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
}
function totp_verify($secret, $code, $window = 1) {
    $tm = floor(time() / 30);
    for ($i = -$window; $i <= $window; ++$i) {
        if (totp_code($secret, $tm + $i) === $code) return true;
    }
    return false;
}
function totp_code($secret, $tm = null) {
    if ($tm === null) $tm = floor(time() / 30);
    $key = base32_decode($secret);
    $bin = pack('N*', 0) . pack('N*', $tm);
    $hash = hash_hmac('sha1', $bin, $key, true);
    $offset = ord($hash[19]) & 0xf;
    $trunc = (ord($hash[$offset]) & 0x7f) << 24 |
        (ord($hash[$offset + 1]) & 0xff) << 16 |
        (ord($hash[$offset + 2]) & 0xff) << 8 |
        (ord($hash[$offset + 3]) & 0xff);
    return str_pad($trunc % 1000000, 6, '0', STR_PAD_LEFT);
}
function base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $binary = '';
    foreach (str_split($b32) as $c) {
        $pos = strpos($alphabet, $c);
        if ($pos === false) continue;
        $binary .= sprintf('%05b', $pos);
    }
    $out = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) < 8) continue;
        $out .= chr(bindec($chunk));
    }
    return $out;
}
// Helpers for reading/writing JSON "db" and simple auth helpers
define('DATA_DIR', __DIR__ . '/../data');
function users_path(){ return DATA_DIR . '/users.json'; }
function read_users(){ $p = users_path(); if(!file_exists($p)) return []; $j = file_get_contents($p); $a = json_decode($j,true); return is_array($a)?$a:[]; }
function write_users($arr){ $p = users_path(); file_put_contents($p, json_encode($arr, JSON_PRETTY_PRINT)); }
function find_user_by_email($email){ foreach(read_users() as $u) if(strtolower($u['email'])===strtolower($email)) return $u; return null; }
function update_user($email,$new){ $users = read_users(); foreach($users as $i=>$u){ if(strtolower($u['email'])===strtolower($email)){ $users[$i] = array_merge($u,$new); write_users($users); return true; } } return false; }
function add_user($user){ $users = read_users(); $users[] = $user; write_users($users); }
function hash_password($pw){ return password_hash($pw, PASSWORD_DEFAULT); }
function verify_password($pw,$hash){ return password_verify($pw,$hash); }
function gen_token($len=40){ $bytes = random_bytes($len); return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }
function gen_6digit(){ return str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT); }
?>