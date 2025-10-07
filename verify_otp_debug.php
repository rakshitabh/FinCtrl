<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Maximum allowed verification attempts
define('MAX_VERIFICATION_ATTEMPTS', 5);

try {
    // Include the database class
    require_once __DIR__ . '/includes/database.php';
    
    error_log("Starting OTP verification process");
    
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
            error_log("JSON input: " . print_r($data, true));
        } else {
            // Handle form data
            $data = $_POST;
            error_log("POST input: " . print_r($data, true));
        }
        
        // Check if OTP and email are provided
        if (isset($data['otp']) && isset($data['email'])) {
            $submitted_otp = trim($data['otp']);
            $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            
            error_log("Submitted OTP: $submitted_otp for email: $email");
            
            if (!$email) {
                error_log("Invalid email address: " . $data['email']);
                output_json('error', 'Invalid email address');
            }
            
            try {
                // Get database instance
                $db = Database::getInstance();
                error_log("Database instance created successfully");
                
                // Get OTP record from database
                $otp_record = $db->fetchOne(
                    "SELECT * FROM otp_verifications WHERE email = :email ORDER BY created_at DESC LIMIT 1",
                    ['email' => $email]
                );
                
                error_log("OTP record found: " . ($otp_record ? "Yes" : "No"));
                if ($otp_record) {
                    error_log("OTP record details: " . print_r($otp_record, true));
                }
                
                if (!$otp_record) {
                    error_log("No OTP record found for email: $email");
                    output_json('error', 'OTP not found. Please request a new OTP.');
                }
                
                // Check if OTP has expired
                $current_time = date('Y-m-d H:i:s');
                if ($current_time > $otp_record['expires_at']) {
                    error_log("OTP expired. Current time: $current_time, Expiry: " . $otp_record['expires_at']);
                    output_json('error', 'OTP has expired. Please request a new one.');
                }
                
                // Check if already verified
                if ($otp_record['verified']) {
                    error_log("OTP already verified for email: $email");
                    output_json('error', 'OTP has already been verified.');
                }
                
                // Check if max attempts exceeded
                if ($otp_record['verification_attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
                    error_log("Max verification attempts exceeded for email: $email");
                    output_json('error', 'Maximum verification attempts exceeded. Please request a new OTP.');
                }
                
                // Increment attempt counter
                try {
                    $db->update(
                        'otp_verifications',
                        ['verification_attempts' => $otp_record['verification_attempts'] + 1],
                        'id = :id',
                        ['id' => $otp_record['id']]
                    );
                    error_log("Incremented verification attempts to " . ($otp_record['verification_attempts'] + 1));
                } catch (Exception $e) {
                    error_log("Error updating verification attempts: " . $e->getMessage());
                }
                
                // Verify OTP
                if ($submitted_otp === $otp_record['otp']) {
                    error_log("OTP verified successfully for email: $email");
                    
                    // Mark OTP as verified - explicitly use boolean true
                    try {
                        $db->update(
                            'otp_verifications',
                            ['verified' => true], // Explicit boolean value
                            'id = :id',
                            ['id' => (int)$otp_record['id']] // Explicitly cast ID to integer
                        );
                        error_log("Updated OTP record as verified");
                    } catch (Exception $e) {
                        error_log("Error updating OTP verification status: " . $e->getMessage());
                    }
                    
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
                    
                    error_log("Invalid OTP submitted. Expected: " . $otp_record['otp'] . ", Received: $submitted_otp");
                    error_log($message);
                    
                    output_json('error', $message);
                }
            } catch (Exception $e) {
                error_log("Database error during verification: " . $e->getMessage());
                output_json('error', 'An error occurred during verification: ' . $e->getMessage());
            }
        } else {
            error_log("Missing OTP or email in request");
            output_json('error', 'OTP and email are required');
        }
    } else {
        error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        output_json('error', 'Invalid request method');
    }
} catch (Throwable $t) {
    // Catch any possible error
    error_log("Uncaught error: " . $t->getMessage());
    output_json('error', 'A system error occurred: ' . $t->getMessage());
}
?>