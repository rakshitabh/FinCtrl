
-- PostgreSQL schema for OTP verifications

-- Create OTP verifications table
CREATE TABLE IF NOT EXISTS otp_verifications (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN NOT NULL DEFAULT FALSE,
    verification_attempts INTEGER NOT NULL DEFAULT 0
);

-- Add index on email for faster lookups
CREATE INDEX IF NOT EXISTS idx_otp_email ON otp_verifications(email);

-- Add index on expiration time for cleanup
CREATE INDEX IF NOT EXISTS idx_otp_expires_at ON otp_verifications(expires_at);

-- Comment on table
COMMENT ON TABLE otp_verifications IS 'Stores email verification OTPs';

-- Comments on columns
COMMENT ON COLUMN otp_verifications.id IS 'Primary key';
COMMENT ON COLUMN otp_verifications.email IS 'Email address OTP was sent to';
COMMENT ON COLUMN otp_verifications.otp IS 'One-time password code';
COMMENT ON COLUMN otp_verifications.created_at IS 'When OTP was created';
COMMENT ON COLUMN otp_verifications.expires_at IS 'When OTP expires';
COMMENT ON COLUMN otp_verifications.verified IS 'Whether OTP has been verified';
COMMENT ON COLUMN otp_verifications.verification_attempts IS 'Number of verification attempts';