-- Create error logs table for debugging and error tracking
-- This table stores all application errors for analysis and troubleshooting

CREATE TABLE IF NOT EXISTS error_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    error_id VARCHAR(32) UNIQUE NOT NULL COMMENT 'Unique reference ID for user reporting',
    error_type VARCHAR(100) NOT NULL COMMENT 'Exception class name',
    error_message TEXT NOT NULL COMMENT 'Error message',
    error_file VARCHAR(500) NOT NULL COMMENT 'File where error occurred',
    error_line INT NOT NULL COMMENT 'Line number where error occurred',
    stack_trace TEXT COMMENT 'Full stack trace',
    
    -- Request context
    request_method VARCHAR(10) COMMENT 'HTTP method (GET, POST, etc)',
    request_uri VARCHAR(500) COMMENT 'Request URI',
    request_data TEXT COMMENT 'JSON encoded POST/GET data (sanitized)',
    
    -- User context
    user_id INT NULL COMMENT 'User who encountered the error',
    user_agent TEXT COMMENT 'Browser user agent string',
    ip_address VARCHAR(45) COMMENT 'IP address (IPv4 or IPv6)',
    session_data TEXT COMMENT 'Session data (sanitized, no passwords)',
    
    -- System context
    php_version VARCHAR(20) COMMENT 'PHP version at time of error',
    memory_usage BIGINT COMMENT 'Memory usage in bytes',
    
    -- Tracking
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'First occurrence timestamp',
    occurrences INT DEFAULT 1 COMMENT 'Number of times this error occurred',
    last_occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last occurrence timestamp',
    
    -- Management
    is_resolved BOOLEAN DEFAULT FALSE COMMENT 'Admin marked as resolved',
    resolved_at TIMESTAMP NULL COMMENT 'When marked as resolved',
    resolved_by INT NULL COMMENT 'Admin user who resolved it',
    notes TEXT COMMENT 'Admin notes about resolution',
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    KEY idx_error_id (error_id),
    KEY idx_error_type (error_type),
    KEY idx_occurred_at (occurred_at),
    KEY idx_user_id (user_id),
    KEY idx_is_resolved (is_resolved),
    KEY idx_occurrences (occurrences)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

