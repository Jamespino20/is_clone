<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }

$ds = DataStructuresManager::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$file = __DIR__ . '/data/documents.json';
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_documents_file(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_documents_file(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

switch ($action) {
    case 'request':
        $type = trim($_POST['type'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($type === '') { echo json_encode(['ok'=>false,'error'=>'Missing type']); exit; }
        $ds->requestDocument($email, $type, $notes);
        echo json_encode(['ok'=>true]);
        break;
        
    case 'list_requests':
        if (in_array($user['role'], ['Staff', 'Administrator'], true)) {
            echo json_encode(['ok'=>true,'items'=>$ds->getDocumentRequestQueue()->getAll()]);
        } elseif ($user['role'] === 'Student') {
            $allRequests = $ds->getDocumentRequestQueue()->getAll();
            $studentRequests = array_filter($allRequests, function($req) use ($email) {
                return isset($req['student_email']) && strtolower($req['student_email']) === strtolower($email);
            });
            echo json_encode(['ok'=>true,'items'=>array_values($studentRequests)]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
        }
        break;
        
    case 'list_available':
        if ($user['role'] !== 'Student') {
            echo json_encode(['ok'=>false,'error'=>'Only students can view documents']);
            break;
        }
        
        $documents = read_documents_file($file);
        $studentEmail = strtolower($email);
        $availableDocs = [];
        
        foreach ($documents as $doc) {
            $targetEmail = strtolower($doc['student_email'] ?? '');
            if ($targetEmail === $studentEmail || $targetEmail === 'all') {
                $availableDocs[] = $doc;
            }
        }
        
        $categorized = [
            'academic' => [],
            'forms' => [],
            'certificates' => []
        ];
        
        foreach ($availableDocs as $doc) {
            $category = $doc['category'] ?? 'academic';
            if (isset($categorized[$category])) {
                $categorized[$category][] = $doc;
            } else {
                $categorized['academic'][] = $doc;
            }
        }
        
        echo json_encode(['ok'=>true,'documents'=>$categorized]);
        break;
        
    case 'add':
        if (!in_array($user['role'], ['Staff', 'Administrator'], true)) {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
            break;
        }
        
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'academic');
        $type = trim($_POST['type'] ?? 'PDF');
        $studentEmail = trim($_POST['student_email'] ?? 'all');
        $size = trim($_POST['size'] ?? '1.0 MB');
        $url = trim($_POST['url'] ?? '#');
        
        if (!$name) {
            echo json_encode(['ok'=>false,'error'=>'Missing document name']);
            break;
        }
        
        $documents = read_documents_file($file);
        $newDoc = [
            'id' => count($documents) + 1,
            'name' => $name,
            'category' => $category,
            'type' => $type,
            'student_email' => strtolower($studentEmail),
            'size' => $size,
            'date' => time(),
            'url' => $url
        ];
        
        $documents[] = $newDoc;
        write_documents_file($file, $documents);
        echo json_encode(['ok'=>true,'item'=>$newDoc]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
