<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    // Redirect to login page
    header('Location: login.html');
    exit;
}

// For AJAX requests, return user information
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'user' => [
            'name' => $_SESSION['user']['name'],
            'email' => $_SESSION['user']['email']
        ]
    ]);
    exit;
}
?>