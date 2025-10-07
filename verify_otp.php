<?php
// Force no errors to be displayed
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering at the very beginning
ob_start();

// Function to clean all output and send JSON
function output_json($status, $message = '', $extra = []) {
    // Discard anything that might have been output before
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start fresh buffer
    ob_start();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Handle both array and string/status inputs
    if (is_array($status)) {
        $response = $status;
    } else {
        $response = array_merge(['status' => $status, 'message' => $message], $extra);
    }
    
    // Output JSON
    echo json_encode($response);
    
    // Get the clean JSON output
    $clean_output = ob_get_clean();
    
    // Output the clean JSON and exit
    echo $clean_output;
    exit;
}

// Set up exception handler to catch any uncaught exceptions
set_exception_handler(function($e) {
    output_json('error', 'An error occurred: ' . $e->getMessage());
});

// Set up error handler to catch any PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    output_json('error', 'A system error occurred');
});

// Maximum allowed verification attempts
define('MAX_VERIFICATION_ATTEMPTS', 5);

try {
    // Include the database class
    require_once __DIR__ . '/includes/database.php';
    
    // Start session
    session_start();
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get data from request (support both JSON and form data)
        $data = [];
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
        } else {
            // Handle form data
            $data = $_POST;
        }
        
        // Check if OTP and email are provided
        if (isset($data['otp']) && isset($data['email'])) {
            $submitted_otp = trim($data['otp']);
            $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                output_json('error', 'Invalid email address');
            }
            
            // Get database instance
            $db = Database::getInstance();
            
            // Get OTP record from database
            $otp_record = $db->fetchOne(
                "SELECT * FROM otp_verifications WHERE email = :email ORDER BY created_at DESC LIMIT 1",
                ['email' => $email]
            );
            
            if (!$otp_record) {
                output_json('error', 'OTP not found. Please request a new OTP.');
            }
            
            // Check if OTP has expired
            $current_time = date('Y-m-d H:i:s');
            if ($current_time > $otp_record['expires_at']) {
                output_json('error', 'OTP has expired. Please request a new one.');
            }
            
            // Check if already verified
            if ($otp_record['verified']) {
                output_json('error', 'OTP has already been verified.');
            }
            
            // Check if max attempts exceeded
            if ($otp_record['verification_attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
                output_json('error', 'Maximum verification attempts exceeded. Please request a new OTP.');
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
                // Mark OTP as verified - explicitly use boolean true
                $db->update(
                    'otp_verifications',
                    ['verified' => true], // Explicit boolean value
                    'id = :id',
                    ['id' => (int)$otp_record['id']] // Explicitly cast ID to integer
                );
                
                // Keep email in session to use later for registration
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_verified'] = true;
                
                output_json('success', 'OTP verified successfully', ['email' => $email]);
            } else {
                // Calculate remaining attempts
                $remaining_attempts = MAX_VERIFICATION_ATTEMPTS - ($otp_record['verification_attempts']);
                $message = 'Invalid OTP. Please try again.';
                
                if ($remaining_attempts > 0) {
                    $message .= ' ' . $remaining_attempts . ' attempts remaining.';
                }
                
                output_json('error', $message);
            }
        } else {
            output_json('error', 'OTP and email are required');
        }
    } else {
        output_json('error', 'Invalid request method');
    }
} catch (Throwable $t) {
    // Catch any possible error
    output_json('error', 'A system error occurred: ' . $t->getMessage());
}
?>