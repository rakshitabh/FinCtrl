<?php
// UTF-8 without BOM

ob_start();
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/includes/database.php';

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    // Clean any accidental output before JSON
    $out = ob_get_clean();
    if ($out && trim($out) !== '') {
        // Ignore stray output but keep it in debug field
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

    $date = $input['date'] ?? null;
    $categoryId = isset($input['categoryId']) ? (int)$input['categoryId'] : 0;
    $amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
    $description = isset($input['description']) ? trim((string)$input['description']) : null;

    if (!$date || $categoryId <= 0 || $amount <= 0) {
        respond(['success' => false, 'message' => 'Missing or invalid fields'], 422);
    }

    // Normalize/validate date
    try {
        $dt = new DateTime($date);
        $date = $dt->format('Y-m-d');
    } catch (Throwable $e) {
        respond(['success' => false, 'message' => 'Invalid date format'], 422);
    }

    $db = Database::getInstance();
    $userId = (int)$_SESSION['user']['id'];

    // Ensure a usable account exists; pick the first active account or create a default Cash account
    $account = $db->fetchOne(
        'SELECT id, currency FROM accounts WHERE user_id = :uid AND (is_active = true OR is_active IS NULL) ORDER BY id ASC LIMIT 1',
        ['uid' => $userId]
    );

    if ($account) {
        $accountId = (int)$account['id'];
        $currency = $account['currency'] ?? null;
    } else {
        // Try to use user's preferred currency, else USD
        $profile = $db->fetchOne('SELECT currency FROM user_profiles WHERE user_id = :uid', ['uid' => $userId]);
        $currency = $profile['currency'] ?? 'USD';
        $accountId = (int)$db->insert('accounts', [
            'user_id' => $userId,
            'name' => 'Cash',
            'type' => 'cash',
            'currency' => $currency,
            'balance' => 0.00,
            'is_active' => true,
        ]);
    }

    // Optional: basic category ownership check (category can be default or user-owned)
    $cat = $db->fetchOne(
        'SELECT id FROM categories WHERE id = :cid AND (user_id = :uid OR user_id IS NULL)',
        ['cid' => $categoryId, 'uid' => $userId]
    );
    if (!$cat) {
        respond(['success' => false, 'message' => 'Invalid category'], 422);
    }

    $txId = (int)$db->insert('transactions', [
        'user_id' => $userId,
        'account_id' => $accountId,
        'category_id' => $categoryId,
        'amount' => $amount,
        'type' => 'expense',
        'description' => $description,
        'transaction_date' => $date,
    ]);

    // Optionally update account balance (reduce by expense amount) if your app tracks it
    try {
        $db->query('UPDATE accounts SET balance = COALESCE(balance,0) - :amt WHERE id = :aid', ['amt' => $amount, 'aid' => $accountId]);
    } catch (Throwable $e) {
        // Best-effort; ignore if schema differs
    }

    respond(['success' => true, 'id' => $txId, 'message' => 'Expense added']);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()], 500);
}

