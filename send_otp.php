<?php
// Force no errors to be displayed
error_reporting(0);
ini_set('display_errors', 0);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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

try {
// Define development mode (set to true for testing, false in production)
define('DEVELOPMENT', true);    // Function to generate OTP
    function generateOTP($length = 6) {
        return str_pad(mt_rand(1, 999999), $length, '0', STR_PAD_LEFT);
    }
    
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
        
        // Check if email is provided
        if (isset($data['email'])) {
            $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            $name = isset($data['name']) ? htmlspecialchars($data['name']) : '';
            
            if (!$email) {
                output_json('error', 'Invalid email address');
            }
            
            // Generate OTP
            $otp = generateOTP();
            
            // Keep this commented out for now - we will send the actual email
            // if (defined('DEVELOPMENT') && DEVELOPMENT === true) {
            //     output_json([
            //         'success' => true, 
            //         'message' => 'Development mode: Email would be sent', 
            //         'test_otp' => $otp
            //     ]);
            // }
            
            // Include required files
            require_once __DIR__ . '/includes/database.php';
            require 'vendor/autoload.php';
            $smtp_config = include 'smtp_config.php';
            
            // Start session for storing email
            session_start();
            
            // Get database instance
            $db = Database::getInstance();
            
            // Delete any existing OTP
            $db->query("DELETE FROM otp_verifications WHERE email = :email", [
                'email' => $email
            ]);
            
            // Set expiration time - 10 minutes from now
            $expires_at = date('Y-m-d H:i:s', time() + (10 * 60));
            
            // Store OTP in database - explicitly cast boolean values
            $db->insert('otp_verifications', [
                'email' => $email,
                'otp' => $otp,
                'expires_at' => $expires_at,
                'verified' => false, // Explicit boolean value
                'verification_attempts' => (int)0 // Explicitly cast to integer
            ]);
            
            // Create new PHPMailer instance with exceptions disabled
            $mail = new PHPMailer(false); // false = don't throw exceptions
            
            // CRITICAL: Turn off debug for JSON response
            $mail->SMTPDebug = 0;
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtp_config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_config['smtp_username'];
            $mail->Password   = $smtp_config['smtp_password'];
            $mail->SMTPSecure = $smtp_config['smtp_secure'];
            $mail->Port       = $smtp_config['smtp_port'];
            
            // Add SSL options to help with connection
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Add timeout setting
            if (isset($smtp_config['smtp_timeout'])) {
                $mail->Timeout = $smtp_config['smtp_timeout'];
            }
            
            // Try to prevent emails from going to spam folder
            $mail->XMailer = 'FinCtrl Mailer';
            $mail->addCustomHeader('X-Application', 'FinCtrl Financial Management System');
            
            // Recipients
            $mail->setFrom($smtp_config['smtp_from_email'], $smtp_config['smtp_from_name']);
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your FinCtrl Verification Code';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h1 style="color: #4CAF50;">FinCtrl</h1>
                    </div>
                    <p>Hello ' . ($name ? htmlspecialchars($name) : 'there') . ',</p>
                    <p>Thank you for signing up for FinCtrl. Please use the verification code below to complete your registration:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <div style="font-size: 30px; font-weight: bold; letter-spacing: 5px; padding: 10px; background-color: #f5f5f5; border-radius: 5px;">' . $otp . '</div>
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you did not request this code, please ignore this email.</p>
                    <p style="margin-top: 30px; font-size: 12px; color: #777; text-align: center;">
                        &copy; ' . date("Y") . ' FinCtrl. All rights reserved.
                    </p>
                </div>
            ';
            $mail->AltBody = 'Your FinCtrl verification code is: ' . $otp . '. This code will expire in 10 minutes.';
            
            // Send email and check for errors
            if ($mail->send()) {
                output_json('success', 'OTP sent to email', [
                    'test_otp' => defined('DEVELOPMENT') && DEVELOPMENT === true ? $otp : null
                ]);
            } else {
                output_json('error', 'Could not send email: ' . $mail->ErrorInfo, [
                    'test_otp' => defined('DEVELOPMENT') && DEVELOPMENT === true ? $otp : null
                ]);
            }
        } else {
            output_json('error', 'Email is required');
        }
    } else {
        output_json('error', 'Invalid request method');
    }
} catch (Throwable $t) {
    // Catch any possible error
    output_json('error', 'A system error occurred: ' . $t->getMessage());
}
?>