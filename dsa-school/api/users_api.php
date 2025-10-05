<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$actorEmail = $_SESSION['user_email'] ?? null;
if (!$actorEmail) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
$actor = get_user_by_email($actorEmail);
if (!$actor || $actor['role'] !== 'Administrator') { echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $users = read_users();
    
    $safeUsers = array_map(function($user) {
        return [
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'Student'
        ];
    }, $users);
    
    echo json_encode(['ok' => true, 'users' => $safeUsers]);
} else {
    echo json_encode(['ok'=>false,'error'=>'Invalid action']);
}
