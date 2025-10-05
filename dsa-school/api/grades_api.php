<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user || !in_array($user['role'], ['Faculty', 'Staff', 'Administrator'], true)) {
    echo json_encode(['ok'=>false,'error'=>'Forbidden']);
    exit;
}

$file = __DIR__ . '/data/grades.json';
if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0775, true);
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_grades_file(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_grades_file(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$grades = read_grades_file($file);

switch ($action) {
    case 'save':
        $class = trim($_POST['class'] ?? '');
        $quarter = trim($_POST['quarter'] ?? 'Q1');
        $students = json_decode($_POST['students'] ?? '[]', true);
        
        if (empty($class) || !is_array($students) || empty($students)) {
            echo json_encode(['ok'=>false,'error'=>'Missing required fields']);
            break;
        }

        $timestamp = date('Y-m-d H:i:s');
        $saved = [];

        foreach ($students as $student) {
            $studentId = trim($student['student_id'] ?? '');
            $studentName = trim($student['student_name'] ?? '');
            $studentEmail = trim($student['student_email'] ?? '');
            $prelim = floatval($student['prelim_grade'] ?? 0);
            $midterm = floatval($student['midterm_grade'] ?? 0);
            $finals = floatval($student['finals_grade'] ?? 0);
            
            if (empty($studentId)) continue;
            
            $average = ($prelim * 0.4) + ($midterm * 0.3) + ($finals * 0.3);
            
            $gradeRecord = [
                'student_id' => $studentId,
                'student_name' => $studentName,
                'student_email' => $studentEmail,
                'class' => $class,
                'quarter' => $quarter,
                'prelim_grade' => $prelim,
                'midterm_grade' => $midterm,
                'finals_grade' => $finals,
                'average' => round($average, 2),
                'faculty_email' => $email,
                'updated_at' => $timestamp
            ];

            $found = false;
            foreach ($grades as $i => $g) {
                if ($g['student_id'] === $studentId && 
                    $g['class'] === $class && 
                    $g['quarter'] === $quarter) {
                    $grades[$i] = $gradeRecord;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $grades[] = $gradeRecord;
            }
            
            $saved[] = $gradeRecord;
        }

        write_grades_file($file, $grades);
        echo json_encode(['ok'=>true, 'message'=>'Grades saved successfully', 'saved'=>count($saved)]);
        break;

    case 'get':
        $class = trim($_GET['class'] ?? '');
        $quarter = trim($_GET['quarter'] ?? 'Q1');
        $studentId = trim($_GET['student_id'] ?? '');

        $filtered = array_filter($grades, function($g) use ($class, $quarter, $studentId) {
            $match = true;
            if (!empty($class)) $match = $match && ($g['class'] ?? '') === $class;
            if (!empty($quarter)) $match = $match && ($g['quarter'] ?? '') === $quarter;
            if (!empty($studentId)) $match = $match && ($g['student_id'] ?? '') === $studentId;
            return $match;
        });

        echo json_encode(['ok'=>true, 'grades'=>array_values($filtered)]);
        break;

    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
