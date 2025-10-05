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
  case 'submit':
    $teacher = trim($_POST['teacher_email'] ?? '');
    $scoresJson = $_POST['scores'] ?? '{}';
    $comments = trim($_POST['comments'] ?? '');
    $scores = json_decode($scoresJson, true);
    if (!$teacher || !is_array($scores)) { echo json_encode(['ok'=>false,'error'=>'Invalid payload']); exit; }
    $ds->addEvaluationResponse($email, $teacher, $scores, $comments);
    echo json_encode(['ok'=>true]);
    break;
  case 'list':
    echo json_encode(['ok'=>true,'items'=>$ds->getEvaluationResponseStack()->getAll()]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

