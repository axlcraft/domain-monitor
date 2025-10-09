-- Link remember tokens to sessions
-- This ensures deleting a session also invalidates the remember token

-- Add session_id column to remember_tokens
ALTER TABLE `remember_tokens` 
ADD COLUMN `session_id` VARCHAR(128) DEFAULT NULL AFTER `user_id`,
ADD INDEX `idx_session_id` (`session_id`);
