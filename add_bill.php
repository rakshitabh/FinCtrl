<?php
// UTF-8 without BOM
ob_start();
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    $out = ob_get_clean();
    if ($out && trim($out) !== '') {
        $data['debug'] = $out;
    }
    echo json_encode($data);
    exit;
}

try {
    if (!isset($_SESSION['user']['id'])) {
        respond(['success' => false, 'message' => 'Not authenticated'], 401);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        respond(['success' => false, 'message' => 'Invalid JSON body'], 400);
    }

    $name = isset($input['name']) ? trim((string)$input['name']) : '';
    $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
    $dueDate = $input['dueDate'] ?? null;
    $remind = isset($input['remind']) ? (bool)$input['remind'] : false;

    if ($name === '' || $amount <= 0 || !$dueDate) {
        respond(['success' => false, 'message' => 'Missing or invalid fields'], 422);
    }

    try {
        $dt = new DateTime($dueDate);
        $dueDate = $dt->format('Y-m-d');
        // Must be strictly after today
        $today = (new DateTime('today'))->format('Y-m-d');
        if ($dueDate <= $today) {
            respond(['success' => false, 'message' => 'Due date must be after today'], 422);
        }
    } catch (Throwable $e) {
        respond(['success' => false, 'message' => 'Invalid date format'], 422);
    }

    $db = Database::getInstance();
    $userId = (int)$_SESSION['user']['id'];

    $billId = (int)$db->insert('bills', [
        'user_id' => $userId,
        'name' => $name,
        'amount' => $amount,
        'due_date' => $dueDate,
        'is_recurring' => false,
    ]);

    respond(['success' => true, 'id' => $billId, 'userId' => $userId, 'remind' => $remind, 'message' => 'Bill added']);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()], 500);
}
