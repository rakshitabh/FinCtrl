# PostgreSQL Database Setup Guide

This guide will walk you through setting up the PostgreSQL database for FinCtrl.

## Prerequisites

1. Install PostgreSQL server (version 12 or higher recommended)
2. Install pgAdmin for database management
3. PHP with pdo_pgsql extension enabled

## Setup Instructions

### 1. Install PostgreSQL

If you haven't already installed PostgreSQL:

- **Windows**: Download and install from [PostgreSQL Downloads](https://www.postgresql.org/download/windows/)
- **macOS**: Use Homebrew with `brew install postgresql`
- **Linux**: Use your package manager (e.g., `sudo apt install postgresql postgresql-contrib`)

### 2. Configure PostgreSQL

1. Create a new user (or use the default 'postgres' user)
2. Set a secure password

### 3. Configure FinCtrl Database Settings

1. Open `db_config.php` in your FinCtrl installation directory
2. Update the configuration settings:
   ```php
   return [
       'host' => 'localhost',     // Your PostgreSQL server host
       'port' => '5432',          // PostgreSQL port (default is 5432)
       'database' => 'finctrl',   // Database name
       'username' => 'postgres',  // PostgreSQL username
       'password' => 'your_password', // Your PostgreSQL password
       'schema' => 'public'       // Database schema
   ];
   ```

### 4. Create Database and Tables

You have two options for creating the database and tables:

#### Option 1: Automatic Setup (Recommended)

Run the database setup script:
```
http://localhost/Finance/db/setup.php
```

This script will:
1. Connect to PostgreSQL server
2. Create the 'finctrl' database if it doesn't exist
3. Create all required tables
4. Insert default data (categories, etc.)

#### Option 2: Manual Setup

1. Log in to pgAdmin
2. Create a new database named 'finctrl'
3. Run the SQL script located at `db/schema.sql` to create tables

### 5. Test Database Connection

1. Run the database connection test:
   ```
   http://localhost/Finance/test_db_connection.php
   ```
2. This will verify that your application can connect to the database successfully

## Database Structure

The FinCtrl database includes the following tables:

- **users**: User accounts and authentication data
- **otp_verifications**: Stores OTP codes for email verification
- **password_resets**: Stores password reset tokens
- **user_profiles**: User preferences and settings
- **categories**: Transaction categories
- **accounts**: Financial accounts
- **transactions**: Financial transactions
- **transfers**: Money transfers between accounts
- **budgets**: Budget planning
- **savings_goals**: Savings goals
- **bills**: Bill tracking and reminders

## Troubleshooting

### Common Issues:

1. **Connection Refused**:
   - Verify PostgreSQL is running
   - Check that your host and port settings are correct

2. **Authentication Failed**:
   - Verify username and password in db_config.php
   - Check PostgreSQL pg_hba.conf file for authentication settings

3. **Database Does Not Exist**:
   - Run the setup script to create the database
   - Or manually create the database in pgAdmin

4. **PHP PDO Extension Not Found**:
   - Enable the pdo_pgsql extension in your php.ini file
   - Restart your web server

For additional help, consult the PostgreSQL documentation or contact your database administrator.