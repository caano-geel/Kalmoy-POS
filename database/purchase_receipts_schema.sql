-- Wholesale purchase receipt audit tables (created automatically by import_wholesale_receipts.php)
-- Run manually only if you need the tables before the PHP import.

CREATE TABLE IF NOT EXISTS `purchase_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL,
  `supplier` varchar(250) NOT NULL,
  `receipt_date` date NOT NULL,
  `customer` varchar(250) DEFAULT NULL,
  `total_amount` double NOT NULL DEFAULT 0,
  `currency` varchar(10) NOT NULL DEFAULT 'KES',
  `notes` text DEFAULT NULL,
  `date_imported` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_purchase_receipt_no` (`receipt_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_receipt_id` int(11) NOT NULL,
  `line_no` int(11) NOT NULL DEFAULT 0,
  `item_code` varchar(100) DEFAULT NULL,
  `item_name` varchar(250) NOT NULL,
  `quantity` double NOT NULL DEFAULT 0,
  `unit_price` double NOT NULL DEFAULT 0,
  `line_total` double NOT NULL DEFAULT 0,
  `product_id` int(11) DEFAULT NULL,
  `inventory_id` int(11) DEFAULT NULL,
  `variant` varchar(100) NOT NULL DEFAULT 'Default',
  PRIMARY KEY (`id`),
  KEY `idx_pri_receipt` (`purchase_receipt_id`),
  KEY `idx_pri_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
