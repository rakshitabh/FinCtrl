# Setting Up Real Email Verification

This guide will walk you through setting up real email verification using PHPMailer.

> **✅ Configuration Status: COMPLETED**  
> Your email configuration has been successfully tested. The system is now ready to send real emails for OTP verification and password resets.

## 1. Install Composer

If you don't have Composer installed yet, you need to install it first:

1. Download Composer from [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Follow the installation instructions for your operating system

## 2. Install PHPMailer

### Windows with WAMP:

1. Open PowerShell or Command Prompt as Administrator
2. Navigate to your project directory:
   ```
   cd c:\wamp64\www\Finance
   ```
3. Run the following command to install PHPMailer:
   ```
   composer install
   ```
   This will create a `vendor` folder with PHPMailer and its dependencies.

### If you encounter any issues with Composer:

1. Make sure PHP is in your system PATH
2. If using WAMP, you can run Composer through the WAMP PHP by using:
   ```
   C:\wamp64\bin\php\php7.x.x\php.exe C:\path\to\composer.phar install
   ```
   Replace `php7.x.x` with your actual PHP version in WAMP.

3. Alternatively, use the full path to composer:
   ```
   php C:\path\to\composer.phar install
   ```

## 3. Configure SMTP Settings

1. Open the `smtp_config.php` file
2. Update the following SMTP settings with your actual email provider details:
   ```php
   return [
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'smtp_secure' => 'tls',
       'smtp_username' => 'your_email@gmail.com',
       'smtp_password' => 'your_app_password',
       'smtp_from_email' => 'your_email@gmail.com',
       'smtp_from_name' => 'FinCtrl'
   ];
   ```

### Using Gmail as SMTP Server

If you're using Gmail, you need to create an "App Password":

1. Go to your Google Account settings
2. Enable 2-Step Verification if not already enabled
3. Go to [App Passwords](https://myaccount.google.com/apppasswords)
4. Create a new app password for "Mail"
5. Use this generated password in your code (not your regular Gmail password)

## 4. Testing

After setting up PHPMailer and configuring your SMTP settings:

1. First, run the test script to verify your email configuration:
   ```
   http://localhost/Finance/test_email.php
   ```
   This now provides a user-friendly interface for testing your email configuration.

2. Register a new account using a valid email address
3. You should receive an OTP email at the specified address
4. In development mode, the OTP is also displayed in the console for testing

✅ **Test Status: PASSED**  
Your email system is configured correctly and able to send emails through the SMTP server.

## 5. Production Settings

Before deploying to production:

1. Remove the test OTP output in the response by deleting or commenting out this line:
   ```php
   $response['test_otp'] = $otp;
   ```
2. Consider storing OTPs in a database instead of session for better security
3. Add rate limiting to prevent abuse

⚠️ **Important Security Note:**  
Your SMTP password is currently stored in plain text in `smtp_config.php`. For production environments, consider using environment variables or a more secure storage method for sensitive credentials.

## Troubleshooting

If emails are not being sent:

1. Check your SMTP settings (server, username, password)
2. Verify that your email provider allows SMTP access
3. For Gmail, ensure you're using an app password, not your regular password
4. Check your server logs for any error messages