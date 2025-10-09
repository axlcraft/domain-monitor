-- Migration: Add CAPTCHA settings
-- Version: 1.2.0
-- Description: Add support for CAPTCHA protection (reCAPTCHA v2, v3, Turnstile)

-- Add CAPTCHA provider setting (disabled, recaptcha_v2, recaptcha_v3, turnstile)
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
VALUES ('captcha_provider', 'disabled', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Add CAPTCHA site key (public key)
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
VALUES ('captcha_site_key', '', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Add CAPTCHA secret key (will be encrypted)
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
VALUES ('captcha_secret_key', '', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Add reCAPTCHA v3 score threshold (minimum score required, 0.0 to 1.0)
INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
VALUES ('recaptcha_v3_score_threshold', '0.5', NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

