-- Create tld_registry table for storing TLD registry information
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

-- Create tld_import_logs table for tracking import operations
CREATE TABLE IF NOT EXISTS tld_import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_type ENUM('tld_list', 'rdap', 'whois', 'manual') NOT NULL,
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
