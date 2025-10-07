<?php
// Set headers for plain text output
header('Content-Type: text/plain');

// Function to analyze the request/response cycle
function test_api_call($endpoint, $method, $data = null) {
    echo "Testing API Call to $endpoint with $method\n";
    echo "======================================\n\n";
    
    // Initialize cURL session
    $ch = curl_init("http://localhost/Finance/$endpoint");
    
    // Set request method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $json_data = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            echo "Request data: $json_data\n";
        }
    }
    
    // Additional cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Execute cURL session
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Display request information
    echo "Request URL: {$info['url']}\n";
    echo "HTTP Status Code: {$info['http_code']}\n";
    
    if ($curl_error) {
        echo "cURL Error: $curl_error\n";
        return;
    }
    
    // Split response into header and body
    $header_size = $info['header_size'];
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    // Display headers
    echo "\nResponse Headers:\n";
    echo "----------------\n";
    echo $header;
    
    // Display raw response body
    echo "\nResponse Body (Raw):\n";
    echo "-------------------\n";
    echo "Length: " . strlen($body) . " bytes\n";
    
    // Display the response body as hexadecimal to find hidden characters
    echo "\nResponse Body (Hex):\n";
    echo "------------------\n";
    
    for ($i = 0; $i < strlen($body); $i++) {
        printf("%02x ", ord($body[$i]));
        if (($i + 1) % 16 === 0) echo "\n";
    }
    echo "\n";
    
    // Try to parse JSON
    echo "\nJSON Parse Result:\n";
    echo "-----------------\n";
    
    $json_data = json_decode($body, true);
    if ($json_data === null) {
        echo "Failed to parse JSON: " . json_last_error_msg() . "\n";
        
        // Check for BOM or other common issues
        if (substr($body, 0, 3) === "\xEF\xBB\xBF") {
            echo "UTF-8 BOM detected at the beginning of the response!\n";
        }
        
        // Check for invisible characters
        echo "\nCharacter Analysis:\n";
        echo "-----------------\n";
        for ($i = 0; $i < min(20, strlen($body)); $i++) {
            $char = $body[$i];
            $ord = ord($char);
            echo "Position $i: ASCII $ord (Hex: " . sprintf('%02X', $ord) . ") " . 
                 ($ord >= 32 && $ord <= 126 ? "'" . $char . "'" : "control character") . "\n";
        }
    } else {
        echo "Successfully parsed JSON:\n";
        print_r($json_data);
    }
    
    echo "\n";
}

// Test login.php with valid credentials
echo "=================================\n";
echo "TEST 1: Valid login credentials\n";
echo "=================================\n";
test_api_call('login.php', 'POST', [
    'email' => 'test@example.com',
    'password' => 'password123',
    'remember' => false
]);

// Test login.php with invalid request method
echo "\n=================================\n";
echo "TEST 2: GET request (should fail)\n";
echo "=================================\n";
test_api_call('login.php', 'GET');

echo "\nDone testing.\n";
?>