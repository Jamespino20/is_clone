<?php
declare(strict_types=1);

// Data Structures Implementation for School Information System

// Stack Implementation for Activity Logs
class ActivityStack {
    private array $items = [];
    private int $maxSize;
    private ?string $persistFile;
    
    public function __construct(int $maxSize = 100, ?string $persistFile = null) {
        $this->maxSize = $maxSize;
        $this->persistFile = $persistFile;
        if ($this->persistFile && file_exists($this->persistFile)) {
            $raw = file_get_contents($this->persistFile);
            $data = json_decode($raw, true);
            if (is_array($data)) $this->items = $data;
        }
    }
    
    public function push(array $activity): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items); // Remove oldest if at capacity
        }
        $this->items[] = $activity;
        $this->save();
    }
    
    public function pop(): ?array {
        return array_pop($this->items);
    }
    
    public function peek(): ?array {
        return end($this->items) ?: null;
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }
    
    public function getAll(): array {
        return array_reverse($this->items); // Return in chronological order
    }
    
    public function clear(): void {
        $this->items = [];
        $this->save();
    }

    private function save(): void {
        if (!$this->persistFile) return;
        @file_put_contents($this->persistFile, json_encode($this->items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}

// Queue Implementation for Notifications
class NotificationQueue {
    private array $items = [];
    private int $maxSize;
    private ?string $persistFile;
    
    public function __construct(int $maxSize = 200, ?string $persistFile = null) {
        $this->maxSize = $maxSize;
        $this->persistFile = $persistFile;
        if ($this->persistFile && file_exists($this->persistFile)) {
            $raw = file_get_contents($this->persistFile);
            $data = json_decode($raw, true);
            if (is_array($data)) $this->items = $data;
        }
    }
    
    public function enqueue(array $notification): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items); // Remove oldest if at capacity
        }
        $this->items[] = $notification;
        $this->save();
    }
    
    public function dequeue(): ?array {
        $val = array_shift($this->items);
        $this->save();
        return $val;
    }
    
    public function front(): ?array {
        return $this->items[0] ?? null;
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }
    
    public function getAll(): array {
        return $this->items;
    }
    
    public function clear(): void {
        $this->items = [];
        $this->save();
    }

    public function markReadByIndexForUser(int $index, string $userEmail): bool {
        if (!isset($this->items[$index])) return false;
        $n =& $this->items[$index];
        if ($n['user_email'] !== $userEmail && empty($n['is_system'])) return false;
        $n['read'] = true;
        $this->save();
        return true;
    }

    private function save(): void {
        if (!$this->persistFile) return;
        @file_put_contents($this->persistFile, json_encode($this->items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}

// Stack Implementation for Grade History
class GradeHistoryStack {
    private array $items = [];
    private int $maxSize;
    
    public function __construct(int $maxSize = 50) {
        $this->maxSize = $maxSize;
    }
    
    public function pushGrade(array $gradeRecord): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items);
        }
        $this->items[] = $gradeRecord;
    }
    
    public function popGrade(): ?array {
        return array_pop($this->items);
    }
    
    public function getLatestGrade(): ?array {
        return end($this->items) ?: null;
    }
    
    public function getAllGrades(): array {
        return array_reverse($this->items);
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }
}

// Queue Implementation for Assignment Submissions
class AssignmentQueue {
    private array $items = [];
    private int $maxSize;
    
    public function __construct(int $maxSize = 100) {
        $this->maxSize = $maxSize;
    }
    
    public function submitAssignment(array $assignment): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items);
        }
        $this->items[] = $assignment;
    }
    
    public function processNext(): ?array {
        return array_shift($this->items);
    }
    
    public function peekNext(): ?array {
        return $this->items[0] ?? null;
    }
    
    public function getAllSubmissions(): array {
        return $this->items;
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }
}

// Queue for Student Document Requests (e.g., COG, Good Moral)
class DocumentRequestQueue {
    private array $items = [];
    private int $maxSize;
    private ?string $persistFile;

    public function __construct(int $maxSize = 200, ?string $persistFile = null) {
        $this->maxSize = $maxSize;
        $this->persistFile = $persistFile;
        if ($this->persistFile && file_exists($this->persistFile)) {
            $raw = file_get_contents($this->persistFile);
            $data = json_decode($raw, true);
            if (is_array($data)) $this->items = $data;
        }
    }

    public function enqueue(array $request): void {
        if (count($this->items) >= $this->maxSize) array_shift($this->items);
        $this->items[] = $request;
        $this->save();
    }

    public function dequeue(): ?array {
        $val = array_shift($this->items);
        $this->save();
        return $val;
    }

    public function getAll(): array { return $this->items; }
    public function size(): int { return count($this->items); }
    public function clear(): void { $this->items = []; $this->save(); }

    private function save(): void {
        if (!$this->persistFile) return;
        @file_put_contents($this->persistFile, json_encode($this->items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}

// Stack for Teacher Evaluation Responses (most recent on top)
class EvaluationResponseStack {
    private array $items = [];
    private int $maxSize;
    private ?string $persistFile;

    public function __construct(int $maxSize = 500, ?string $persistFile = null) {
        $this->maxSize = $maxSize;
        $this->persistFile = $persistFile;
        if ($this->persistFile && file_exists($this->persistFile)) {
            $raw = file_get_contents($this->persistFile);
            $data = json_decode($raw, true);
            if (is_array($data)) $this->items = $data;
        }
    }

    public function push(array $response): void {
        if (count($this->items) >= $this->maxSize) array_shift($this->items);
        $this->items[] = $response;
        $this->save();
    }

    public function pop(): ?array { $val = array_pop($this->items); $this->save(); return $val; }
    public function peek(): ?array { return end($this->items) ?: null; }
    public function getAll(): array { return array_reverse($this->items); }
    public function size(): int { return count($this->items); }
    public function clear(): void { $this->items = []; $this->save(); }

    private function save(): void {
        if (!$this->persistFile) return;
        @file_put_contents($this->persistFile, json_encode($this->items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}

// Stack Implementation for System Logs
class SystemLogStack {
    private array $items = [];
    private int $maxSize;
    private ?string $persistFile;
    
    public function __construct(int $maxSize = 500, ?string $persistFile = null) {
        $this->maxSize = $maxSize;
        $this->persistFile = $persistFile;
        if ($this->persistFile && file_exists($this->persistFile)) {
            $raw = file_get_contents($this->persistFile);
            $data = json_decode($raw, true);
            if (is_array($data)) $this->items = $data;
        }
    }
    
    public function log(array $logEntry): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items);
        }
        $this->items[] = $logEntry;
        $this->save();
    }
    
    public function getLatestLog(): ?array {
        return end($this->items) ?: null;
    }
    
    public function getAllLogs(): array {
        return array_reverse($this->items);
    }
    
    public function getLogsByUser(string $userEmail): array {
        return array_filter($this->items, fn($log) => $log['user_email'] === $userEmail);
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }

    private function save(): void {
        if (!$this->persistFile) return;
        @file_put_contents($this->persistFile, json_encode($this->items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}

// Queue Implementation for Tuition Payments
class PaymentQueue {
    private array $items = [];
    private int $maxSize;
    
    public function __construct(int $maxSize = 100) {
        $this->maxSize = $maxSize;
    }
    
    public function addPayment(array $payment): void {
        if (count($this->items) >= $this->maxSize) {
            array_shift($this->items);
        }
        $this->items[] = $payment;
    }
    
    public function processPayment(): ?array {
        return array_shift($this->items);
    }
    
    public function getPendingPayments(): array {
        return array_filter($this->items, fn($payment) => $payment['status'] === 'pending');
    }
    
    public function getAllPayments(): array {
        return $this->items;
    }
    
    public function isEmpty(): bool {
        return empty($this->items);
    }
    
    public function size(): int {
        return count($this->items);
    }
}

// Data Structures Manager
class DataStructuresManager {
    private static ?self $instance = null;
    private ActivityStack $activityStack;
    private NotificationQueue $notificationQueue;
    private GradeHistoryStack $gradeStack;
    private AssignmentQueue $assignmentQueue;
    private SystemLogStack $systemLogStack;
    private PaymentQueue $paymentQueue;
    private DocumentRequestQueue $documentRequestQueue;
    private EvaluationResponseStack $evaluationResponseStack;
    
    private function __construct() {
        $base = __DIR__ . '/data';
        if (!is_dir($base)) @mkdir($base, 0775, true);
        $this->activityStack = new ActivityStack(100, $base . '/activities.json');
        $this->notificationQueue = new NotificationQueue(200, $base . '/notifications.json');
        $this->gradeStack = new GradeHistoryStack();
        $this->assignmentQueue = new AssignmentQueue();
        $this->systemLogStack = new SystemLogStack(500, $base . '/system_logs.json');
        $this->paymentQueue = new PaymentQueue();
        $this->documentRequestQueue = new DocumentRequestQueue(200, $base . '/document_requests.json');
        $this->evaluationResponseStack = new EvaluationResponseStack(500, $base . '/evaluation_responses.json');
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getActivityStack(): ActivityStack {
        return $this->activityStack;
    }
    
    public function getNotificationQueue(): NotificationQueue {
        return $this->notificationQueue;
    }
    
    public function getGradeStack(): GradeHistoryStack {
        return $this->gradeStack;
    }
    
    public function getAssignmentQueue(): AssignmentQueue {
        return $this->assignmentQueue;
    }
    
    public function getSystemLogStack(): SystemLogStack {
        return $this->systemLogStack;
    }
    
    public function getPaymentQueue(): PaymentQueue {
        return $this->paymentQueue;
    }

    public function getDocumentRequestQueue(): DocumentRequestQueue {
        return $this->documentRequestQueue;
    }

    public function getEvaluationResponseStack(): EvaluationResponseStack {
        return $this->evaluationResponseStack;
    }
    
    // Helper methods for common operations
    public function logActivity(string $userEmail, string $action, string $details = ''): void {
        $activity = [
            'user_email' => $userEmail,
            'action' => $action,
            'details' => $details,
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        $this->activityStack->push($activity);
        $this->systemLogStack->log($activity);
    }
    
    public function addNotification(string $userEmail, string $title, string $message, string $type = 'info'): void {
        $notification = [
            'user_email' => $userEmail,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'timestamp' => time(),
            'read' => false
        ];
        $this->notificationQueue->enqueue($notification);
    }
    
    public function addSystemNotification(string $title, string $message, string $type = 'info', array $targetRoles = []): void {
        $notification = [
            'user_email' => 'system', // Special identifier for system notifications
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'timestamp' => time(),
            'read' => false,
            'target_roles' => $targetRoles, // Empty array means all roles
            'is_system' => true
        ];
        $this->notificationQueue->enqueue($notification);
    }

    public function markNotificationRead(int $index, string $userEmail): bool {
        return $this->notificationQueue->markReadByIndexForUser($index, $userEmail);
    }
    
    public function recordGrade(string $studentEmail, string $subject, float $grade, string $semester): void {
        $gradeRecord = [
            'student_email' => $studentEmail,
            'subject' => $subject,
            'grade' => $grade,
            'semester' => $semester,
            'timestamp' => time(),
            'recorded_by' => $_SESSION['user_email'] ?? 'system'
        ];
        $this->gradeStack->pushGrade($gradeRecord);
    }
    
    public function submitAssignment(string $studentEmail, string $assignmentId, string $content): void {
        $assignment = [
            'student_email' => $studentEmail,
            'assignment_id' => $assignmentId,
            'content' => $content,
            'submitted_at' => time(),
            'status' => 'submitted'
        ];
        $this->assignmentQueue->submitAssignment($assignment);
    }

    public function requestDocument(string $studentEmail, string $type, string $notes = ''): void {
        $req = [
            'student_email' => $studentEmail,
            'type' => $type,
            'notes' => $notes,
            'status' => 'pending',
            'requested_at' => time()
        ];
        $this->documentRequestQueue->enqueue($req);
        $this->addNotification('system', 'New Document Request', "$studentEmail requested $type", 'info');
    }

    public function addEvaluationResponse(string $studentEmail, string $teacherEmail, array $scores, string $comments = ''): void {
        $resp = [
            'student_email' => $studentEmail,
            'teacher_email' => $teacherEmail,
            'scores' => $scores,
            'comments' => $comments,
            'submitted_at' => time()
        ];
        $this->evaluationResponseStack->push($resp);
        $this->logActivity($studentEmail, 'submit_evaluation', "teacher=$teacherEmail");
    }
    
    public function addPayment(string $studentEmail, float $amount, string $method): void {
        $payment = [
            'student_email' => $studentEmail,
            'amount' => $amount,
            'method' => $method,
            'timestamp' => time(),
            'status' => 'pending',
            'processed_by' => $_SESSION['user_email'] ?? 'system'
        ];
        $this->paymentQueue->addPayment($payment);
    }
}
?>
