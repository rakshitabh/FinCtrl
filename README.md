# FinCtrl - Financial Management Application

FinCtrl is a modern financial management web application that helps users track their spending, manage budgets, and gain insights into their financial health.

## Features

- **User Authentication:** Secure signup and login with email verification
- **Dashboard:** Overview of financial status including income, expenses, and savings
- **Transaction Tracking:** Monitor and categorize all financial transactions
- **Budget Management:** Set and track budgets for different expense categories
- **Reporting:** Visual analytics of spending patterns and financial trends

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP
- **Email:** PHPMailer for email verification and notifications
- **Security:** Password hashing, session management, OTP verification

## Project Structure

- `index.html` - Landing page
- `login.html` - User login page
- `signup.html` - New user registration
- `dashboard.html` - Main application dashboard
- `reset-password.php` - Password reset page
- **Assets:**
  - `assets/css/` - Stylesheets
  - `assets/js/` - JavaScript files
  - `assets/images/` - Images and SVGs
- **PHP Backend:**
  - `send_otp.php` - Email verification service
  - `verify_otp.php` - OTP verification handler
  - `register.php` - User registration processor
  - `login.php` - Authentication service
  - `logout.php` - Session termination
  - `check_session.php` - Session validation
  - `forgot_password.php` - Password reset request handler
  - `reset_password.php` - Password reset processor

## Setup Instructions

### Prerequisites

- Web server with PHP 7.4+ support (WAMP, XAMPP, etc.)
- Composer for PHP dependencies
- Email SMTP credentials for authentication emails

### Installation

1. Clone the repository to your web server directory:
   ```
   git clone https://github.com/yourusername/finctrl.git
   ```

2. Install PHP dependencies:
   ```
   cd finctrl
   composer install
   ```

3. Configure email settings:
   - Open `smtp_config.php`
   - Update with your SMTP server details

4. For detailed setup instructions for the email system, see `email_setup_guide.md`

## Email Configuration

Real email verification is implemented using PHPMailer. To set up:

1. Install dependencies with Composer
2. Configure SMTP settings in `smtp_config.php`
3. Test the configuration with `test_email.php`

For detailed instructions, see the [Email Setup Guide](email_setup_guide.md).

## Security Features

- Password hashing using PHP's password_hash()
- Time-limited OTP codes for email verification
- Session management with secure cookies
- CSRF protection for forms
- Input validation and sanitization

## License

[MIT License](LICENSE)

## Contact

For questions or support, please contact [your-email@example.com]