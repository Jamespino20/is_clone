<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Administrator') {
    echo json_encode(['ok'=>false,'error'=>'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'load':
        $settingsFile = __DIR__ . '/data/settings.json';
        $settings = [];

        if (file_exists($settingsFile)) {
            $settingsRaw = @file_get_contents($settingsFile);
            $settings = json_decode($settingsRaw ?: '{}', true) ?: [];
        } else {
            // Default settings
            $settings = [
                'school_name' => 'St. Luke\'s School of San Rafael',
                'school_email' => 'info@slssr.edu.ph',
                'school_address' => 'Sampaloc, San Rafael, Bulacan',
                'school_phone' => '+63 912 345 6789',
                'academic_year' => '2024-2025',
                'current_semester' => '1',
                'enrollment_start' => date('Y-m-d'),
                'enrollment_end' => date('Y-m-d', strtotime('+30 days')),
                'require_2fa' => 'optional',
                'session_timeout' => 30,
                'password_min_length' => 8,
                'timezone' => 'Asia/Manila',
                'grade_levels' => ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
                'semester_system' => 'quarterly', // quarterly, semester, summer
                'grading_scale' => [
                    'A' => [90, 100, 1.0],
                    'B' => [80, 89, 1.5],
                    'C' => [70, 79, 2.0],
                    'D' => [60, 69, 2.5],
                    'F' => [0, 59, 3.0]
                ]
            ];
        }

        echo json_encode(['ok' => true, 'settings' => $settings]);
        break;

    case 'save':
        $settings = $_POST;

        // Validate required fields
        $required = ['school_name', 'school_email', 'school_address', 'school_phone'];
        foreach ($required as $field) {
            if (empty($settings[$field])) {
                echo json_encode(['ok' => false, 'error' => "Field '$field' is required"]);
                exit;
            }
        }

        // Save settings
        $settingsFile = __DIR__ . '/data/settings.json';
        if (!is_dir(__DIR__ . '/data')) {
            mkdir(__DIR__ . '/data', 0755, true);
        }

        $result = file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        if ($result === false) {
            echo json_encode(['ok' => false, 'error' => 'Failed to save settings']);
            exit;
        }

        // Log the change
        $ds = DataStructuresManager::getInstance();
        $ds->logActivity($email, 'settings_updated', 'Updated system settings');

        echo json_encode(['ok' => true, 'message' => 'Settings saved successfully']);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}