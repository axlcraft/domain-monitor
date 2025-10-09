-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Credentials are set during installation and displayed in output
-- Placeholders will be replaced by web installer
INSERT INTO users (username, password, email, full_name, is_active) VALUES
('{{ADMIN_USERNAME}}', '{{ADMIN_PASSWORD_HASH}}', '{{ADMIN_EMAIL}}', 'Administrator', 1)
ON DUPLICATE KEY UPDATE username=username;

