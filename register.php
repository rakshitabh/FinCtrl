<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/includes/database.php';

// Check if it is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Check if required fields are provided
    if (isset($data['fullName']) && isset($data['email']) && isset($data['password'])) {
        $fullName = htmlspecialchars($data['fullName']);
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $password = $data['password'];

        try {
            // Get database instance
            $db = Database::getInstance();

            // Check if email has been verified through OTP
            $otpRecord = $db->fetchOne(
                "SELECT verified FROM otp_verifications WHERE email = :email ORDER BY created_at DESC LIMIT 1",
                ['email' => $email]
            );

            if (!$otpRecord || $otpRecord['verified'] !== true) {
                echo json_encode(['success' => false, 'message' => 'Email verification required']);
                exit;
            }

            // Check if user already exists
            $existingUser = $db->fetchOne(
                "SELECT id FROM users WHERE email = :email",
                ['email' => $email]
            );

            if ($existingUser) {
                echo json_encode(['success' => false, 'message' => 'User with this email already exists']);
                exit;
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into database
            $userId = $db->insert('users', [
                'name' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'email_verified' => true
            ]);

            // Create a user session
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $fullName,
                'email' => $email,
                'logged_in' => true
            ];

            echo json_encode(['success' => true, 'message' => 'Registration successful', 'redirect' => 'dashboard.html']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>