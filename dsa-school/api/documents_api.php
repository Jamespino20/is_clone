<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$ds = DataStructuresManager::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
  case 'request':
    $type = trim($_POST['type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($type === '') { echo json_encode(['ok'=>false,'error'=>'Missing type']); exit; }
    $ds->requestDocument($email, $type, $notes);
    echo json_encode(['ok'=>true]);
    break;
  case 'list':
    echo json_encode(['ok'=>true,'items'=>$ds->getDocumentRequestQueue()->getAll()]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

