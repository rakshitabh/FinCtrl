<?php
// Gmail credential validator

// Set to display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Gmail Credential Validator</h1>";
echo "<pre>";

try {
    // Load SMTP configuration
    $smtp_config = include 'smtp_config.php';
    
    echo "Testing connection to Gmail SMTP server...\n";
    echo "Username: " . $smtp_config['smtp_username'] . "\n";
    echo "Password: " . str_repeat('*', strlen($smtp_config['smtp_password'])) . "\n\n";
    
    // Try connecting to Gmail SMTP
    $smtp = fsockopen($smtp_config['smtp_host'], $smtp_config['smtp_port'], $errno, $errstr, 30);
    
    if (!$smtp) {
        echo "ERROR: Could not connect to SMTP server!\n";
        echo "Error $errno: $errstr\n";
    } else {
        echo "✓ Successfully connected to SMTP server\n\n";
        
        // Try EHLO
        echo "Sending EHLO command...\n";
        fwrite($smtp, "EHLO localhost\r\n");
        $ehlo_response = fgets($smtp, 1024);
        echo "Response: $ehlo_response\n";
        
        // Read the rest of the EHLO response
        while (substr($ehlo_response, 3, 1) == '-') {
            $ehlo_response = fgets($smtp, 1024);
            echo "Response: $ehlo_response\n";
        }
        
        // Check if STARTTLS is available
        if (strpos($ehlo_response, 'STARTTLS') !== false) {
            echo "✓ STARTTLS is available\n\n";
        } else {
            echo "WARNING: STARTTLS might not be available\n\n";
        }
        
        // Close connection
        fclose($smtp);
        
        echo "Now checking if your Google account has the required settings...\n\n";
        
        echo "RECOMMENDATIONS:\n";
        echo "1. Make sure 2-Step Verification is enabled for your Google account\n";
        echo "2. Generate a new App Password specifically for this application\n";
        echo "3. Check if 'Less secure app access' is turned OFF (it should be)\n";
        echo "4. Make sure your Google account doesn't have any security holds\n";
        echo "5. Check if your IP isn't blocked by Google\n\n";
        
        echo "ADDITIONAL TESTS:\n";
        echo "- Try accessing Gmail directly to ensure your account is working\n";
        echo "- Check if your email isn't over quota\n";
        echo "- Make sure your App Password is correctly entered (spaces are important)\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h2>Next Steps</h2>";
echo "<p>After addressing any issues found above:</p>";
echo "<ul>";
echo "<li><a href='direct_email_test.php'>Run the direct PHPMailer test</a></li>";
echo "<li><a href='test_email.php'>Go back to the regular test page</a></li>";
echo "</ul>";
?>