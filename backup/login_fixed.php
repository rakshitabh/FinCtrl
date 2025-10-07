<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's a POST request
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
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // For this example, we'll simulate successful login for certain credentials
        if ($email === 'test@example.com' && $password === 'password123') {
            // Create a user session
            $_SESSION['user'] = [
                'name' => 'Test User',
                'email' => $email,
                'logged_in' => true
            ];
            
            // Set remember-me cookie if selected
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            }
            
            echo json_encode(['success' => true, 'redirect' => 'dashboard.html']);
            exit;
        } else {
            // For easier testing, accept any email with password "test123"
            if ($password === 'test123') {
                // Create a user session with the provided email
                $name = explode('@', $email)[0]; // Use part before @ as name
                $name = ucfirst($name); // Capitalize first letter
                
                $_SESSION['user'] = [
                    'name' => $name,
                    'email' => $email,
                    'logged_in' => true
                ];
                
                echo json_encode(['success' => true, 'redirect' => 'dashboard.html']);
                exit;
            }
            
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}