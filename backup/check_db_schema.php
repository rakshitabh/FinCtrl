<?php
// Script to check database schema for OTP tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/includes/database.php';
    
    // Get database instance
    $db = Database::getInstance();
    
    echo "<h2>Database Table Structure</h2>";
    
    // Get OTP table structure
    $schema = $db->fetchAll("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'otp_verifications'
        ORDER BY ordinal_position
    ");
    
    echo "<h3>OTP Verifications Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Nullable</th><th>Default</th></tr>";
    
    foreach ($schema as $column) {
        echo "<tr>";
        echo "<td>{$column['column_name']}</td>";
        echo "<td>{$column['data_type']}</td>";
        echo "<td>{$column['is_nullable']}</td>";
        echo "<td>{$column['column_default']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get current OTP records
    $otps = $db->fetchAll("SELECT * FROM otp_verifications ORDER BY created_at DESC LIMIT 5");
    
    echo "<h3>Recent OTP Records:</h3>";
    
    if (count($otps) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($otps[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        foreach ($otps as $otp) {
            echo "<tr>";
            foreach ($otp as $value) {
                if (is_bool($value)) {
                    echo "<td>" . ($value ? 'true' : 'false') . "</td>";
                } else {
                    echo "<td>$value</td>";
                }
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No OTP records found.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
?>