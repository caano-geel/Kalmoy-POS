-- Users / Staff management columns
-- Run once in phpMyAdmin or: mysql -u root ash_pos_db < database/update_users_management.sql

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email` varchar(250) DEFAULT NULL AFTER `username`,
  ADD COLUMN IF NOT EXISTS `phone` varchar(50) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `status` tinyint(1) NOT NULL DEFAULT 1 AFTER `type`,
  ADD COLUMN IF NOT EXISTS `permissions` text DEFAULT NULL COMMENT 'JSON per-user permissions for staff' AFTER `status`;

-- Ensure default admin stays active
UPDATE `users` SET `status` = 1 WHERE `status` IS NULL OR `status` = 0 AND `type` = 1;
