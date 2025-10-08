-- Add complete_workflow to import_type enum
ALTER TABLE tld_import_logs 
MODIFY COLUMN import_type ENUM('tld_list', 'rdap', 'whois', 'manual', 'complete_workflow', 'check_updates') NOT NULL;
