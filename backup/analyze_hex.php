<?php
// Get file content
$file = 'login.php';
$content = file_get_contents($file);

// Output file information
echo "File: $file\n";
echo "Size: " . strlen($content) . " bytes\n\n";

// Output first 100 bytes in hex format
echo "First 100 bytes (hex):\n";
for ($i = 0; $i < min(100, strlen($content)); $i++) {
    printf("%02X ", ord($content[$i]));
    if (($i + 1) % 16 === 0) echo "\n";
}

echo "\n\nLast 100 bytes (hex):\n";
$lastPos = max(0, strlen($content) - 100);
for ($i = $lastPos; $i < strlen($content); $i++) {
    printf("%02X ", ord($content[$i]));
    if (($i - $lastPos + 1) % 16 === 0) echo "\n";
}

// Check for UTF-8 BOM
$bom = chr(239) . chr(187) . chr(191); // EF BB BF
if (substr($content, 0, 3) === $bom) {
    echo "\n\nUTF-8 BOM detected at the beginning of the file!\n";
} else {
    echo "\n\nNo UTF-8 BOM detected.\n";
}

// Check for invisible characters at the start
echo "\nFirst 10 characters analysis:\n";
for ($i = 0; $i < min(10, strlen($content)); $i++) {
    $char = $content[$i];
    $ord = ord($char);
    $printable = ($ord >= 32 && $ord <= 126) ? $char : '.';
    printf("Position %d: ASCII %d (0x%02X) '%s'\n", $i, $ord, $ord, $printable);
}

// Check for problematic whitespace or characters at the end
echo "\nLast 10 characters analysis:\n";
for ($i = max(0, strlen($content) - 10); $i < strlen($content); $i++) {
    $char = $content[$i];
    $ord = ord($char);
    $printable = ($ord >= 32 && $ord <= 126) ? $char : '.';
    printf("Position %d: ASCII %d (0x%02X) '%s'\n", $i, $ord, $ord, $printable);
}
?>