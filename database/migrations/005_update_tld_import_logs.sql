-- Update tld_import_logs table to support TLD list imports
-- Add version field and update import_type enum

-- Add version column (will fail gracefully if column already exists)
ALTER TABLE tld_import_logs 
ADD COLUMN version VARCHAR(50) NULL AFTER iana_publication_date;

-- Update import_type enum to include 'tld_list'
-- Note: This will fail gracefully if the enum already includes 'tld_list'
ALTER TABLE tld_import_logs 
MODIFY COLUMN import_type ENUM('tld_list', 'rdap', 'whois', 'manual') NOT NULL;
