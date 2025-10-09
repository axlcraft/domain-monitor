-- Create new sessions table compatible with PHP session handler
CREATE TABLE `sessions` (
  `id` VARCHAR(128) NOT NULL PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `country` VARCHAR(100) DEFAULT NULL,
  `country_code` VARCHAR(2) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `isp` VARCHAR(255) DEFAULT NULL,
  `timezone` VARCHAR(50) DEFAULT NULL,
  `payload` MEDIUMTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_last_activity` (`last_activity`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

