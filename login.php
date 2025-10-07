<?php
// Start by buffering all output to prevent any accidental output
ob_start();

// Start session
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include the database class
require_once __DIR__ . '/includes/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Function to safely output JSON and exit
function output_json($data) {
    // Clear any previous output
    ob_clean();

    // Output the JSON
    echo json_encode($data);

    // End the output buffer and exit
    ob_end_flush();
    exit;
}

// Check if it is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Check if required fields are provided
    if (isset($data['email']) && isset($data['password'])) {
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $password = $data['password'];
        $remember = isset($data['remember']) ? $data['remember'] : false;
        
        if (!$email) {
            output_json(['success' => false, 'message' => 'Invalid email address']);
        }

        try {
            // Get database instance
            $db = Database::getInstance();
            
            // Get user by email
            $user = $db->fetchOne("SELECT * FROM users WHERE email = :email", [
                'email' => $email
            ]);
            
            // Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                // Update last login timestamp
                $db->update(
                    'users',
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = :id',
                    ['id' => $user['id']]
                );
                
                // Create a user session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'logged_in' => true
                ];
                
                // Set remember-me cookie if selected
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                output_json(['success' => true, 'redirect' => 'dashboard.html']);
            } else {
                // Fall back to test accounts for development (remove in production)
                if ($email === 'test@example.com' && $password === 'password123') {
                    $_SESSION['user'] = [
                        'id' => 0,
                        'name' => 'Test User',
                        'email' => $email,
                        'logged_in' => true
                    ];
                    output_json(['success' => true, 'redirect' => 'dashboard.html']);
                } else if ($password === 'test123') {
                    // For easier testing, accept any email with password "test123"
                    $name = explode('@', $email)[0]; // Use part before @ as name
                    $name = ucfirst($name); // Capitalize first letter
                    
                    $_SESSION['user'] = [
                        'id' => 0,
                        'name' => $name,
                        'email' => $email,
                        'logged_in' => true
                    ];
                    
                    output_json(['success' => true, 'redirect' => 'dashboard.html']);
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