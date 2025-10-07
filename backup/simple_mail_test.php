<?php
// Simple PHPMailer Test without complex output

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Show errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple PHPMailer Test</h1>";

try {
    // Load autoloader
    require 'vendor/autoload.php';
    
    // Get SMTP configuration
    $smtp_config = include 'smtp_config.php';
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 2;
    
    // Tell PHPMailer to use SMTP
    $mail->isSMTP();
    
    // Set the hostname of the mail server
    $mail->Host = $smtp_config['smtp_host'];
    
    // Set the SMTP port number
    $mail->Port = $smtp_config['smtp_port'];
    
    // Set the encryption mechanism to use
    $mail->SMTPSecure = $smtp_config['smtp_secure'];
    
    // Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    
    // Username to use for SMTP authentication
    $mail->Username = $smtp_config['smtp_username'];
    
    // Password to use for SMTP authentication
    $mail->Password = $smtp_config['smtp_password'];
    
    // Timeout setting
    $mail->Timeout = $smtp_config['smtp_timeout'];
    
    // Skip SSL certificate verification (not recommended for production)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Set who the message is to be sent from
    $mail->setFrom($smtp_config['smtp_from_email'], $smtp_config['smtp_from_name']);
    
    // Set who the message is to be sent to
    $mail->addAddress($smtp_config['smtp_username']);
    
    // Set the subject line
    $mail->Subject = 'Simple PHPMailer Test';
    
    // Set the body of the message
    $mail->isHTML(true);
    $mail->Body = '<h1>Test Email</h1><p>This is a test email sent using PHPMailer.</p>';
    $mail->AltBody = 'This is a test email sent using PHPMailer.';
    
    // Send the message
    if (!$mail->send()) {
        echo "<p style='color: red;'><strong>Mailer Error:</strong> " . $mail->ErrorInfo . "</p>";
    } else {
        echo "<p style='color: green;'><strong>Message sent successfully!</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>