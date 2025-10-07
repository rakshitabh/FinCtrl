<?php
// This is a proxy script to ensure proper JSON is returned

// Set proper headers
header('Content-Type: application/json');

// Get data from login.php
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => file_get_contents('php://input'),
        'timeout' => 5,
    ]
]);

// Forward the request to the actual login.php
$result = file_get_contents('http://localhost/Finance/login.php', false, $context);

// Check if we got a valid response
if ($result === FALSE) {
    echo json_encode(['success' => false, 'message' => 'Error connecting to login service']);
    exit;
}

// Check if the result is valid JSON
$data = json_decode($result, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // Log the invalid response for debugging
    error_log('Invalid JSON response from login.php: ' . $result);
    
    // Return a valid JSON error response
    echo json_encode(['success' => false, 'message' => 'Invalid response from server']);
    exit;
}

// Return the JSON result
echo $result;
?>