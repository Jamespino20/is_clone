<?php
declare(strict_types=1);

// Ensure we always return JSON, even on errors
header('Content-Type: application/json');

// Disable PHP error display to prevent HTML in JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Function to safely return JSON response
function json_response($data) {
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Wrap everything in try-catch to ensure JSON response
try {
    session_start();
    require_once __DIR__ . '/helpers.php';

    $email = $_SESSION['user_email'] ?? null;
    if (!$email) {
        json_response(['ok' => false, 'error' => 'Unauthorized']);
    }

    $actor = get_user_by_email($email);
    if (!$actor || !in_array($actor['role'], ['Staff','Administrator'], true)) {
        json_response(['ok' => false, 'error' => 'Forbidden']);
    }

    $file = __DIR__ . '/data/students.json';
    if (!is_dir(__DIR__ . '/data')) {
        @mkdir(__DIR__ . '/data', 0775, true);
    }
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }

    function read_students_file(string $f): array {
        $raw = @file_get_contents($f);
        $j = json_decode($raw ?: '[]', true);
        return is_array($j) ? $j : [];
    }

    function write_students_file(string $f, array $list): void {
        @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
    $students = read_students_file($file);

    switch ($action) {
        case 'list':
            try {
                // Merge user accounts with role Student so they appear even if not yet in students.json
                $allUsers = read_users();
                $byEmail = [];
                foreach ($students as $s) {
                    $byEmail[strtolower($s['email'] ?? '')] = true;
                }
                foreach ($allUsers as $u) {
                    if (($u['role'] ?? '') === 'Student') {
                        $e = strtolower($u['email'] ?? '');
                        if (!isset($byEmail[$e])) {
                            $students[] = [
                                'id' => (int) (max(array_column($students, 'id')) ?: 0) + 1,
                                'name' => $u['name'] ?? '',
                                'email' => $u['email'] ?? '',
                                'student_id' => $u['student_id'] ?? 'STU' . rand(1000, 9999),
                                'grade_level' => $u['grade_level'] ?? 'Grade 7',
                                'section' => $u['section'] ?? 'St. Luke',
                                'enrollment_status' => 'Enrolled',
                                'tuition_balance' => 0,
                                'attendance_rate' => 0,
                                'gpa' => 0,
                                'created' => $u['created'] ?? time()
                            ];
                        }
                    }
                }

                // Load enrollment assignments and update student sections
                $enrollmentFile = __DIR__ . '/data/enrollment.json';
                if (file_exists($enrollmentFile)) {
                    $enrollmentRaw = @file_get_contents($enrollmentFile);
                    $enrollmentData = json_decode($enrollmentRaw ?: '{}', true);
                    $assignments = $enrollmentData['student_assignments'] ?? [];

                    foreach ($students as &$student) {
                        $studentId = $student['student_id'] ?? '';
                        if (isset($assignments[$studentId])) {
                            $assignment = $assignments[$studentId];
                            $student['grade_level'] = $assignment['grade_level'];
                            $student['section'] = $assignment['section'];
                        }
                    }
                }

                json_response(['ok' => true, 'items' => $students]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error loading students: ' . $e->getMessage()]);
            }
            break;

        case 'create':
            try {
                $name = trim($_POST['name'] ?? '');
                $studentId = trim($_POST['student_id'] ?? '');
                $studentEmail = trim($_POST['email'] ?? '');
                $grade = trim($_POST['grade_level'] ?? '');
                $section = trim($_POST['section'] ?? '');
                $status = trim($_POST['status'] ?? 'Enrolled');

                if ($name === '' || $studentId === '' || $studentEmail === '' || $grade === '') {
                    json_response(['ok' => false, 'error' => 'Missing fields']);
                }

                // Validate grade level against K-10
                $validLevels = defined('SCHOOL_LEVELS') ? SCHOOL_LEVELS : ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10'];
                if (!in_array($grade, $validLevels, true)) {
                    json_response(['ok' => false, 'error' => 'Invalid grade level']);
                }

                $id = (int) (max(array_column($students, 'id')) ?: 0) + 1;
                $new = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $studentEmail,
                    'student_id' => $studentId,
                    'grade_level' => $grade,
                    'section' => $section,
                    'enrollment_status' => $status,
                    'tuition_balance' => 0,
                    'attendance_rate' => 0,
                    'gpa' => 0,
                    'created' => time()
                ];

                // Check if there's an enrollment assignment for this student
                $enrollmentFile = __DIR__ . '/data/enrollment.json';
                if (file_exists($enrollmentFile)) {
                    $enrollmentRaw = @file_get_contents($enrollmentFile);
                    $enrollmentData = json_decode($enrollmentRaw ?: '{}', true);
                    $assignments = $enrollmentData['student_assignments'] ?? [];

                    if (isset($assignments[$studentId])) {
                        $assignment = $assignments[$studentId];
                        $new['grade_level'] = $assignment['grade_level'];
                        $new['section'] = $assignment['section'];
                    }
                }

                $students[] = $new;
                write_students_file($file, $students);
                json_response(['ok' => true, 'item' => $new]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error creating student: ' . $e->getMessage()]);
            }
            break;

        case 'update':
            try {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    json_response(['ok' => false, 'error' => 'Missing id']);
                }

                foreach ($students as $i => $s) {
                    if ((int)$s['id'] === $id) {
                        $fields = ['name','email','student_id','grade_level','section','enrollment_status','tuition_balance','attendance_rate','gpa'];
                        foreach ($fields as $f) {
                            if (isset($_POST[$f])) {
                                $students[$i][$f] = $_POST[$f];
                            }
                        }
                        write_students_file($file, $students);
                        json_response(['ok' => true]);
                    }
                }
                json_response(['ok' => false, 'error' => 'Not found']);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error updating student: ' . $e->getMessage()]);
            }
            break;

        case 'delete':
            try {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    json_response(['ok' => false, 'error' => 'Missing id']);
                }

                $students = array_values(array_filter($students, fn($s) => (int)$s['id'] !== $id));
                write_students_file($file, $students);
                json_response(['ok' => true]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error deleting student: ' . $e->getMessage()]);
            }
            break;

        default:
            json_response(['ok' => false, 'error' => 'Unknown action']);
    }

} catch (Exception $e) {
    // Ensure we always return JSON even if a fatal error occurs
    json_response(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
