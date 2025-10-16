-- Add Two-Factor Authentication (2FA) support
-- TOTP (Time-based One-Time Password) implementation

-- Add 2FA fields to users table
ALTER TABLE users 
ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE AFTER email_verified,
ADD COLUMN two_factor_secret VARCHAR(32) NULL AFTER two_factor_enabled,
ADD COLUMN two_factor_backup_codes TEXT NULL AFTER two_factor_secret,
ADD COLUMN two_factor_setup_at TIMESTAMP NULL AFTER two_factor_backup_codes;

-- Create table for 2FA verification attempts (for rate limiting and security)
CREATE TABLE IF NOT EXISTS two_factor_verification_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table for 2FA email codes (backup method)
CREATE TABLE IF NOT EXISTS two_factor_email_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_code (code),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add 2FA policy setting
INSERT INTO settings (setting_key, setting_value) VALUES
('two_factor_policy', 'optional'),
('two_factor_rate_limit_minutes', '15'),
('two_factor_email_code_expiry_minutes', '10')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
