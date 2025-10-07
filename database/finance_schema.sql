-- Finance App Database Schema
-- Complete PostgreSQL schema for the Finance application
-- Focus on OTP verification system

-- Create database (Uncomment and run separately if needed)
-- CREATE DATABASE finctrl;
-- \c finctrl

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE
);

-- OTP verification table
CREATE TABLE IF NOT EXISTS otp_verifications (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN NOT NULL DEFAULT FALSE,
    verification_attempts INTEGER NOT NULL DEFAULT 0
);

-- Add indexes for OTP table
CREATE INDEX IF NOT EXISTS idx_otp_email ON otp_verifications(email);
CREATE INDEX IF NOT EXISTS idx_otp_expires_at ON otp_verifications(expires_at);

-- Add comments to OTP table
COMMENT ON TABLE otp_verifications IS 'Stores email verification OTPs';
COMMENT ON COLUMN otp_verifications.id IS 'Primary key';
COMMENT ON COLUMN otp_verifications.email IS 'Email address OTP was sent to';
COMMENT ON COLUMN otp_verifications.otp IS 'One-time password code';
COMMENT ON COLUMN otp_verifications.created_at IS 'When OTP was created';
COMMENT ON COLUMN otp_verifications.expires_at IS 'When OTP expires';
COMMENT ON COLUMN otp_verifications.verified IS 'Whether OTP has been verified';
COMMENT ON COLUMN otp_verifications.verification_attempts IS 'Number of verification attempts';

-- Password reset table
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_used BOOLEAN DEFAULT FALSE
);

-- User profiles
CREATE TABLE IF NOT EXISTS user_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE REFERENCES users(id),
    currency VARCHAR(10) DEFAULT 'USD',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(100) DEFAULT 'UTC',
    avatar_url VARCHAR(255),
    notification_preferences JSONB DEFAULT '{"email": true, "push": false}',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL, -- 'income', 'expense'
    icon VARCHAR(50),
    color VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Accounts
CREATE TABLE IF NOT EXISTS accounts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'checking', 'savings', 'credit', 'cash', etc.
    currency VARCHAR(10) DEFAULT 'USD',
    balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    icon VARCHAR(50),
    color VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions
CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    account_id INTEGER REFERENCES accounts(id),
    category_id INTEGER REFERENCES categories(id),
    amount DECIMAL(15,2) NOT NULL,
    type VARCHAR(20) NOT NULL, -- 'income', 'expense', 'transfer'
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_id INTEGER,
    attachment_url VARCHAR(255)
);

-- Transfers
CREATE TABLE IF NOT EXISTS transfers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    from_account_id INTEGER REFERENCES accounts(id),
    to_account_id INTEGER REFERENCES accounts(id),
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    transfer_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id INTEGER REFERENCES transactions(id)
);

-- Budgets
CREATE TABLE IF NOT EXISTS budgets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    category_id INTEGER REFERENCES categories(id),
    period VARCHAR(20) NOT NULL, -- 'weekly', 'monthly', 'yearly'
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Savings goals
CREATE TABLE IF NOT EXISTS savings_goals (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    target_date DATE,
    account_id INTEGER REFERENCES accounts(id),
    icon VARCHAR(50),
    color VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed BOOLEAN DEFAULT FALSE
);

-- Bills
CREATE TABLE IF NOT EXISTS bills (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    due_date DATE NOT NULL,
    category_id INTEGER REFERENCES categories(id),
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50), -- 'monthly', 'weekly', etc.
    recurrence_count INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_paid BOOLEAN DEFAULT FALSE,
    account_id INTEGER REFERENCES accounts(id),
    reminder_days INTEGER DEFAULT 3
);

-- Create additional indexes for performance
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_accounts_user_id ON accounts(user_id);
CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_budgets_user_id ON budgets(user_id);
CREATE INDEX idx_bills_user_id ON bills(user_id);
CREATE INDEX idx_bills_due_date ON bills(due_date);
CREATE INDEX idx_password_resets_email ON password_resets(email);

-- Insert default categories
INSERT INTO categories (name, type, icon, color, is_default) VALUES
('Salary', 'income', 'fa-briefcase', '#4CAF50', TRUE),
('Investments', 'income', 'fa-chart-line', '#2196F3', TRUE),
('Gifts', 'income', 'fa-gift', '#9C27B0', TRUE),
('Other Income', 'income', 'fa-money-bill', '#FF9800', TRUE),
('Housing', 'expense', 'fa-home', '#F44336', TRUE),
('Transportation', 'expense', 'fa-car', '#795548', TRUE),
('Food', 'expense', 'fa-utensils', '#FF5722', TRUE),
('Utilities', 'expense', 'fa-bolt', '#607D8B', TRUE),
('Healthcare', 'expense', 'fa-medkit', '#E91E63', TRUE),
('Entertainment', 'expense', 'fa-film', '#9C27B0', TRUE),
('Shopping', 'expense', 'fa-shopping-bag', '#3F51B5', TRUE),
('Education', 'expense', 'fa-graduation-cap', '#009688', TRUE),
('Personal Care', 'expense', 'fa-heart', '#F06292', TRUE),
('Travel', 'expense', 'fa-plane', '#03A9F4', TRUE),
('Debt Payments', 'expense', 'fa-credit-card', '#FF5722', TRUE),
('Savings', 'expense', 'fa-piggy-bank', '#4CAF50', TRUE),
('Gifts & Donations', 'expense', 'fa-gift', '#9E9E9E', TRUE),
('Taxes', 'expense', 'fa-file-invoice-dollar', '#795548', TRUE),
('Miscellaneous', 'expense', 'fa-ellipsis-h', '#607D8B', TRUE);
