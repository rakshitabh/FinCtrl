<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/includes/database.php';

function respond($ok, $msg = '', $extra = []){
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// Ensure user is logged in (project stores as $_SESSION['user']['id'])
if (!isset($_SESSION['user']['id'])) {
    respond(false, 'Not authenticated');
}

$userId = (int)$_SESSION['user']['id'];

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    $params = ['uid' => $userId];

    // IMPORTANT: Delete in FK-safe order
    // 1) Transfers first (references transactions/accounts/users)
    $db->delete('transfers', 'user_id = :uid', $params);
    // 2) Transactions (references accounts/categories/users)
    $db->delete('transactions', 'user_id = :uid', $params);
    // 3) Bills (references users/accounts/categories)
    $db->delete('bills', 'user_id = :uid', $params);
    // 4) Budgets (references users/categories)
    $db->delete('budgets', 'user_id = :uid', $params);
    // 5) Savings goals
    $db->delete('savings_goals', 'user_id = :uid', $params);
    // 6) Categories (user-defined)
    $db->delete('categories', 'user_id = :uid', $params);
    // 7) Accounts (must be after transactions/transfers)
    $db->delete('accounts', 'user_id = :uid', $params);
    // 8) User profile (avatar etc.)
    $db->delete('user_profiles', 'user_id = :uid', $params);

    // Finally delete user
    $db->delete('users', 'id = :uid', $params);

    $db->commit();

    // End session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    respond(true, 'Account deleted');
} catch (Throwable $e) {
    try { if (isset($db)) { $db->rollback(); } } catch (Throwable $ignored) {}
    http_response_code(500);
    // Surface error to help diagnose in dev; change to generic message if needed
    respond(false, 'Failed to delete account: ' . $e->getMessage());
}
