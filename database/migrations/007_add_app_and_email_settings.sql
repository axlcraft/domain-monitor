-- Add application and email settings to database
INSERT INTO settings (setting_key, setting_value) VALUES
-- Application Settings
('app_name', 'Domain Monitor'),
('app_url', 'http://localhost:8000'),
('app_timezone', 'UTC'),

-- Email Settings
('mail_host', 'smtp.mailtrap.io'),
('mail_port', '2525'),
('mail_username', ''),
('mail_password', ''),
('mail_encryption', 'tls'),
('mail_from_address', 'noreply@domainmonitor.com'),
('mail_from_name', 'Domain Monitor')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

