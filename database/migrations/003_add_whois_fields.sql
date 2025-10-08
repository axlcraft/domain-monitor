
-- Add WHOIS-related columns to domains table
-- Note: These statements may show warnings if columns already exist, but won't fail

-- Add registrar_url column
ALTER TABLE domains ADD COLUMN registrar_url VARCHAR(255) AFTER registrar;

-- Add updated_date column  
ALTER TABLE domains ADD COLUMN updated_date DATE AFTER expiration_date;

-- Add abuse_email column
ALTER TABLE domains ADD COLUMN abuse_email VARCHAR(255) AFTER updated_date;

