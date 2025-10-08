-- Add notes column to domains table
ALTER TABLE domains ADD COLUMN notes TEXT AFTER whois_data;

