<?php
require_once __DIR__ . '/../initialize.php';
$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, $port);
if ($conn->connect_error) die($conn->connect_error);
$sql = "CREATE TABLE IF NOT EXISTS `expenses` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($conn->query($sql)) {
    echo "expenses table OK\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
