<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once __DIR__ . '/includes/database.php';
header('Content-Type: application/json');

function output_json($data) {
    ob_clean();
    echo json_encode($data);
    ob_end_flush();
    exit;
}

function needs_onboarding($db, $userId) {
    // Check profile exists and has currency and email preference
    $profile = $db->fetchOne("SELECT currency, notification_preferences FROM user_profiles WHERE user_id = :id", ['id' => $userId]);
    $needsProfile = false;
    if (!$profile) {
        $needsProfile = true;
    } else {
        $prefs = [];
        if (isset($profile['notification_preferences']) && $profile['notification_preferences'] !== null) {
            $decoded = json_decode($profile['notification_preferences'], true);
            if (is_array($decoded)) $prefs = $decoded;
        }
        if (empty($profile['currency']) || !array_key_exists('email', $prefs)) {
            $needsProfile = true;
        }
    }

    // Check a monthly budget exists for current month
    $start = date('Y-m-01');
    $end = date('Y-m-t');
    $budget = $db->fetchOne(
        "SELECT id FROM budgets WHERE user_id = :id AND period = 'monthly' AND start_date >= :start AND start_date <= :end LIMIT 1",
        ['id' => $userId, 'start' => $start, 'end' => $end]
    );
    $needsBudget = !$budget;

    return $needsProfile || $needsBudget;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['email']) && isset($data['password'])) {
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $password = $data['password'];
        $remember = isset($data['remember']) ? $data['remember'] : false;
        if (!$email) {
            output_json(['success' => false, 'message' => 'Invalid email address']);
        }
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
            if ($user && password_verify($password, $user['password'])) {
                $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'logged_in' => true
                ];
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                }
                $redirect = needs_onboarding($db, $user['id']) ? 'onboarding.html' : 'dashboard.html';
                output_json(['success' => true, 'redirect' => $redirect]);
            } else {
                // Dev fallbacks
                if ($email === 'test@example.com' && $password === 'password123') {
                    $_SESSION['user'] = ['id' => 0, 'name' => 'Test User', 'email' => $email, 'logged_in' => true];
                    output_json(['success' => true, 'redirect' => 'onboarding.html']);
                } elseif ($password === 'test123') {
                    $name = ucfirst(explode('@', $email)[0]);
                    $_SESSION['user'] = ['id' => 0, 'name' => $name, 'email' => $email, 'logged_in' => true];
                    output_json(['success' => true, 'redirect' => 'onboarding.html']);
                } else {
                    output_json(['success' => false, 'message' => 'Invalid email or password']);
                }
            }
        } catch (Exception $e) {
            output_json(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
        }
    } else {
        output_json(['success' => false, 'message' => 'Email and password are required']);
    }
} else {
    output_json(['success' => false, 'message' => 'Invalid request method']);
}
?>