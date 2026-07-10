-- AL-Sunnah Herbal POS: Expenses, Notifications, Backup modules
-- Run manually in phpMyAdmin or MySQL CLI

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_by_name` varchar(150) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = visible to all admin users',
  `type` varchar(20) NOT NULL DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `ref_key` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_read` (`is_read`, `date_created`),
  KEY `idx_notifications_ref` (`ref_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_by_name` varchar(150) NOT NULL DEFAULT '',
  `status` varchar(50) NOT NULL DEFAULT 'success',
  `message` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: product expiry tracking for expiring-stock notifications
-- Run separately if needed: ALTER TABLE `inventory` ADD COLUMN `expiry_date` date DEFAULT NULL AFTER `cost_price`;
