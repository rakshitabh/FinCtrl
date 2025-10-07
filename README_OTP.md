# Finance App OTP System

This document provides an overview of the OTP (One-Time Password) system implemented for the Finance application.

## Overview

The OTP system provides a secure way to verify user email addresses during registration. The system:

1. Generates a random 6-digit OTP
2. Sends the OTP to the user's email
3. Stores the OTP in the database with an expiration time
4. Verifies the OTP when the user enters it
5. Manages verification attempts and expiration

## Files

- `send_otp.php` - Generates and sends OTP via email
- `verify_otp.php` - Validates submitted OTP against database records
- `includes/database.php` - Database connection and operation handling
- `smtp_config.php` - SMTP server configuration
- `test_otp.html` - Simple testing page for the OTP system

## Database Structure

The system uses a PostgreSQL database table named `otp_verifications` with the following structure:

- `id` - Primary key
- `email` - User's email address
- `otp_code` - The generated OTP
- `created_at` - Timestamp when OTP was created
- `expires_at` - Timestamp when OTP expires
- `verified` - Boolean indicating if OTP has been verified
- `verification_attempts` - Number of failed verification attempts

## Usage

### Sending OTP

```php
// POST request to send_otp.php
// Required: email
// Optional: name
```

Example response:
```json
{
  "status": "success",
  "message": "OTP sent to email"
}
```

### Verifying OTP

```php
// POST request to verify_otp.php
// Required: email, otp
```

Example response:
```json
{
  "status": "success",
  "message": "OTP verified successfully",
  "email": "user@example.com"
}
```

## Testing

Use the `test_otp.html` page to test the OTP sending and verification process.

In development mode (DEVELOPMENT = true), the OTP will be displayed in the response for testing purposes.

## Implementation Notes

1. OTPs expire after 10 minutes
2. Maximum 5 verification attempts per OTP
3. PostgreSQL data types are properly handled in database operations
4. Both JSON and form data are supported for requests
5. Clean response handling with output buffering