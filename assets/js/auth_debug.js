// Auth Pages JavaScript - DEBUG VERSION
// This version uses the debug endpoints for OTP functions

document.addEventListener('DOMContentLoaded', function() {
    console.log("Auth Debug JS loaded");

    // Common Functions
    function showError(element, message) {
        element.textContent = message;
        element.parentElement.querySelector('input').classList.add('error');
    }
    
    function clearError(element) {
        element.textContent = '';
        element.parentElement.querySelector('input').classList.remove('error');
    }
    
    function togglePasswordVisibility(passwordField, toggleBtn) {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleBtn.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleBtn.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    
    // Modal Functions
    function showModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // ====== SIGNUP PAGE FUNCTIONALITY ======
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        console.log("Signup form detected - initializing");
        
        const fullNameInput = document.getElementById('fullName');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        
        const fullNameError = document.getElementById('fullNameError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        
        // Password visibility toggles
        const togglePassword = document.getElementById('togglePassword');
        if (togglePassword) {
            togglePassword.addEventListener('click', () => {
                togglePasswordVisibility(passwordInput, togglePassword);
            });
        }
        
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        if (toggleConfirmPassword) {
            toggleConfirmPassword.addEventListener('click', () => {
                togglePasswordVisibility(confirmPasswordInput, toggleConfirmPassword);
            });
        }
        
        // Password Strength Indicator
        const strengthProgress = document.getElementById('strengthProgress');
        const strengthText = document.getElementById('strengthText');
        const passwordStrength = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let status = '';
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            switch (strength) {
                case 0:
                    status = 'Too weak';
                    break;
                case 1:
                    status = 'Weak';
                    break;
                case 2:
                    status = 'Fair';
                    break;
                case 3:
                    status = 'Good';
                    break;
                case 4:
                    status = 'Strong';
                    break;
            }
            
            // Remove previous strength classes
            passwordStrength.className = 'password-strength';
            
            if (password.length > 0) {
                passwordStrength.classList.add(`strength-${strength}`);
                strengthText.textContent = status;
            } else {
                strengthText.textContent = 'Password strength';
            }
        });
        
        // Form Validation
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Signup form submitted");
            let isValid = true;
            
            // Validate Full Name
            if (fullNameInput.value.trim() === '') {
                showError(fullNameError, 'Full name is required');
                isValid = false;
            } else if (fullNameInput.value.trim().length < 2) {
                showError(fullNameError, 'Full name must be at least 2 characters');
                isValid = false;
            } else {
                clearError(fullNameError);
            }
            
            // Validate Email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value.trim() === '') {
                showError(emailError, 'Email is required');
                isValid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                showError(emailError, 'Please enter a valid email address');
                isValid = false;
            } else {
                clearError(emailError);
            }
            
            // Validate Password
            if (passwordInput.value === '') {
                showError(passwordError, 'Password is required');
                isValid = false;
            } else if (passwordInput.value.length < 8) {
                showError(passwordError, 'Password must be at least 8 characters');
                isValid = false;
            } else {
                clearError(passwordError);
            }
            
            // Validate Confirm Password
            if (confirmPasswordInput.value === '') {
                showError(confirmPasswordError, 'Please confirm your password');
                isValid = false;
            } else if (confirmPasswordInput.value !== passwordInput.value) {
                showError(confirmPasswordError, 'Passwords do not match');
                isValid = false;
            } else {
                clearError(confirmPasswordError);
            }
            
            // If form is valid, send OTP to email
            if (isValid) {
                // Disable submit button and show loading state
                const submitBtn = signupForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                console.log("Sending OTP request to debug endpoint");
                
                // Send request to server to send OTP using the debug endpoint
                fetch('send_otp_debug.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        name: fullNameInput.value.trim(),
                        email: emailInput.value.trim()
                    }),
                })
                .then(response => {
                    console.log("OTP request response received");
                    return response.json();
                })
                .then(data => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                    
                    console.log("OTP response data:", data);
                    
                    if (data.status === 'success') {
                        // Show OTP modal
                        showModal('otpModal');
                        startOtpCountdown();
                        
                        // For testing, display the OTP in console
                        if (data.test_otp) {
                            console.log('Test OTP:', data.test_otp);
                        }
                    } else {
                        // Show error
                        showError(emailError, data.message || 'Failed to send OTP. Please try again.');
                    }
                })
                .catch(error => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                    
                    // Show error
                    showError(emailError, 'An error occurred. Please try again.');
                    console.error('Error:', error);
                });
            }
        });
        
        // OTP Modal Functionality
        if (document.getElementById('otpModal')) {
            const otpInputs = document.querySelectorAll('.otp-input');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const closeOtpModal = document.getElementById('closeOtpModal');
            const resendOtp = document.getElementById('resendOtp');
            const otpCountdown = document.getElementById('otpCountdown');
            let countdownTimer;
            
            // Handle OTP input auto-tab
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === this.maxLength && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });
            
            // Close OTP modal
            if (closeOtpModal) {
                closeOtpModal.addEventListener('click', function() {
                    closeModal('otpModal');
                    clearOtpFields();
                });
            }
            
            // Verify OTP button
            if (verifyOtpBtn) {
                verifyOtpBtn.addEventListener('click', function() {
                    let otpValue = '';
                    let isComplete = true;
                    
                    otpInputs.forEach(input => {
                        otpValue += input.value;
                        if (input.value === '') isComplete = false;
                    });
                    
                    if (!isComplete) {
                        document.getElementById('otpError').textContent = 'Please enter the complete verification code';
                        return;
                    }
                    
                    console.log("Verifying OTP:", otpValue);
                    
                    // Disable button and show loading state
                    verifyOtpBtn.disabled = true;
                    const originalBtnText = verifyOtpBtn.textContent;
                    verifyOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                    
                    // Send OTP to server for verification using debug endpoint
                    fetch('verify_otp_debug.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            otp: otpValue,
                            email: document.getElementById('email').value.trim()
                        }),
                    })
                    .then(response => {
                        console.log("OTP verification response received");
                        return response.json();
                    })
                    .then(data => {
                        // Re-enable button
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.innerHTML = originalBtnText;
                        
                        console.log("OTP verification data:", data);
                        
                        if (data.status === 'success') {
                            // If OTP verification is successful, register the user
                            registerUser();
                        } else {
                            // Show error
                            document.getElementById('otpError').textContent = data.message || 'Invalid OTP. Please try again.';
                        }
                    })
                    .catch(error => {
                        // Re-enable button
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.innerHTML = originalBtnText;
                        
                        // Show error
                        document.getElementById('otpError').textContent = 'An error occurred. Please try again.';
                        console.error('Error:', error);
                    });
                });
            }
            
            // Function to register user after OTP verification
            function registerUser() {
                // Show loading state on verify button
                verifyOtpBtn.disabled = true;
                verifyOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Completing Registration...';
                
                console.log("Registering user");
                
                // Send registration request to server
                fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fullName: fullNameInput.value.trim(),
                        email: emailInput.value.trim(),
                        password: passwordInput.value
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    // Re-enable button
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.innerHTML = 'Verify';
                    
                    console.log("Registration response:", data);
                    
                    if (data.success) {
                        // Close OTP modal and show success
                        closeModal('otpModal');
                        showModal('successModal');
                        clearTimeout(countdownTimer);
                    } else {
                        // Show error
                        document.getElementById('otpError').textContent = data.message || 'Registration failed. Please try again.';
                    }
                })
                .catch(error => {
                    // Re-enable button
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.innerHTML = 'Verify';
                    
                    // Show error
                    document.getElementById('otpError').textContent = 'Registration failed. Please try again.';
                    console.error('Error:', error);
                });
            }
            
            // Resend OTP
            if (resendOtp) {
                resendOtp.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.classList.contains('disabled')) return;
                    
                    console.log("Resending OTP");
                    
                    // Show loading state
                    this.classList.add('disabled');
                    const originalText = this.textContent;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    
                    // Send request to server to resend OTP using debug endpoint
                    fetch('send_otp_debug.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            name: fullNameInput.value.trim(),
                            email: emailInput.value.trim()
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset text
                        this.textContent = originalText;
                        
                        console.log("Resend OTP response:", data);
                        
                        if (data.status === 'success') {
                            clearOtpFields();
                            startOtpCountdown();
                            // Show a success message that OTP was resent
                            document.getElementById('otpError').textContent = 'A new verification code has been sent.';
                            document.getElementById('otpError').style.color = 'var(--success)';
                            
                            // For testing, display the OTP in console
                            if (data.test_otp) {
                                console.log('New Test OTP:', data.test_otp);
                            }
                        } else {
                            // Show error
                            document.getElementById('otpError').textContent = data.message || 'Failed to resend verification code.';
                            document.getElementById('otpError').style.color = 'var(--error)';
                            this.classList.remove('disabled');
                        }
                    })
                    .catch(error => {
                        // Reset text and state
                        this.textContent = originalText;
                        this.classList.remove('disabled');
                        
                        // Show error
                        document.getElementById('otpError').textContent = 'An error occurred. Please try again.';
                        document.getElementById('otpError').style.color = 'var(--error)';
                        console.error('Error:', error);
                    });
                });
            }
            
            // Success modal to login redirect
            const goToLoginBtn = document.getElementById('goToLoginBtn');
            if (goToLoginBtn) {
                goToLoginBtn.addEventListener('click', function() {
                    window.location.href = 'login.html';
                });
            }
            
            // Helper functions
            function startOtpCountdown() {
                let timeLeft = 60;
                resendOtp.classList.add('disabled');
                
                otpCountdown.textContent = `${timeLeft}s`;
                
                countdownTimer = setInterval(() => {
                    timeLeft--;
                    otpCountdown.textContent = `${timeLeft}s`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        resendOtp.classList.remove('disabled');
                        otpCountdown.textContent = '';
                    }
                }, 1000);
            }
            
            function clearOtpFields() {
                otpInputs.forEach(input => {
                    input.value = '';
                });
                document.getElementById('otpError').textContent = '';
                otpInputs[0].focus();
            }
        }
    }
    
    // ====== LOGIN PAGE FUNCTIONALITY ======
    // Login functionality omitted for brevity
});