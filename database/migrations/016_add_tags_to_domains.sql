-- Add tags column to domains table
-- This allows users to organize domains with custom tags

ALTER TABLE domains 
ADD COLUMN tags TEXT NULL COMMENT 'Comma-separated tags for organization' 
AFTER notes;

-- Add index for tag searches
ALTER TABLE domains 
ADD INDEX idx_tags (tags(255));
