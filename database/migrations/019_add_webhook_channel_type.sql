-- Add 'webhook' and 'mattermost' to the channel_type ENUM in notification_channels table
-- This allows custom webhook integrations and Mattermost support

ALTER TABLE notification_channels 
MODIFY COLUMN channel_type ENUM('email', 'telegram', 'discord', 'slack', 'mattermost', 'webhook') NOT NULL;
