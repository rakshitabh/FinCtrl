<?php
// Include the database class
require_once __DIR__ . '/includes/database.php';

// Set headers for HTML output
header('Content-Type: text/html');

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Query to show table structure
    $result = $db->query("
        SELECT column_name, data_type, character_maximum_length, 
               column_default, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'otp_verifications'
        ORDER BY ordinal_position;
    ");
    
    $columns = $result->fetchAll();
    
    echo "<h1>OTP Verifications Table Structure</h1>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Length</th><th>Default</th><th>Nullable</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['column_name']}</td>";
        echo "<td>{$column['data_type']}</td>";
        echo "<td>{$column['character_maximum_length']}</td>";
        echo "<td>{$column['column_default']}</td>";
        echo "<td>{$column['is_nullable']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Failed to retrieve table structure: " . $e->getMessage() . "</p>";
}
?>