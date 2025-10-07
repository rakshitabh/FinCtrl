<?php
// Set headers for plain text output
header('Content-Type: text/plain');

// File to check
$file = 'login.php';

echo "PHP File Character Analysis\n";
echo "==========================\n\n";

if (file_exists($file)) {
    $contents = file_get_contents($file);
    $length = strlen($contents);
    
    echo "File: $file\n";
    echo "Total length: $length bytes\n\n";
    
    // Check first 10 characters
    echo "First 10 characters:\n";
    for ($i = 0; $i < min(10, $length); $i++) {
        $char = $contents[$i];
        $ord = ord($char);
        $hex = sprintf('%02X', $ord);
        $display = $ord >= 32 && $ord <= 126 ? $char : '.';
        echo "Position $i: ASCII $ord (0x$hex) '$display'\n";
    }
    
    echo "\nLast 10 characters:\n";
    for ($i = max(0, $length - 10); $i < $length; $i++) {
        $char = $contents[$i];
        $ord = ord($char);
        $hex = sprintf('%02X', $ord);
        $display = $ord >= 32 && $ord <= 126 ? $char : '.';
        echo "Position $i: ASCII $ord (0x$hex) '$display'\n";
    }
    
    // Check for PHP closing tag at the end
    $lastFive = substr($contents, -5);
    echo "\nLast 5 characters as string: '" . $lastFive . "'\n";
    
    if (strpos($lastFive, '?>') !== false) {
        echo "PHP closing tag found at the end of the file.\n";
    } else {
        echo "No PHP closing tag at the end of the file.\n";
    }
    
    // Execute the file with direct output
    echo "\nRunning the file and capturing output...\n";
    
    // Create a temporary PHP file that includes login.php and outputs its raw bytes
    $tempFile = 'temp_debug.php';
    file_put_contents($tempFile, '<?php
    ob_start();
    $_SERVER["REQUEST_METHOD"] = "GET";
    include("' . $file . '");
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . " bytes\n";
    echo "Output as hex: " . bin2hex($output) . "\n";
    echo "Output as text: " . $output . "\n";
    ');
    
    echo shell_exec("php $tempFile");
    
    // Clean up
    unlink($tempFile);
    
} else {
    echo "File not found: $file\n";
}
?>