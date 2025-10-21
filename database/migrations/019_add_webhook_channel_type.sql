-- Add 'webhook' to the channel_type ENUM in notification_channels table
-- This allows custom webhook integrations like Mattermost

ALTER TABLE notification_channels 
MODIFY COLUMN channel_type ENUM('email', 'telegram', 'discord', 'slack', 'webhook') NOT NULL;
