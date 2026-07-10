-- CBPOS: wipe test / seed business data for a fresh start
-- Clean transactional data for a fresh production start.
-- Prefer database/production_schema.sql for new InfinityFree installs.
-- Target production DB (manual phpMyAdmin): if0_42375362_kalmoyposdb
-- WARNING: Destructive. Do not run on a live shop with real sales unless intentional.
--
-- IMPORTANT: Select ALL text below and click Go (run as one script).
--
-- REMOVES: test sales, orders, expenses, seed products, stock, logs, notifications
-- KEEPS:   admin users, shop settings (system_info), brand, categories, Walk-in customer

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `order_list`;
DELETE FROM `sales`;
DELETE FROM `cart`;
DELETE FROM `orders`;
DELETE FROM `expenses`;
DELETE FROM `inventory`;
DELETE FROM `products`;
DELETE FROM `admin_activity_log`;
DELETE FROM `backup_logs`;
DELETE FROM `notifications`;

ALTER TABLE `order_list` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `cart` AUTO_INCREMENT = 1;
ALTER TABLE `orders` AUTO_INCREMENT = 1;
ALTER TABLE `expenses` AUTO_INCREMENT = 1;
ALTER TABLE `inventory` AUTO_INCREMENT = 1;
ALTER TABLE `products` AUTO_INCREMENT = 1;
ALTER TABLE `admin_activity_log` AUTO_INCREMENT = 1;
ALTER TABLE `backup_logs` AUTO_INCREMENT = 1;
ALTER TABLE `notifications` AUTO_INCREMENT = 1;

DELETE FROM `clients` WHERE `email` != 'pos.walkin@local';

INSERT INTO `clients` (
  `firstname`, `lastname`, `gender`, `contact`, `email`, `password`,
  `default_delivery_address`, `status`, `delete_flag`, `date_created`
)
SELECT
  'Walk-in', 'Customer', 'N/A', '0000000000', 'pos.walkin@local',
  'e516cfe749aa7f31dbaf567b07985bde', 'In-Store POS', 1, 0, NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `clients` WHERE `email` = 'pos.walkin@local'
);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'products' AS tbl, COUNT(*) AS row_count FROM `products`
UNION ALL SELECT 'inventory', COUNT(*) FROM `inventory`
UNION ALL SELECT 'orders', COUNT(*) FROM `orders`
UNION ALL SELECT 'sales', COUNT(*) FROM `sales`
UNION ALL SELECT 'expenses', COUNT(*) FROM `expenses`
UNION ALL SELECT 'users', COUNT(*) FROM `users`
UNION ALL SELECT 'categories', COUNT(*) FROM `categories`
UNION ALL SELECT 'brands', COUNT(*) FROM `brands`
UNION ALL SELECT 'clients', COUNT(*) FROM `clients`;
