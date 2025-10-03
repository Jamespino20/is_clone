<?php
declare(strict_types=1);

// Where users.json lives (you moved data/ inside api/)
define('USERS_FILE', __DIR__ . '/data/users.json');

// -------- Storage helpers --------
function read_users(): array {
    $path = realpath(USERS_FILE);
    error_log('USERS_FILE => ' . ($path ?: USERS_FILE));
    if (!file_exists(USERS_FILE)) { error_log('USERS_FILE missing'); return []; }
    $raw = file_get_contents(USERS_FILE);
    $users = json_decode($raw, true);
    error_log('USERS_COUNT => ' . (is_array($users) ? count($users) : 0));
    if (!is_array($users)) { error_log('JSON decode failed: ' . $raw); return []; }
    return $users;
}

function write_users(array $users): void {
    $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents(USERS_FILE, $json, LOCK_EX);
}

// -------- User helpers --------
function get_user_by_email(string $email): ?array {
    $email = strtolower(trim($email));
    foreach (read_users() as $u) {
        if (strtolower(trim($u['email'])) === $email) return $u;
    }
    error_log('NO_MATCH for email => ' . $email);
    return null;
}

function update_user(string $email, array $new): bool {
    $list = read_users();
    foreach ($list as $i => $u) {
        if (strtolower($u['email']) === strtolower($email)) {
            $list[$i] = array_merge($u, $new);
            write_users($list);
            return true;
        }
    }
    return false;
}

function add_user(array $user): void {
    $list = read_users();
    $list[] = $user;
    write_users($list);
}

// -------- Crypto & tokens --------
function hash_password(string $pw): string {
    return password_hash($pw, PASSWORD_DEFAULT);
}
function verify_password(string $pw, string $hash): bool {
    return password_verify($pw, $hash);
}
function gen_token(int $len = 16): string {
    // URL-safe base64 without padding
    return rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');
}
function gen_6digit(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
function gen_totp_secret(int $length = 16): string {
    $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = '';
    for ($i = 0; $i < $length; $i++) $s .= $base32[random_int(0, 31)];
    return $s;
}

// -------- TOTP (RFC 6238) --------
function base32_decode_str(string $b32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
    $bits = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $v = strpos($alphabet, $b32[$i]);
        if ($v === false) continue;
        $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $out .= chr(bindec(substr($bits, $i, 8)));
    }
    return $out;
}
function hotp(string $key, int $counter, int $digits = 6): string {
    $bin_counter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $bin_counter, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
    return str_pad((string)($truncated % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}
function totp_code(string $secret, ?int $time = null, int $period = 30, int $digits = 6): string {
    $time = $time ?? time();
    $counter = intdiv($time, $period);
    $key = base32_decode_str($secret);
    return hotp($key, $counter, $digits);
}
function totp_verify(string $secret, string $code, int $window = 1, int $period = 30, int $digits = 6): bool {
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secret, $now + ($i * $period), $period, $digits), $code)) {
            return true;
        }
    }
    return false;
}
?>