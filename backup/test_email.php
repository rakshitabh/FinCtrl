<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if form submitted to send test email
$sent = false;
$success = false;
$errorMessage = '';
$testEmailAddress = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmailAddress = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL) ? $_POST['test_email'] : '';
    $sent = true;
    
    try {
        // Include autoloader
        require 'vendor/autoload.php';
        
        // Load SMTP configuration
        $smtp_config = include 'smtp_config.php';
        
        // Create instance of PHPMailer
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = isset($_POST['show_debug']) ? $smtp_config['smtp_debug'] ?? 2 : 0; // Enable verbose debug output if checkbox checked
        $mail->isSMTP();                           // Use SMTP
        $mail->Host       = $smtp_config['smtp_host'];      // SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = $smtp_config['smtp_username']; // SMTP username
        $mail->Password   = $smtp_config['smtp_password'];  // SMTP password
        $mail->SMTPSecure = $smtp_config['smtp_secure'];   // Enable TLS encryption
        $mail->Port       = $smtp_config['smtp_port'];     // TCP port to connect to
        
        // Additional settings
        if (isset($smtp_config['smtp_timeout'])) {
            $mail->Timeout = $smtp_config['smtp_timeout'];
        }
        
        // Try to prevent emails from going to spam folder
        $mail->XMailer = 'FinCtrl Mailer';
        $mail->addCustomHeader('X-Application', 'FinCtrl Financial Management System');
        
        // Recipients
        $mail->setFrom($smtp_config['smtp_from_email'], $smtp_config['smtp_from_name']);
        $mail->addAddress($testEmailAddress);  // Add recipient
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'FinCtrl Email Test';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h1 style="color: #4CAF50;">FinCtrl Email Test</h1>
                </div>
                <p>Hello,</p>
                <p>This is a test email to verify that PHPMailer is configured correctly.</p>
                <p>If you received this email, your SMTP settings are working properly!</p>
                <p>You can now proceed with using the real email verification features in FinCtrl.</p>
                <p style="margin-top: 30px; font-size: 12px; color: #777; text-align: center;">
                    &copy; ' . date("Y") . ' FinCtrl. All rights reserved.
                </p>
            </div>
        ';
        
        // Plain text version for non-HTML mail clients
        $mail->AltBody = 'This is a test email to verify that your FinCtrl email system is configured correctly.';
        
        // Send the email
        $mail->send();
        $success = true;
    } catch (Exception $e) {
        $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $success = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinCtrl Email Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            text-decoration: none;
        }
        .logo-img {
            height: 40px;
            margin-right: 10px;
        }
        .test-form {
            margin-top: 30px;
        }
        .result-box {
            margin: 30px 0;
            padding: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
            border-left: 5px solid;
        }
        .success {
            border-color: var(--success-color);
            background-color: rgba(76, 175, 80, 0.1);
        }
        .error {
            border-color: var(--danger-color);
            background-color: rgba(244, 67, 54, 0.1);
        }
        .next-steps {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .next-steps h3 {
            margin-top: 0;
        }
        .debug-output {
            margin-top: 20px;
            padding: 15px;
            background-color: #2d2d2d;
            color: #f8f9fa;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            white-space: pre-wrap;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }
        .action-links {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.html" class="logo">
                <img src="assets/images/logo.svg" alt="FinCtrl Logo" class="logo-img">
                FinCtrl
            </a>
            <h1>Email Configuration Test</h1>
            <p>Use this tool to verify that your email system is working correctly</p>
        </div>
        
        <?php if ($sent): ?>
            <?php if ($success): ?>
                <div class="result-box success">
                    <h3><i class="fas fa-check-circle"></i> Success!</h3>
                    <p>Test email has been sent successfully to <strong><?php echo htmlspecialchars($testEmailAddress); ?></strong>.</p>
                    <p>Please check your inbox (and spam folder) to confirm receipt.</p>
                </div>
                
                <div class="next-steps">
                    <h3>Next Steps</h3>
                    <ol>
                        <li>You can now use the real email verification features in FinCtrl.</li>
                        <li>Make sure the "test_otp" output is removed from production code.</li>
                        <li>Test the complete signup flow with email verification.</li>
                        <li>Test the password reset functionality.</li>
                    </ol>
                </div>
                
                <div class="action-links">
                    <a href="signup.html" class="btn btn-primary">Test Signup</a>
                    <a href="login.html" class="btn">Test Login</a>
                    <a href="test_email.php" class="btn">Send Another Test</a>
                </div>
            <?php else: ?>
                <div class="result-box error">
                    <h3><i class="fas fa-times-circle"></i> Error</h3>
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
                
                <div class="next-steps">
                    <h3>Troubleshooting</h3>
                    <ol>
                        <li>Check that your SMTP credentials in smtp_config.php are correct.</li>
                        <li>Make sure that your email provider allows SMTP access.</li>
                        <li>If using Gmail, ensure you're using an App Password.</li>
                        <li>Try running the test again with the debug output enabled.</li>
                    </ol>
                </div>
                
                <a href="test_email.php" class="btn btn-primary">Try Again</a>
            <?php endif; ?>
        <?php else: ?>
            <form method="post" action="test_email.php" class="test-form">
                <div class="form-group">
                    <label for="test_email">Email Address for Testing</label>
                    <input type="email" id="test_email" name="test_email" placeholder="Enter your email address" required>
                    <small>A test email will be sent to this address.</small>
                </div>
                
                <div class="form-check" style="margin-top: 15px;">
                    <input type="checkbox" id="show_debug" name="show_debug" class="form-check-input">
                    <label for="show_debug" class="form-check-label">Show debug output</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Send Test Email</button>
            </form>
        <?php endif; ?>
        
        <?php if ($sent && isset($_POST['show_debug']) && $mail->SMTPDebug === 2): ?>
            <div class="debug-output">
                <pre><?php echo ob_get_clean(); ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="action-links" style="margin-top: 30px;">
            <a href="email_setup_guide.md" class="btn">Email Setup Guide</a>
            <a href="index.html" class="btn">Back to Home</a>
        </div>
    </div>
</body>
</html>