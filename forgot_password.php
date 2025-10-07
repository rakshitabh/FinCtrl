<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Function to generate a reset token
function generateResetToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if email is provided
    if (isset($data['email'])) {
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // In a real application, you would:
        // 1. Check if the email exists in your database
        // 2. Generate a reset token and store it in the database with an expiry time
        // 3. Send an email with a link containing the token
        
        // For this example, we'll simulate sending a reset link
        $resetToken = generateResetToken();
        $resetLink = 'https://yourwebsite.com/reset-password.php?token=' . $resetToken;
        
        // Include autoloader
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
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - FinCtrl';
            $mail->Body    = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h1 style="color: #4CAF50;">FinCtrl</h1>
                    </div>
                    <p>Hello,</p>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $resetLink . '" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Reset Password</a>
                    </div>
                    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>This link will expire in 30 minutes.</p>
                    <p style="margin-top: 30px; font-size: 12px; color: #777; text-align: center;">
                        &copy; ' . date("Y") . ' FinCtrl. All rights reserved.
                    </p>
                </div>
            ';
            
            $mail->send();
            $response = ['success' => true, 'message' => 'Password reset link sent to email'];
            
            // For development only - remove in production
            $response['test_reset_link'] = $resetLink;
            
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