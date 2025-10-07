<?php
// Direct test script for PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set to display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHPMailer Direct Test</h1>";
echo "<pre>";

try {
    // Check if PHPMailer is installed
    echo "Checking for PHPMailer...\n";
    require 'vendor/autoload.php';
    echo "✓ PHPMailer autoload found\n\n";
    
    // Load SMTP configuration
    echo "Loading SMTP configuration...\n";
    $smtp_config = include 'smtp_config.php';
    echo "✓ SMTP config loaded\n";
    echo "Host: " . $smtp_config['smtp_host'] . "\n";
    echo "Port: " . $smtp_config['smtp_port'] . "\n";
    echo "Username: " . $smtp_config['smtp_username'] . "\n";
    echo "From Email: " . $smtp_config['smtp_from_email'] . "\n\n";
    
    // Creating PHPMailer instance
    echo "Creating PHPMailer instance...\n";
    $mail = new PHPMailer(true);
    echo "✓ PHPMailer instance created\n\n";
    
    // Enable verbose debug output
    echo "Setting up SMTP connection with debug level " . $smtp_config['smtp_debug'] . "...\n";
    $mail->SMTPDebug = $smtp_config['smtp_debug'];
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG: $str\n";
    };
    
    // Server settings
    echo "Configuring server settings...\n";
    $mail->isSMTP();
    $mail->Host       = $smtp_config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_config['smtp_username'];
    $mail->Password   = $smtp_config['smtp_password'];
    $mail->SMTPSecure = $smtp_config['smtp_secure'];
    $mail->Port       = $smtp_config['smtp_port'];
    $mail->Timeout    = $smtp_config['smtp_timeout'];
    
    // Additional settings that might help
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    echo "✓ Server settings configured\n\n";
    
    // Set additional headers to help prevent emails from being marked as spam
    echo "Setting email headers...\n";
    $mail->XMailer = 'FinCtrl Direct Test';
    $mail->addCustomHeader('X-Application', 'FinCtrl Email Test');
    echo "✓ Headers set\n\n";
    
    // Sender and recipient
    echo "Setting sender and recipient...\n";
    $mail->setFrom($smtp_config['smtp_from_email'], $smtp_config['smtp_from_name']);
    $test_email = isset($_GET['email']) ? $_GET['email'] : $smtp_config['smtp_username'];
    $mail->addAddress($test_email);
    echo "✓ Sender and recipient set\n\n";
    
    // Content
    echo "Setting email content...\n";
    $mail->isHTML(true);
    $mail->Subject = 'FinCtrl Direct PHPMailer Test';
    $mail->Body    = '<h1>This is a direct test from PHPMailer</h1>
                       <p>If you received this email, your email configuration is working correctly.</p>
                       <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>';
    $mail->AltBody = 'This is a direct test from PHPMailer. If you received this email, your configuration is working.';
    echo "✓ Content set\n\n";
    
    // Send the email
    echo "Attempting to send email...\n";
    if($mail->send()) {
        echo "✓ Message has been sent to $test_email\n";
        echo "SUCCESS: Email sent! Check your inbox (and spam folder).\n";
    } else {
        echo "ERROR: Message could not be sent.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    if (isset($mail) && $mail instanceof PHPMailer) {
        echo "PHPMailer error info: " . $mail->ErrorInfo . "\n";
    }
}

echo "</pre>";

echo "<h2>Try with different email:</h2>";
echo "<form method='get'>";
echo "<input type='email' name='email' placeholder='Enter email address'>";
echo "<button type='submit'>Send Test Email</button>";
echo "</form>";

echo "<p><a href='test_email.php'>Go back to regular test page</a></p>";
?>