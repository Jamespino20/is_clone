<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }

$file = __DIR__ . '/data/tuition.json';
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_tuition_file(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_tuition_file(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$tuitionRecords = read_tuition_file($file);

switch ($action) {
    case 'list':
        if (in_array($user['role'], ['Staff', 'Administrator'], true)) {
            $studentsFile = __DIR__ . '/data/students.json';
            $students = read_tuition_file($studentsFile);
            
            $allUsers = read_users();
            $studentUsers = array_filter($allUsers, fn($u) => ($u['role'] ?? '') === 'Student');
            
            $result = [];
            foreach ($studentUsers as $student) {
                $studentEmail = strtolower($student['email'] ?? '');
                $record = null;
                foreach ($tuitionRecords as $t) {
                    if (strtolower($t['student_email'] ?? '') === $studentEmail) {
                        $record = $t;
                        break;
                    }
                }
                
                if (!$record) {
                    $record = [
                        'id' => count($tuitionRecords) + count($result) + 1,
                        'student_id' => $student['student_id'] ?? '',
                        'student_name' => $student['name'] ?? '',
                        'student_email' => $student['email'] ?? '',
                        'total_amount' => 15000,
                        'paid_amount' => 0,
                        'balance' => 15000,
                        'status' => 'Unpaid',
                        'last_payment_date' => null,
                        'payment_history' => []
                    ];
                }
                
                $record['balance'] = $record['total_amount'] - $record['paid_amount'];
                if ($record['balance'] <= 0) {
                    $record['status'] = 'Paid';
                } elseif ($record['paid_amount'] > 0) {
                    $record['status'] = 'Partial';
                } else {
                    $record['status'] = 'Unpaid';
                }
                
                $result[] = $record;
            }
            
            echo json_encode(['ok'=>true,'items'=>$result]);
        } elseif ($user['role'] === 'Student') {
            $studentEmail = strtolower($email);
            $record = null;
            foreach ($tuitionRecords as $t) {
                if (strtolower($t['student_email'] ?? '') === $studentEmail) {
                    $record = $t;
                    break;
                }
            }
            
            if (!$record) {
                $record = [
                    'id' => count($tuitionRecords) + 1,
                    'student_id' => $user['student_id'] ?? '',
                    'student_name' => $user['name'] ?? '',
                    'student_email' => $user['email'] ?? '',
                    'total_amount' => 15000,
                    'paid_amount' => 0,
                    'balance' => 15000,
                    'status' => 'Unpaid',
                    'last_payment_date' => null,
                    'payment_history' => []
                ];
            }
            
            $record['balance'] = $record['total_amount'] - $record['paid_amount'];
            if ($record['balance'] <= 0) {
                $record['status'] = 'Paid';
            } elseif ($record['paid_amount'] > 0) {
                $record['status'] = 'Partial';
            } else {
                $record['status'] = 'Unpaid';
            }
            
            echo json_encode(['ok'=>true,'item'=>$record]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
        }
        break;
        
    case 'record_payment':
        if (!in_array($user['role'], ['Staff', 'Administrator'], true)) {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
            break;
        }
        
        $studentEmail = trim($_POST['student_email'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $method = trim($_POST['method'] ?? 'Cash');
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$studentEmail || $amount <= 0) {
            echo json_encode(['ok'=>false,'error'=>'Invalid input']);
            break;
        }
        
        $studentEmail = strtolower($studentEmail);
        $found = false;
        foreach ($tuitionRecords as &$record) {
            if (strtolower($record['student_email'] ?? '') === $studentEmail) {
                $found = true;
                $record['paid_amount'] = ($record['paid_amount'] ?? 0) + $amount;
                $record['last_payment_date'] = time();
                
                if (!isset($record['payment_history'])) {
                    $record['payment_history'] = [];
                }
                
                $record['payment_history'][] = [
                    'date' => time(),
                    'amount' => $amount,
                    'method' => $method,
                    'notes' => $notes,
                    'recorded_by' => $email
                ];
                
                $record['balance'] = $record['total_amount'] - $record['paid_amount'];
                if ($record['balance'] <= 0) {
                    $record['status'] = 'Paid';
                } elseif ($record['paid_amount'] > 0) {
                    $record['status'] = 'Partial';
                }
                
                break;
            }
        }
        unset($record);
        
        if (!$found) {
            $studentUser = null;
            $allUsers = read_users();
            foreach ($allUsers as $u) {
                if (strtolower($u['email'] ?? '') === $studentEmail) {
                    $studentUser = $u;
                    break;
                }
            }
            
            if (!$studentUser) {
                echo json_encode(['ok'=>false,'error'=>'Student not found']);
                break;
            }
            
            $newRecord = [
                'id' => count($tuitionRecords) + 1,
                'student_id' => $studentUser['student_id'] ?? '',
                'student_name' => $studentUser['name'] ?? '',
                'student_email' => $studentUser['email'] ?? '',
                'total_amount' => 15000,
                'paid_amount' => $amount,
                'balance' => 15000 - $amount,
                'status' => $amount >= 15000 ? 'Paid' : 'Partial',
                'last_payment_date' => time(),
                'payment_history' => [
                    [
                        'date' => time(),
                        'amount' => $amount,
                        'method' => $method,
                        'notes' => $notes,
                        'recorded_by' => $email
                    ]
                ]
            ];
            
            $tuitionRecords[] = $newRecord;
        }
        
        write_tuition_file($file, $tuitionRecords);
        echo json_encode(['ok'=>true]);
        break;
        
    case 'update_total':
        if (!in_array($user['role'], ['Staff', 'Administrator'], true)) {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
            break;
        }
        
        $studentEmail = trim($_POST['student_email'] ?? '');
        $newTotal = floatval($_POST['total_amount'] ?? 0);
        
        if (!$studentEmail || $newTotal <= 0) {
            echo json_encode(['ok'=>false,'error'=>'Invalid input']);
            break;
        }
        
        $studentEmail = strtolower($studentEmail);
        $found = false;
        foreach ($tuitionRecords as &$record) {
            if (strtolower($record['student_email'] ?? '') === $studentEmail) {
                $found = true;
                $record['total_amount'] = $newTotal;
                $record['balance'] = $newTotal - ($record['paid_amount'] ?? 0);
                
                if ($record['balance'] <= 0) {
                    $record['status'] = 'Paid';
                } elseif ($record['paid_amount'] > 0) {
                    $record['status'] = 'Partial';
                } else {
                    $record['status'] = 'Unpaid';
                }
                
                break;
            }
        }
        unset($record);
        
        if (!$found) {
            echo json_encode(['ok'=>false,'error'=>'Record not found']);
            break;
        }
        
        write_tuition_file($file, $tuitionRecords);
        echo json_encode(['ok'=>true]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
