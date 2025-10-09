-- Domain Monitor v1.1.0 - Complete Initial Schema
-- This consolidated migration includes all features for fresh installations

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Notification groups table (must be created first - referenced by domains)
CREATE TABLE IF NOT EXISTS notification_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domains table
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    notification_group_id INT NULL,
    registrar VARCHAR(255),
    registrar_url VARCHAR(255),
    expiration_date DATE,
    updated_date DATE,
    abuse_email VARCHAR(255),
    last_checked TIMESTAMP NULL,
    status ENUM('active', 'expiring_soon', 'expired', 'error', 'available') DEFAULT 'active',
    whois_data JSON,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_group_id) REFERENCES notification_groups(id) ON DELETE SET NULL,
    INDEX idx_notification_group_id (notification_group_id),
    INDEX idx_domain_name (domain_name),
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_status (status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification channels table
CREATE TABLE IF NOT EXISTS notification_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_group_id INT NOT NULL,
    channel_type ENUM('email', 'telegram', 'discord', 'slack') NOT NULL,
    channel_config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_group_id) REFERENCES notification_groups(id) ON DELETE CASCADE,
    INDEX idx_group_id (notification_group_id),
    INDEX idx_channel_type (channel_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification logs table
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    channel_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    INDEX idx_domain_id (domain_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USER MANAGEMENT & AUTHENTICATION
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    email_verification_sent_at TIMESTAMP NULL,
    full_name VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (credentials will be set during installation)
INSERT INTO users (username, password, email, full_name, is_active, role, email_verified) VALUES
('{{ADMIN_USERNAME}}', '{{ADMIN_PASSWORD_HASH}}', '{{ADMIN_EMAIL}}', 'Administrator', 1, 'admin', 1)
ON DUPLICATE KEY UPDATE username=username;

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (database-backed sessions)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    country VARCHAR(100) DEFAULT NULL,
    country_code VARCHAR(2) DEFAULT NULL,
    region VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    isp VARCHAR(255) DEFAULT NULL,
    timezone VARCHAR(50) DEFAULT NULL,
    payload MEDIUMTEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remember me tokens table
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User notifications table (in-app notifications)
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    domain_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TLD REGISTRY SYSTEM
-- =====================================================

-- TLD registry table
CREATE TABLE IF NOT EXISTS tld_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tld VARCHAR(63) NOT NULL UNIQUE,
    rdap_servers JSON,
    whois_server VARCHAR(255),
    registry_url VARCHAR(500),
    iana_publication_date TIMESTAMP NULL,
    iana_last_updated TIMESTAMP NULL,
    record_last_updated TIMESTAMP NULL,
    registration_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tld (tld),
    INDEX idx_is_active (is_active),
    INDEX idx_iana_publication_date (iana_publication_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TLD import logs table
CREATE TABLE IF NOT EXISTS tld_import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_type ENUM('tld_list', 'rdap', 'whois', 'manual', 'complete_workflow', 'check_updates') NOT NULL,
    total_tlds INT DEFAULT 0,
    new_tlds INT DEFAULT 0,
    updated_tlds INT DEFAULT 0,
    failed_tlds INT DEFAULT 0,
    iana_publication_date TIMESTAMP NULL,
    version VARCHAR(50) NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    error_message TEXT,
    details JSON,
    INDEX idx_started_at (started_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM SETTINGS
-- =====================================================

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    `type` VARCHAR(50) DEFAULT 'string',
    `description` TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, `type`, `description`) VALUES
-- Application settings
('app_name', 'Domain Monitor', 'string', 'Application name'),
('app_url', 'http://localhost:8000', 'string', 'Application URL'),
('app_timezone', 'UTC', 'string', 'Application timezone'),
('app_version', '1.1.0', 'string', 'Application version number'),

-- Email settings
('mail_host', 'smtp.mailtrap.io', 'string', 'SMTP server host'),
('mail_port', '2525', 'string', 'SMTP server port'),
('mail_username', '', 'string', 'SMTP username'),
('mail_password', '', 'encrypted', 'SMTP password (encrypted)'),
('mail_encryption', 'tls', 'string', 'SMTP encryption (tls/ssl)'),
('mail_from_address', 'noreply@domainmonitor.com', 'string', 'From email address'),
('mail_from_name', 'Domain Monitor', 'string', 'From name'),

-- Monitoring settings
('notification_days_before', '60,30,21,14,7,5,3,2,1', 'string', 'Notification days before expiration'),
('check_interval_hours', '24', 'string', 'Domain check interval in hours'),
('last_check_run', NULL, 'datetime', 'Last time cron job ran'),

-- Authentication settings
('registration_enabled', '0', 'boolean', 'Enable user registration'),
('require_email_verification', '1', 'boolean', 'Require email verification for new users')

ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- =====================================================
-- MIGRATION TRACKING
-- =====================================================

-- Migrations tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_migration (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mark this consolidated migration as executed
INSERT INTO migrations (migration) VALUES ('000_initial_schema_v1.1.0.sql')
ON DUPLICATE KEY UPDATE migration=migration;

