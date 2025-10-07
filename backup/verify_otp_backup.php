<?php
// Start session to access stored OTP
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if OTP is provided
    if (isset($data['otp'])) {
        $submitted_otp = $data['otp'];
        
        // Check if OTP is set in session
        if (
            isset($_SESSION['otp']) && 
            isset($_SESSION['otp_expires']) && 
            isset($_SESSION['otp_email'])
        ) {
            $stored_otp = $_SESSION['otp'];
            $expiry_time = $_SESSION['otp_expires'];
            
            // Check if OTP has expired
            if (time() > $expiry_time) {
                echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
                exit;
            }
            
            // Verify OTP
            if ($submitted_otp === $stored_otp) {
                // OTP is valid
                // In a real application, you would mark the user as verified in your database
                
                // Clear OTP data from session
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expires']);
                
                // Keep email in session to use later for registration
                $email = $_SESSION['otp_email'];
                
                echo json_encode(['success' => true, 'message' => 'OTP verified successfully', 'email' => $email]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'OTP session not found. Please request a new OTP.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'OTP is required']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>