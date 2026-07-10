-- Kalmoy POS production schema + seed data
-- Import into InfinityFree: if0_42375362_kalmoyposdb (phpMyAdmin)
-- Also suitable for local XAMPP: ash_pos_db
--
-- SECURITY: No administrator login is included in this public file.
-- After import, create the first admin with database/create_production_admin.private.sql
-- (copy from create_production_admin.private.sql.example — never commit the private file).
-- The app login page works with an empty users table (login simply fails until an admin exists).
--
-- Excludes transactional data: orders, sales, expenses, activity logs, notifications, backups
-- Includes catalog seed (brands/categories/products/inventory) + empty users schema

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `admin_activity_log`;
CREATE TABLE `admin_activity_log` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) DEFAULT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_activity_date` (`date_created`),
  KEY `idx_admin_activity_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `backup_logs`;
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_by_name` varchar(150) NOT NULL DEFAULT '',
  `status` varchar(50) NOT NULL DEFAULT 'success',
  `message` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
INSERT INTO `brands` VALUES ('1', 'AL Sunnah Herbal', 'Al Sunnah Herbal offers natural herbal, wellness, and personal care products.', 'assets/branding/brands/1.png', '1', '0', '2026-06-27 23:30:36');
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `client_id` int(30) NOT NULL,
  `inventory_id` int(30) NOT NULL,
  `price` double NOT NULL,
  `quantity` int(30) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `category` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
INSERT INTO `categories` VALUES ('1', 'Hair Care', 'Hair growth and treatment products.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('2', 'Skin Care', 'Products for healthy and glowing skin.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('3', 'Herbal Supplements', 'Natural supplements for wellness.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('4', 'Men\'s Health', 'Herbal products for men\'s wellness.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('5', 'Women\'s Health', 'Products supporting women\'s health.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('6', 'Traditional Herbal Products', 'Traditional herbal remedies and care.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('7', 'Weight Management', 'Products for weight and body support.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('8', 'Beauty & Cosmetics', 'Beauty and cosmetic care products.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('9', 'Oral & Dental Care', 'Products for oral and dental hygiene.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('10', 'Natural Oils', 'Natural oils for health and beauty.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('11', 'Perfumes & Fragrances', 'Fragrances and scented products.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('12', 'Energy & Wellness', 'Products for energy and vitality.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('13', 'Herbal Drinks & Foods', 'Herbal drinks and nutritional foods.', '1', '0', '2026-06-27 23:24:09');
INSERT INTO `categories` VALUES ('14', 'Personal Care', 'Daily personal hygiene products.', '1', '0', '2026-06-27 23:24:09');
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(250) NOT NULL,
  `lastname` varchar(250) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` text NOT NULL,
  `default_delivery_address` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
INSERT INTO `clients` VALUES ('4', 'Walk-in', 'Customer', 'N/A', '0000000000', 'pos.walkin@local', 'e516cfe749aa7f31dbaf567b07985bde', 'In-Store POS', '1', '0', '2026-06-28 02:15:48');
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
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
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `variant` text NOT NULL,
  `product_id` int(30) NOT NULL,
  `quantity` double NOT NULL,
  `price` float NOT NULL,
  `cost_price` double NOT NULL DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4;
INSERT INTO `inventory` VALUES ('1', 'Default', '1', '38', '1000', '0', NULL, '2026-06-27 23:50:53', NULL);
INSERT INTO `inventory` VALUES ('2', 'Default', '2', '37', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('3', 'Default', '3', '18', '1000', '500', NULL, '2026-06-28 00:07:47', '2026-06-28 07:45:56');
INSERT INTO `inventory` VALUES ('4', 'Default', '4', '20', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('5', 'Default', '5', '32', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('6', 'Default', '6', '27', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('7', 'Default', '7', '16', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('8', 'Default', '8', '50', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('9', 'Default', '9', '11', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('10', 'Default', '10', '8', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('11', 'Default', '11', '20', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('12', 'Default', '12', '20', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('13', 'Default', '13', '30', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('14', 'Default', '14', '9', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('15', 'Default', '15', '6', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('16', 'Default', '16', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('17', 'Default', '17', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('18', 'Default', '18', '14', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('19', 'Default', '19', '1', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('20', 'Default', '20', '15', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('21', 'Default', '21', '16', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('22', 'Default', '22', '25', '1000', '500', NULL, '2026-06-28 00:07:47', '2026-06-28 07:46:53');
INSERT INTO `inventory` VALUES ('23', 'Default', '23', '24', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('24', 'Default', '24', '27', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('25', 'Default', '25', '24', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('26', 'Default', '26', '8', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('27', 'Default', '27', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('28', 'Default', '28', '21', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('29', 'Default', '29', '6', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('30', 'Default', '30', '14', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('31', 'Default', '31', '1', '1000', '0', NULL, '2026-06-28 00:07:47', '2026-06-28 08:35:50');
INSERT INTO `inventory` VALUES ('32', 'Default', '32', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('33', 'Default', '33', '8', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('34', 'Default', '34', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('35', 'Default', '35', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('36', 'Default', '36', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('37', 'Default', '37', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('38', 'Default', '38', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('39', 'Default', '39', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('40', 'Default', '40', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('41', 'Default', '41', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('42', 'Default', '42', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('43', 'Default', '43', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('44', 'Default', '44', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('45', 'Default', '45', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('46', 'Default', '46', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('47', 'Default', '47', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('48', 'Default', '48', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('49', 'Default', '49', '10', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('50', 'Default', '50', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('51', 'Default', '51', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('52', 'Default', '52', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('53', 'Default', '53', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('54', 'Default', '54', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('55', 'Default', '55', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('56', 'Default', '56', '2', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('57', 'Default', '57', '8', '1000', '0', NULL, '2026-06-28 00:07:47', '2026-06-28 07:57:26');
INSERT INTO `inventory` VALUES ('58', 'Default', '58', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('59', 'Default', '59', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('60', 'Default', '60', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('61', 'Default', '61', '0', '1000', '0', NULL, '2026-06-28 00:07:47', '2026-06-28 08:56:52');
INSERT INTO `inventory` VALUES ('62', 'Default', '62', '5', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('63', 'Default', '63', '3', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
INSERT INTO `inventory` VALUES ('64', 'Default', '64', '4', '1000', '0', NULL, '2026-06-28 00:07:47', NULL);
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
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
  KEY `idx_notifications_read` (`is_read`,`date_created`),
  KEY `idx_notifications_ref` (`ref_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `order_list`;
CREATE TABLE `order_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `order_id` int(30) NOT NULL,
  `inventory_id` int(30) NOT NULL,
  `quantity` int(30) NOT NULL,
  `price` double NOT NULL,
  `total` double NOT NULL,
  `cost_price` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_list_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_list_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `ref_code` varchar(100) NOT NULL,
  `client_id` int(30) NOT NULL,
  `delivery_address` text NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `order_type` tinyint(1) NOT NULL COMMENT '1= pickup,2= deliver',
  `amount` double NOT NULL,
  `discount_total` double NOT NULL DEFAULT 0,
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0 = pending,\r\n1= Packed,\r\n2 = Out for Delivery,\r\n3=Delivered,\r\n4=cancelled',
  `paid` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `brand_id` int(30) NOT NULL,
  `category_id` int(30) NOT NULL,
  `name` varchar(250) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `specs` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `brand_id` (`brand_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4;
INSERT INTO `products` VALUES ('1', '1', '8', 'Indho-Kuul', '8901234567890', '&lt;p&gt;Indho-Kuul is a traditional herbal eye cosmetic used for beauty and personal care. Suitable for daily use.&lt;/p&gt;', '1', '0', '2026-06-27 23:45:30');
INSERT INTO `products` VALUES ('2', '1', '2', 'Scar Remover', NULL, '<p>Scar Remover is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('3', '1', '1', 'Shampoo 3 in 1', NULL, '<p>Shampoo 3 in 1 is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('4', '1', '3', 'Shilajid Seamoses', NULL, '<p>Shilajid Seamoses is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('5', '1', '6', 'Jilbahaha', NULL, '<p>Jilbahaha is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('6', '1', '9', 'Teeth Restoration', NULL, '<p>Teeth Restoration is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('7', '1', '3', 'Calcium', NULL, '<p>Calcium is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('8', '1', '8', 'Qays', NULL, '<p>Qays is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('9', '1', '8', 'Mino Glow', NULL, '<p>Mino Glow is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('10', '1', '1', 'Shampoo Mino Glow', NULL, '<p>Shampoo Mino Glow is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('11', '1', '7', 'Slim Cream', NULL, '<p>Slim Cream is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('12', '1', '4', 'Titan hel', NULL, '<p>Titan hel is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('13', '1', '3', 'Magnesium', NULL, '<p>Magnesium is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('14', '1', '4', 'Prosta', NULL, '<p>Prosta is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('15', '1', '12', 'Chocolate', NULL, '<p>Chocolate is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('16', '1', '6', 'Neem', NULL, '&lt;p&gt;Neem is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.&lt;/p&gt;', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('17', '1', '13', 'Hadjoul', NULL, '<p>Hadjoul is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('18', '1', '3', 'Biotin', NULL, '<p>Biotin is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('19', '1', '4', 'Black Horse', NULL, '<p>Black Horse is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('20', '1', '14', 'Spray', NULL, '<p>Spray is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('21', '1', '12', 'Boost', NULL, '<p>Boost is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('22', '1', '10', 'Batana Oil', '9789914467543', '&lt;p&gt;Batana Oil is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.&lt;/p&gt;', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('23', '1', '13', 'Men Coffee', NULL, '<p>Men Coffee is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('24', '1', '13', 'Arabica', '01266115', '&lt;p&gt;Arabica is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.&lt;/p&gt;', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('25', '1', '6', 'Macun', NULL, '<p>Macun is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('26', '1', '10', 'Saliid Herbal', NULL, '<p>Saliid Herbal is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('27', '1', '6', 'Man hah', NULL, '<p>Man hah is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '1', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('28', '1', '7', 'Organic', NULL, '<p>Organic is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('29', '1', '13', 'Xanjo', NULL, '<p>Xanjo is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('30', '1', '14', 'DR5', NULL, '<p>DR5 is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('31', '1', '9', 'Happy Cleaner', NULL, '<p>Happy Cleaner is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('32', '1', '2', 'Kojii', NULL, '<p>Kojii is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('33', '1', '2', 'Assantee', NULL, '<p>Assantee is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('34', '1', '8', 'Watva', NULL, '<p>Watva is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('35', '1', '3', 'Vitamin C', NULL, '<p>Vitamin C is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('36', '1', '1', 'Minoxidil', NULL, '<p>Minoxidil is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('37', '1', '8', 'MK', NULL, '<p>MK is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('38', '1', '3', 'Turmeric', NULL, '<p>Turmeric is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('39', '1', '1', 'Morocan', NULL, '<p>Morocan is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('40', '1', '6', 'Macun Bac', NULL, '<p>Macun Bac is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('41', '1', '11', 'Miski', NULL, '<p>Miski is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('42', '1', '1', 'Skala', NULL, '<p>Skala is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('43', '1', '2', 'Glysolid Pig', NULL, '<p>Glysolid Pig is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('44', '1', '2', 'Glysolid Mid', NULL, '<p>Glysolid Mid is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('45', '1', '2', 'Glysolid Small', NULL, '<p>Glysolid Small is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('46', '1', '2', 'Location Pig', NULL, '<p>Location Pig is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('47', '1', '5', 'Pretty Small', NULL, '<p>Pretty Small is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('48', '1', '11', 'Unsi', NULL, '<p>Unsi is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('49', '1', '6', 'Bahasha Kilkillaha', NULL, '<p>Bahasha Kilkillaha is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('50', '1', '14', 'Sakiin', NULL, '<p>Sakiin is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('51', '1', '5', 'M2 Tone', NULL, '<p>M2 Tone is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('52', '1', '10', 'Oliva', NULL, '<p>Oliva is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('53', '1', '1', 'Hair Treatmen', NULL, '<p>Hair Treatmen is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('54', '1', '1', 'Silky', NULL, '<p>Silky is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('55', '1', '8', 'Shark Porler', NULL, '<p>Shark Porler is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('56', '1', '2', 'Skin Doctor', NULL, '<p>Skin Doctor is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('57', '1', '2', 'Gates Bay', NULL, '<p>Gates Bay is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('58', '1', '12', 'CBC', NULL, '<p>CBC is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('59', '1', '3', 'Ashwaganda', NULL, '<p>Ashwaganda is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('60', '1', '2', 'Papaya', NULL, '<p>Papaya is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('61', '1', '1', 'Mayonese', NULL, '<p>Mayonese is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('62', '1', '4', 'Penfera', NULL, '<p>Penfera is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('63', '1', '12', 'Mega', NULL, '<p>Mega is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('64', '1', '2', 'Face Wash', NULL, '<p>Face Wash is a quality herbal, beauty, or wellness product from Al Sunnah Herbal.</p>', '1', '0', '2026-06-28 00:07:47');
INSERT INTO `products` VALUES ('65', '1', '4', 'Man Sample', NULL, '&lt;p&gt;Kani waxaa loo isticmaalaa si ka fiican&amp;nbsp;&lt;/p&gt;', '1', '1', '2026-06-28 00:09:18');
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `order_id` int(30) NOT NULL,
  `total_amount` double NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `system_info`;
CREATE TABLE `system_info` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;
INSERT INTO `system_info` VALUES ('1', 'name', 'Kalmoy POS');
INSERT INTO `system_info` VALUES ('6', 'short_name', 'Kalmoy');
INSERT INTO `system_info` VALUES ('11', 'logo', 'assets/branding/logo.png');
INSERT INTO `system_info` VALUES ('13', 'user_avatar', 'dist/img/no-image-available.png');
INSERT INTO `system_info` VALUES ('14', 'cover', 'assets/branding/cover.jpg');
INSERT INTO `system_info` VALUES ('15', 'scanner_sound_mode', 'uploaded');
INSERT INTO `system_info` VALUES ('16', 'scanner_sound_file', 'assets/branding/scanner-sound.mp3');
INSERT INTO `system_info` VALUES ('17', 'cashier_permissions', '{\"dashboard_full\":0,\"dashboard_limited\":0,\"pos\":1,\"products\":0,\"inventory_view\":1,\"inventory_manage\":0,\"orders_view\":0,\"orders_manage\":1,\"clients\":0,\"sales_report\":0,\"brands\":0,\"categories\":0,\"settings\":0,\"permissions\":0,\"my_account\":1,\"delete_actions\":0}');
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(250) NOT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` text NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `permissions` text DEFAULT NULL,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `customer_debts`;
CREATE TABLE `customer_debts` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `client_id` int(30) NOT NULL,
  `order_id` int(30) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `balance` double NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'current',
  `due_date` date DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `order_id` (`order_id`),
  KEY `due_date` (`due_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `debt_payments`;
CREATE TABLE `debt_payments` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `client_id` int(30) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `payment_method` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(30) DEFAULT NULL,
  `created_by_name` varchar(250) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;