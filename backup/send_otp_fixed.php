<?php
// Make sure nothing is output before our JSON
ob_start();

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Define development mode for testing
define('DEVELOPMENT', true);

// Include the database class
require_once __DIR__ . '/includes/database.php';

// Start session (optional - we're moving to database but might need session for other purposes)
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to generate OTP
function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[rand(0, 9)];
    }
    return $otp;
}

// Function to clean up output buffer and send JSON response
function output_json($data) {
    // Clean any previous output that might corrupt JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set proper content type
    header('Content-Type: application/json');
    
    // Send JSON response
    echo json_encode($data);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if email is provided
    if (isset($data['email'])) {
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $name = isset($data['name']) ? htmlspecialchars($data['name']) : '';
        
        if (!$email) {
            output_json(['success' => false, 'message' => 'Invalid email address']);
        }
        
        // Generate OTP
        $otp = generateOTP();
        
        try {
            // Get database instance
            $db = Database::getInstance();
            
            // Check if there's an existing OTP for this email and delete it
            $db->query("DELETE FROM otp_verifications WHERE email = :email", [
                'email' => $email
            ]);
            
            // Set expiration time - 10 minutes from now
            $expires_at = date('Y-m-d H:i:s', time() + (10 * 60));
            
            // Store OTP in database
            $db->insert('otp_verifications', [
                'email' => $email,
                'otp' => $otp,
                'expires_at' => $expires_at,
                'verified' => false,
                'verification_attempts' => 0
            ]);
            
            // Also store email in session for convenience
            $_SESSION['otp_email'] = $email;
            
            // Include PHPMailer autoloader
            require 'vendor/autoload.php';
    
            // Load SMTP configuration
            $smtp_config = include 'smtp_config.php';
    
            // Create new PHPMailer instance
            $mail = new PHPMailer(true);
            
            // Turn off debug for JSON response (critical)
            $mail->SMTPDebug = 0;
            
            // Server settings
            $mail->isSMTP();                           // Use SMTP
            $mail->Host       = $smtp_config['smtp_host'];      // SMTP server
            $mail->SMTPAuth   = true;                  // Enable SMTP authentication
            $mail->Username   = $smtp_config['smtp_username']; // SMTP username
            $mail->Password   = $smtp_config['smtp_password'];  // SMTP password
            $mail->SMTPSecure = $smtp_config['smtp_secure'];   // Enable TLS encryption
            $mail->Port       = $smtp_config['smtp_port'];     // TCP port to connect to
            
            // Add SSL options to help with connection
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
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
            $mail->Body    = '
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
            
            // Send email
            $mail->send();
            
            // Prepare success response
            $response = ['success' => true, 'message' => 'OTP sent to email'];
            
            // For development only - remove in production
            if (defined('DEVELOPMENT') && DEVELOPMENT === true) {
                $response['test_otp'] = $otp;
            }
            
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Could not send email or store OTP. Error: ' . $e->getMessage()];
        }
        
        // Output the JSON response
        output_json($response);
    } else {
        output_json(['success' => false, 'message' => 'Email is required']);
    }
} else {
    output_json(['success' => false, 'message' => 'Invalid request method']);
}
?>