<?php
// Include the database class
require_once __DIR__ . '/includes/database.php';

// Start session (optional - we're moving to database but might need session for other purposes)
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to clean up output buffer and send JSON response
function output_json($data) {
    // Clean any previous output that might corrupt JSON
    if (ob_get_length()) ob_clean();
    
    // Send JSON response
    echo json_encode($data);
    exit;
}

// Maximum allowed verification attempts
define('MAX_VERIFICATION_ATTEMPTS', 5);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if OTP is provided
    if (isset($data['otp']) && isset($data['email'])) {
        $submitted_otp = $data['otp'];
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            output_json(['success' => false, 'message' => 'Invalid email address']);
        }
        
        // Get database instance
        $db = Database::getInstance();
        
        try {
            // Get OTP record from database
            $otp_record = $db->fetchOne(
                "SELECT * FROM otp_verifications WHERE email = :email ORDER BY created_at DESC LIMIT 1",
                ['email' => $email]
            );
            
            if (!$otp_record) {
                output_json(['success' => false, 'message' => 'OTP not found. Please request a new OTP.']);
            }
            
            // Check if OTP has expired
            $current_time = date('Y-m-d H:i:s');
            if ($current_time > $otp_record['expires_at']) {
                output_json(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
            }
            
            // Check if already verified
            if ($otp_record['verified']) {
                output_json(['success' => false, 'message' => 'OTP has already been verified.']);
            }
            
            // Check if max attempts exceeded
            if ($otp_record['verification_attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
                output_json(['success' => false, 'message' => 'Maximum verification attempts exceeded. Please request a new OTP.']);
            }
            
            // Increment attempt counter
            $db->update(
                'otp_verifications',
                ['verification_attempts' => $otp_record['verification_attempts'] + 1],
                'id = :id',
                ['id' => $otp_record['id']]
            );
            
            // Verify OTP
            if ($submitted_otp === $otp_record['otp']) {
                // Mark OTP as verified
                $db->update(
                    'otp_verifications',
                    ['verified' => true],
                    'id = :id',
                    ['id' => $otp_record['id']]
                );
                
                // Keep email in session to use later for registration
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_verified'] = true;
                
                output_json(['success' => true, 'message' => 'OTP verified successfully', 'email' => $email]);
            } else {
                // Calculate remaining attempts
                $remaining_attempts = MAX_VERIFICATION_ATTEMPTS - ($otp_record['verification_attempts'] + 1);
                $message = 'Invalid OTP. Please try again.';
                
                if ($remaining_attempts > 0) {
                    $message .= ' ' . $remaining_attempts . ' attempts remaining.';
                }
                
                output_json(['success' => false, 'message' => $message]);
            }
        } catch (Exception $e) {
            output_json(['success' => false, 'message' => 'Error verifying OTP: ' . $e->getMessage()]);
        }
    } else {
        output_json(['success' => false, 'message' => 'OTP and email are required']);
    }
} else {
    output_json(['success' => false, 'message' => 'Invalid request method']);
}
?>