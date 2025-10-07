<?php
// Database setup script

// Include the database class
require_once __DIR__ . "/../includes/database.php";

// Function to output messages
function output($message, $error = false) {
    echo ($error ? "[ERROR] " : "[INFO] ") . $message . PHP_EOL;
}

// More PHP code omitted for brevity...
