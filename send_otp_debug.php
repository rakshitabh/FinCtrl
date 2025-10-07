<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Start output buffering to capture any errors
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

try {
    // Define development mode (set to true for testing, false in production)
    define('DEVELOPMENT', true);
    
    // Function to generate OTP
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
            // Debug input
            error_log("JSON input: " . print_r($data, true));
        } else {
            // Handle form data
            $data = $_POST;
            // Debug input
            error_log("POST input: " . print_r($data, true));
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
            error_log("Generated OTP: " . $otp . " for email: " . $email);
            
            // Include required files
            require_once __DIR__ . '/includes/database.php';
            require 'vendor/autoload.php';
            $smtp_config = include 'smtp_config.php';
            
            // Debug SMTP config
            error_log("SMTP Config: " . print_r($smtp_config, true));
            
            // Start session for storing email
            session_start();
            
            try {
                // Get database instance
                $db = Database::getInstance();
                error_log("Database instance created successfully");
                
                // Delete any existing OTP
                try {
                    $db->query("DELETE FROM otp_verifications WHERE email = :email", [
                        'email' => $email
                    ]);
                    error_log("Deleted existing OTP records for: " . $email);
                } catch (Exception $e) {
                    error_log("Error deleting existing OTP: " . $e->getMessage());
                }
                
                // Set expiration time - 10 minutes from now
                $expires_at = date('Y-m-d H:i:s', time() + (10 * 60));
                
                // Debug data to be inserted
                $data_to_insert = [
                    'email' => $email,
                    'otp' => $otp,
                    'expires_at' => $expires_at,
                    'verified' => false,
                    'verification_attempts' => (int)0
                ];
                error_log("Data to insert into otp_verifications: " . print_r($data_to_insert, true));
                
                // Store OTP in database - explicitly cast boolean values
                try {
                    $otp_id = $db->insert('otp_verifications', $data_to_insert);
                    error_log("OTP stored successfully in database with ID: " . $otp_id);
                } catch (Exception $e) {
                    error_log("Error storing OTP in database: " . $e->getMessage());
                    error_log("SQL Error: " . $e->getCode());
                    throw new Exception("Database error: " . $e->getMessage(), 500);
                }
                
                // Create new PHPMailer instance with exceptions enabled for debugging
                $mail = new PHPMailer(true); // true = throw exceptions
                error_log("PHPMailer instance created");
                
                // Turn on debug for testing
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer: $str");
                };
                
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
                    error_log("Email sent successfully to: " . $email);
                    output_json('success', 'OTP sent to email', [
                        'test_otp' => defined('DEVELOPMENT') && DEVELOPMENT === true ? $otp : null
                    ]);
                } else {
                    error_log("Email sending failed: " . $mail->ErrorInfo);
                    output_json('error', 'Could not send email: ' . $mail->ErrorInfo, [
                        'test_otp' => defined('DEVELOPMENT') && DEVELOPMENT === true ? $otp : null
                    ]);
                }
            } catch (Exception $e) {
                error_log("Exception occurred: " . $e->getMessage());
                output_json('error', 'An error occurred: ' . $e->getMessage(), [
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
    error_log("Uncaught error: " . $t->getMessage());
    output_json('error', 'A system error occurred: ' . $t->getMessage());
}
?>