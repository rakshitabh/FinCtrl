// Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    fetch('check_session.php?ajax=true')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                window.location.href = 'login.html';
                return;
            }
            
            // Update user name in the dashboard
            if (data.user && data.user.name) {
                // Update welcome message
                const welcomeHeading = document.querySelector('.welcome-card h2');
                if (welcomeHeading) {
                    welcomeHeading.textContent = `Welcome back, ${data.user.name.split(' ')[0]}!`;
                }
                
                // Update user profile name
                const userProfileName = document.querySelector('.user-profile span');
                if (userProfileName) {
                    userProfileName.textContent = data.user.name;
                }
                
                // Update profile image with user's initials
                const userProfileImg = document.querySelector('.user-profile img');
                if (userProfileImg) {
                    const nameParts = data.user.name.split(' ');
                    const initials = nameParts.map(part => part[0]).join('');
                    userProfileImg.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.user.name)}&background=4CAF50&color=fff`;
                }
            }
        })
        .catch(error => {
            console.error('Session check failed:', error);
            window.location.href = 'login.html';
        });
    
    // Mobile sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }
    
    // Click outside to close sidebar (on mobile)
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnMenuToggle = menuToggle.contains(event.target);
        
        if (window.innerWidth < 992 && !isClickInsideSidebar && !isClickOnMenuToggle && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
    
    // User profile dropdown
    const userProfile = document.querySelector('.user-profile');
    
    if (userProfile) {
        userProfile.addEventListener('click', function() {
            // Implement dropdown functionality here
            alert('User profile clicked. Dropdown functionality to be implemented.');
        });
    }
    
    // Notifications dropdown
    const notifications = document.querySelector('.notifications');
    
    if (notifications) {
        notifications.addEventListener('click', function() {
            // Implement notifications functionality here
            alert('Notifications clicked. Dropdown functionality to be implemented.');
        });
    }
    
    // Add Transaction button
    const addTransactionBtn = document.querySelector('.welcome-card .btn');
    
    if (addTransactionBtn) {
        addTransactionBtn.addEventListener('click', function() {
            // Implement add transaction functionality here
            alert('Add Transaction clicked. Modal functionality to be implemented.');
        });
    }
});