<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Start session to store OTP
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
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // Generate OTP
        $otp = generateOTP();
        
        // Store OTP in session (in a real app, you would use a database with expiration)
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_expires'] = time() + (10 * 60); // OTP valid for 10 minutes
        
        // Include PHPMailer autoloader
        require 'vendor/autoload.php';

        // Load SMTP configuration
        $smtp_config = include 'smtp_config.php';

        // Create new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = $smtp_config['smtp_debug'] ?? 0; // Use debug level from config or default to 0
            $mail->isSMTP();                           // Use SMTP
            $mail->Host       = $smtp_config['smtp_host'];      // SMTP server
            $mail->SMTPAuth   = true;                  // Enable SMTP authentication
            $mail->Username   = $smtp_config['smtp_username']; // SMTP username
            $mail->Password   = $smtp_config['smtp_password'];  // SMTP password
            $mail->SMTPSecure = $smtp_config['smtp_secure'];   // Enable TLS encryption
            $mail->Port       = $smtp_config['smtp_port'];     // TCP port to connect to
            
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
            
            $mail->send();
            $response = ['success' => true, 'message' => 'OTP sent to email'];
            
            // For development only - remove in production
            $response['test_otp'] = $otp;
            
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Could not send email. Error: ' . $mail->ErrorInfo];
        }
        
        echo json_encode($response);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>