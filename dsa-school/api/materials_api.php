<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Faculty') {
    echo json_encode(['ok'=>false,'error'=>'Only faculty can access materials']); exit;
}

$file = __DIR__ . '/data/materials.json';
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_materials(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_materials(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$materials = read_materials($file);

switch ($action) {
    case 'list':
        $facultyEmail = strtolower($email);
        $myMaterials = array_filter($materials, fn($m) => strtolower($m['faculty_email'] ?? '') === $facultyEmail);
        echo json_encode(['ok'=>true,'items'=>array_values($myMaterials)]);
        break;
        
    case 'upload':
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? 'Lecture Notes');
        $class = trim($_POST['class'] ?? '');
        $url = trim($_POST['url'] ?? '#');
        $size = trim($_POST['size'] ?? '1.0 MB');
        
        if (!$name || !$class) {
            echo json_encode(['ok'=>false,'error'=>'Missing required fields']);
            break;
        }
        
        $newMaterial = [
            'id' => count($materials) + 1,
            'faculty_email' => strtolower($email),
            'name' => $name,
            'type' => $type,
            'class' => $class,
            'url' => $url,
            'size' => $size,
            'downloads' => 0,
            'uploaded' => time()
        ];
        
        $materials[] = $newMaterial;
        write_materials($file, $materials);
        echo json_encode(['ok'=>true,'item'=>$newMaterial]);
        break;
        
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['ok'=>false,'error'=>'Missing material ID']);
            break;
        }
        
        $facultyEmail = strtolower($email);
        $materials = array_values(array_filter($materials, fn($m) => 
            intval($m['id']) !== $id || strtolower($m['faculty_email'] ?? '') !== $facultyEmail
        ));
        
        write_materials($file, $materials);
        echo json_encode(['ok'=>true]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
