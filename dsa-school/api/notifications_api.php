<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$ds = DataStructuresManager::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
  case 'send':
    $actor = get_user_by_email($email);
    if (!$actor) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
    $target = trim($_POST['target_email'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = trim($_POST['type'] ?? 'info');
    if ($target === '' || $title === '' || $message === '') { echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }
    $ds->addNotification($target, $title, $message, $type);
    $ds->logActivity($email, 'send_notification', "to=$target title=$title");
    echo json_encode(['ok'=>true]);
    break;
  case 'mark_read':
    $idx = (int)($_POST['index'] ?? -1);
    if ($idx < 0) { echo json_encode(['ok'=>false,'error'=>'Invalid index']); exit; }
    $ok = $ds->markNotificationRead($idx, $email);
    echo json_encode(['ok'=>$ok]);
    break;
  case 'list':
    $all = $ds->getNotificationQueue()->getAll();
    echo json_encode(['ok'=>true,'items'=>$all]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

