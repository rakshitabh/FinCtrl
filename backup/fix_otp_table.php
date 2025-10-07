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
    <title>Fix OTP Table Structure</title>
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
    <h1>Fix OTP Verifications Table Structure</h1>
    
    <?php
    try {
        // Get database instance
        $db = Database::getInstance();
        
        // First, check the current structure
        $result = $db->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'otp_verifications';
        ");
        
        $existingColumns = array_map(function($col) {
            return $col['column_name'];
        }, $result->fetchAll());
        
        echo "<h3>Current Columns:</h3>";
        echo "<pre>" . print_r($existingColumns, true) . "</pre>";
        
        // Array to track columns we need to add
        $columnsToAdd = [];
        
        // Check if verified column exists
        if (!in_array('verified', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN verified BOOLEAN DEFAULT FALSE";
        }
        
        // Check if verification_attempts column exists
        if (!in_array('verification_attempts', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN verification_attempts INT DEFAULT 0";
        }
        
        // Check if created_at column exists
        if (!in_array('created_at', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        }
        
        if (!empty($columnsToAdd)) {
            $sql = "ALTER TABLE otp_verifications " . implode(", ", $columnsToAdd);
            echo "<h3>Executing SQL:</h3>";
            echo "<pre>{$sql}</pre>";
            
            // Execute the ALTER TABLE statement
            $db->query($sql);
            
            echo "<p class='success'>Table structure updated successfully!</p>";
        } else {
            echo "<p>No changes needed to the table structure.</p>";
        }
        
        // Check the updated structure
        $result = $db->query("
            SELECT column_name, data_type, column_default, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'otp_verifications'
            ORDER BY ordinal_position;
        ");
        
        $updatedColumns = $result->fetchAll();
        
        echo "<h3>Updated Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Column Name</th><th>Data Type</th><th>Default</th><th>Nullable</th></tr>";
        
        foreach ($updatedColumns as $column) {
            echo "<tr>";
            echo "<td>{$column['column_name']}</td>";
            echo "<td>{$column['data_type']}</td>";
            echo "<td>{$column['column_default']}</td>";
            echo "<td>{$column['is_nullable']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h3>Next Steps</h3>
    <p>Now that the table structure has been fixed, you can try the OTP test:</p>
    <ul>
        <li><a href="test_otp_db.php">Test OTP Database</a></li>
        <li><a href="test_otp.html">Test OTP Verification Flow</a></li>
    </ul>
    
</body>
</html>