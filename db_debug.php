<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database class
require_once __DIR__ . '/includes/database.php';

// Output function
function output($title, $data) {
    echo "<h3>$title</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    echo "<hr>";
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Debugging</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        h3 { margin-bottom: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        hr { border: 0; border-top: 1px solid #ddd; margin: 20px 0; }
        .container { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Debugging</h1>
";

try {
    // Create database instance
    $db = Database::getInstance();
    echo "<p class='success'>Database connection established successfully.</p>";
    
    // Get PostgreSQL version
    $version = $db->fetchOne("SELECT version()");
    output("PostgreSQL Version", $version);
    
    // Check if OTP table exists
    $tables = $db->fetchAll("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    output("Available Tables", $tables);
    
    // Get OTP table structure
    $columns = $db->fetchAll("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'otp_verifications'
        ORDER BY ordinal_position
    ");
    output("OTP Table Structure", $columns);
    
    // Check for recent OTP records
    $otpRecords = $db->fetchAll("
        SELECT * FROM otp_verifications 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    output("Recent OTP Records", $otpRecords);
    
    // Test inserting an OTP record
    echo "<h3>Test OTP Insertion</h3>";
    
    // Generate test data
    $testEmail = 'test_' . time() . '@example.com';
    $testOTP = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', time() + 600);  // 10 minutes from now
    
    // Show test data
    echo "<p>Test Email: $testEmail</p>";
    echo "<p>Test OTP: $testOTP</p>";
    echo "<p>Expires At: $expires_at</p>";
    
    try {
        // Insert test record
        $insertData = [
            'email' => $testEmail,
            'otp' => $testOTP,
            'expires_at' => $expires_at,
            'verified' => false,
            'verification_attempts' => 0
        ];
        
        $id = $db->insert('otp_verifications', $insertData);
        
        if ($id) {
            echo "<p class='success'>Test OTP record inserted successfully with ID: $id</p>";
            
            // Fetch the inserted record
            $record = $db->fetchOne("SELECT * FROM otp_verifications WHERE id = :id", ['id' => $id]);
            output("Inserted Record", $record);
            
            // Delete the test record
            $deleted = $db->delete('otp_verifications', 'id = :id', ['id' => $id]);
            echo "<p>Test record deleted: $deleted row(s) affected</p>";
        } else {
            echo "<p class='error'>Failed to insert test OTP record</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error inserting test record: " . $e->getMessage() . "</p>";
    }
    
    // Show query for troubleshooting
    echo "<h3>SQL Query for Troubleshooting</h3>";
    echo "<pre>
-- Check for recent OTPs
SELECT * FROM otp_verifications ORDER BY created_at DESC LIMIT 10;

-- Check for specific email
SELECT * FROM otp_verifications WHERE email = 'your-email@example.com' ORDER BY created_at DESC;

-- Delete expired OTPs
DELETE FROM otp_verifications WHERE expires_at < NOW();
</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
}

// HTML footer
echo "
    </div>
</body>
</html>";
?>