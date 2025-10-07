<?php
// Include the database class
require_once __DIR__ . '/includes/database.php';

// Set headers for HTML output
header('Content-Type: text/html');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Database Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #4CAF50;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>OTP Database Test</h1>
    
    <?php
    try {
        // Get database instance
        $db = Database::getInstance();
        echo "<p class='success'>Database connection successful!</p>";
        
        // Test that the otp_verifications table exists
        try {
            // First try a simple query to check if the table exists
            $result = $db->query("SELECT COUNT(*) FROM otp_verifications");
            $tableExists = true;
        } catch (Exception $e) {
            $tableExists = false;
        }
        
        if ($tableExists) {
            echo "<p class='success'>The otp_verifications table exists!</p>";
            
            // Test inserting a record
            $testEmail = 'test@example.com';
            $testOtp = '123456';
            $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now
            
            // Delete any existing test records first
            $db->query("DELETE FROM otp_verifications WHERE email = :email", ['email' => $testEmail]);
            
            // Insert test record
            $id = $db->insert('otp_verifications', [
                'email' => $testEmail,
                'otp' => $testOtp,
                'expires_at' => $expiresAt,
                'verified' => false,
                'verification_attempts' => 0
            ]);
            
            echo "<p class='success'>Test OTP record inserted with ID: {$id}</p>";
            
            // Retrieve the record
            $record = $db->fetchOne("SELECT * FROM otp_verifications WHERE id = :id", ['id' => $id]);
            
            echo "<h3>Retrieved OTP Record:</h3>";
            echo "<pre>" . print_r($record, true) . "</pre>";
            
            // Update the record
            $updated = $db->update(
                'otp_verifications',
                ['verification_attempts' => 1],
                'id = :id',
                ['id' => $id]
            );
            
            echo "<p class='success'>Record updated. Affected rows: {$updated}</p>";
            
            // Retrieve again to show update
            $updatedRecord = $db->fetchOne("SELECT * FROM otp_verifications WHERE id = :id", ['id' => $id]);
            
            echo "<h3>Updated OTP Record:</h3>";
            echo "<pre>" . print_r($updatedRecord, true) . "</pre>";
            
            // Delete the test record
            $deleted = $db->delete('otp_verifications', 'id = :id', ['id' => $id]);
            
            echo "<p class='success'>Record deleted. Affected rows: {$deleted}</p>";
            
        } else {
            echo "<p class='error'>The otp_verifications table does not exist!</p>";
            echo "<p>Please run the following SQL to create it:</p>";
            echo "<pre>
CREATE TABLE otp_verifications (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verification_attempts INT DEFAULT 0
);
            </pre>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Next Steps</h2>
    <p>If all tests pass, you can try the full OTP process using our test form:</p>
    <p><a href="test_otp.html">OTP Verification Test Form</a></p>
</body>
</html>