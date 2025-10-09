-- Add application version to settings
INSERT INTO settings (setting_key, setting_value) VALUES
('app_version', '1.1.0')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

