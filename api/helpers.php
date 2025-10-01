<?php
// helpers.php - user storage and TOTP helpers
// ensure server timezone (adjust as needed)
date_default_timezone_set('Asia/Manila');

// tighten session cookie settings before starting session
ini_set('session.use_strict_mode', 1);
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

define('DATA_FILE', __DIR__ . '/../data/users.json');

function load_users(){
    if(!file_exists(DATA_FILE)) file_put_contents(DATA_FILE, json_encode([]));
    $raw = file_get_contents(DATA_FILE);
    $arr = json_decode($raw, true);
    if(!is_array($arr)) $arr = [];
    return $arr;
}

function save_users($users){
    file_put_contents(DATA_FILE, json_encode(array_values($users), JSON_PRETTY_PRINT));
}

// Session helpers ---------------------------------------------------------
// update last activity timestamp for session
function touch_session(){
    $_SESSION['last_activity'] = time();
}

// set the remembered session duration (seconds)
function set_remember_duration($seconds){
    $_SESSION['remember_duration'] = (int)$seconds;
}

// check if session has expired based on inactivity and remember duration
function session_expired(){
    if(empty($_SESSION['last_activity'])) return false;
    $max_inactive = $_SESSION['remember_duration'] ?? (7 * 24 * 3600); // default 7 days
    if(time() - $_SESSION['last_activity'] > $max_inactive) return true;
    return false;
}


function find_user($field, $value){
    $users = load_users();
    foreach($users as $u) if(isset($u[$field]) && $u[$field] === $value) return $u;
    return null;
}

function update_user($id, $changes){
    $users = load_users();
    foreach($users as &$u){
        if($u['id'] === $id){
            $u = array_merge($u, $changes);
            save_users($users);
            return $u;
        }
    }
    return null;
}

function random_base32($length = 16){
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = '';
    for($i=0;$i<$length;$i++) $s .= $chars[random_int(0, strlen($chars)-1)];
    return $s;
}

function base32_decode($b32){
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $l = strlen($b32);
    $bits = '';
    for($i=0;$i<$l;$i++){
        $val = strpos($alphabet, $b32[$i]);
        if($val === false) continue;
        $bits .= str_pad(decbin($val),5,'0',STR_PAD_LEFT);
    }
    $bytes = '';
    for($i=0;$i<strlen($bits);$i+=8){
        $byte = substr($bits,$i,8);
        if(strlen($byte) < 8) continue;
        $bytes .= chr(bindec($byte));
    }
    return $bytes;
}

function hotp($secret, $counter, $digits=6){
    $bin_counter = pack('J', $counter);
    // pack('J') may not be available on 32-bit, use 64-bit manual
    if(strlen($bin_counter) !== 8){
        $bin_counter = '';
        for($i=7;$i>=0;$i--) $bin_counter .= chr(($counter >> ($i*8)) & 0xFF);
    }
    $key = base32_decode($secret);
    $hash = hash_hmac('sha1', $bin_counter, $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $truncated = substr($hash, $offset, 4);
    $code = unpack('N', $truncated)[1] & 0x7fffffff;
    return str_pad($code % pow(10, $digits), $digits, '0', STR_PAD_LEFT);
}

function totp($secret, $timeSlice=null, $digits=6, $period=30){
    if($timeSlice === null) $timeSlice = floor(time() / $period);
    return hotp($secret, $timeSlice, $digits);
}

function verify_totp($secret, $code, $discrepancy = 1, $period=30){
    $timeSlice = floor(time() / $period);
    for($i=-$discrepancy;$i<=$discrepancy;$i++){
        if(hash_equals(totp($secret, $timeSlice + $i, strlen($code), $period), $code)) return true;
    }
    return false;
}

function create_user($username, $email, $password, $role='student'){
    $users = load_users();
    // unique email or username
    foreach($users as $u){
        if($u['email'] === $email) return ['error'=>'Email already registered'];
        if($u['username'] === $username) return ['error'=>'Username already taken'];
    }
    $id = uniqid('u_', true);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $secret = random_base32(16);
    $user = ['id'=>$id,'username'=>$username,'email'=>$email,'password_hash'=>$hash,'role'=>$role,'totp_secret'=>$secret,'2fa_enabled'=>true];
    $users[] = $user;
    save_users($users);
    return $user;
}

function authenticate_password($user, $password){
    return password_verify($password, $user['password_hash']);
}

?>
