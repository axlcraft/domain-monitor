-- Create notification_groups table
CREATE TABLE IF NOT EXISTS notification_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification_channels table
CREATE TABLE IF NOT EXISTS notification_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_group_id INT NOT NULL,
    channel_type ENUM('email', 'telegram', 'discord', 'slack') NOT NULL,
    channel_config JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_group_id) REFERENCES notification_groups(id) ON DELETE CASCADE,
    INDEX idx_notification_group_id (notification_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create domains table
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    notification_group_id INT,
    registrar VARCHAR(255),
    expiration_date DATE,
    last_checked TIMESTAMP NULL,
    status ENUM('active', 'expiring_soon', 'expired', 'error', 'available') DEFAULT 'active',
    whois_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_group_id) REFERENCES notification_groups(id) ON DELETE SET NULL,
    INDEX idx_notification_group_id (notification_group_id),
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification_logs table
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

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('notification_days_before', '30,15,7,3,1'),
('check_interval_hours', '24'),
('last_check_run', NULL)
ON DUPLICATE KEY UPDATE setting_key=setting_key;

