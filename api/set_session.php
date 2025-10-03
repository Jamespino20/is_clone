<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

$key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['key'] ?? '');
$val = $_POST['value'] ?? null;

if ($key === '' || $val === null) {
    echo json_encode(['ok' => false, 'error' => 'Missing key or value']);
    exit;
}

$_SESSION[$key] = $val;
echo json_encode(['ok' => true]);
?>