<?php
// Start session
session_start();

// Check if a token is provided in the URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redirect to forgot password page if no token
    header('Location: login.html?error=invalid_token');
    exit;
}

// Store the token in a session variable
$_SESSION['reset_token'] = $_GET['token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FinCtrl</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <!-- Left panel with form -->
        <div class="auth-form-panel">
            <a href="index.html" class="logo">
                <img src="assets/images/logo.png" alt="FinCtrl Logo" class="logo-img">
                FinCtrl
            </a>
            
            <div class="auth-form-wrapper">
                <h2>Reset Your Password</h2>
                <p class="form-description">Enter your new password below to reset your account password.</p>
                
                <form id="resetPasswordForm" class="auth-form">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <span class="strength-label">Password strength:</span>
                            <div class="strength-meter">
                                <div class="strength-meter-fill" data-strength="0"></div>
                            </div>
                            <span class="strength-text">Too weak</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-input">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
                
                <div class="auth-links">
                    <p>Remember your password? <a href="login.html">Sign in</a></p>
                </div>
            </div>
        </div>
        
        <!-- Right panel with image/illustration -->
        <div class="auth-image-panel">
            <div class="auth-image-content">
                <h2>Take control of your finances</h2>
                <p>Reset your password to securely access your financial dashboard and continue managing your finances with confidence.</p>
            </div>
            <img src="assets/images/auth-illustration.svg" alt="Financial Management Illustration" class="auth-illustration">
        </div>
    </div>
    
    <!-- Toast notification -->
    <div class="toast-container">
        <div class="toast" id="toast">
            <div class="toast-content">
                <i class="fas fa-check-circle toast-icon success"></i>
                <div class="toast-message">Success message goes here</div>
            </div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const resetForm = document.getElementById('resetPasswordForm');
        
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validate password
            if (password !== confirmPassword) {
                showToast('Passwords do not match', 'error');
                return;
            }
            
            if (password.length < 8) {
                showToast('Password must be at least 8 characters', 'error');
                return;
            }
            
            // Get the token from the URL query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            // Send reset request
            fetch('reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Redirect to login page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
        });
        
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = toast.querySelector('.toast-message');
            const toastIcon = toast.querySelector('.toast-icon');
            
            toastMessage.textContent = message;
            
            // Set icon based on type
            if (type === 'error') {
                toastIcon.classList.remove('fa-check-circle', 'success');
                toastIcon.classList.add('fa-times-circle', 'error');
            } else {
                toastIcon.classList.remove('fa-times-circle', 'error');
                toastIcon.classList.add('fa-check-circle', 'success');
            }
            
            // Show toast
            toast.classList.add('show');
            
            // Hide after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }
        
        // Close toast on click
        document.querySelector('.toast-close').addEventListener('click', function() {
            document.getElementById('toast').classList.remove('show');
        });
        
        // Password toggle visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthMeter = document.querySelector('.strength-meter-fill');
        const strengthText = document.querySelector('.strength-text');
        
        passwordInput.addEventListener('input', updatePasswordStrength);
        
        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character variety check
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Update strength meter
            const percentage = Math.min(100, (strength / 6) * 100);
            strengthMeter.style.width = percentage + '%';
            strengthMeter.setAttribute('data-strength', strength);
            
            // Update strength text
            if (strength < 2) strengthText.textContent = 'Too weak';
            else if (strength < 4) strengthText.textContent = 'Moderate';
            else if (strength < 6) strengthText.textContent = 'Strong';
            else strengthText.textContent = 'Very strong';
        }
    });
    </script>
</body>
</html>