CREATE TABLE IF NOT EXISTS `admin_activity_log` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) DEFAULT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_activity_date` (`date_created`),
  KEY `idx_admin_activity_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
