<?php
// Set headers for plain text output
header('Content-Type: text/plain');

// Function to check file for BOM
function check_bom($file) {
    $contents = file_get_contents($file);
    $bom = chr(239) . chr(187) . chr(191);
    
    if (substr($contents, 0, 3) === $bom) {
        return "BOM found at the beginning of the file.";
    }
    
    return "No BOM found.";
}

// Check login.php
$file = 'login.php';
echo "JSON Response Diagnostic Tool\n";
echo "===========================\n\n";

if (file_exists($file)) {
    echo "Checking $file:\n";
    echo "- " . check_bom($file) . "\n";
    
    // Get first 100 characters and show hexdump
    $contents = file_get_contents($file);
    $first100 = substr($contents, 0, 100);
    
    echo "- First few characters (hex):\n";
    for ($i = 0; $i < min(strlen($first100), 100); $i++) {
        echo sprintf("%02x", ord($first100[$i])) . " ";
        if (($i + 1) % 16 === 0) echo "\n";
    }
    
    echo "\n\n";
    
    // Test direct output for GET request
    echo "Testing direct output from login.php for GET request:\n";
    echo "----------------------------------------------------\n";
    $output = shell_exec("php -r \"include('$file');\"");
    
    echo "Raw output length: " . strlen($output) . " bytes\n";
    echo "Raw output (hex):\n";
    for ($i = 0; $i < min(strlen($output), 100); $i++) {
        echo sprintf("%02x", ord($output[$i])) . " ";
        if (($i + 1) % 16 === 0) echo "\n";
    }
    
    echo "\n\nText output:\n";
    echo $output . "\n\n";
    
    // JSON parsing test
    echo "\nJSON Parsing Test:\n";
    echo "=================\n";
    
    $parsed = json_decode($output, true);
    if ($parsed === null) {
        echo "JSON parsing failed: " . json_last_error_msg() . "\n";
        
        // Check for whitespace/invisible characters at start
        echo "First 20 characters with visibility:\n";
        for ($i = 0; $i < min(strlen($output), 20); $i++) {
            $char = $output[$i];
            $ord = ord($char);
            echo "Position $i: ASCII $ord (" . ($ord < 32 ? "control character" : $char) . ")\n";
        }
    } else {
        echo "JSON parsed successfully: " . json_encode($parsed) . "\n";
    }
} else {
    echo "File $file not found.\n\n";
}

echo "\nEnd of diagnostic report.";
?>