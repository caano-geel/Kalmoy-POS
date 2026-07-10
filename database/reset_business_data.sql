-- Business data reset (reference SQL — prefer: php database/reset_business_data.php)
-- Creates backup automatically in the PHP script before running deletes.
-- KEEPS: users, system_info, categories, brands, walk-in client
-- DOES NOT run receipt import — use reset_business_data.php for full pipeline.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `purchase_receipt_items`;
DELETE FROM `purchase_receipts`;
DELETE FROM `order_list`;
DELETE FROM `sales`;
DELETE FROM `cart`;
DELETE FROM `orders`;
DELETE FROM `expenses`;
DELETE FROM `notifications`;
DELETE FROM `admin_activity_log`;
DELETE FROM `backup_logs`;
DELETE FROM `inventory`;

-- Remove all products except official master catalog (adjust list as needed)
DELETE FROM `products`
WHERE LOWER(`name`) NOT IN (
  'arabica','ashwaganda','assantee','bahasha kilkilaha','batana oil','biotin','black horse','boost',
  'calcium','cbc','chocolate','dr5','face wash','gates bay','glysolid mid','glysolid pig','glysolid small',
  'hadjoul','hair treatmen','happy cleaner','indho-kuul','jilbahaha','kojii','location pig','m2 tone',
  'macun','macun bac','magnesium','mayonese','mega','men coffee','mino glow','minoxidil','miski','mk',
  'morocan','neem','oliva','organic','papaya','penfera','pretty small','prostate','qays','sakiin',
  'saliid herbal','scar remover','shampoo 3 in 1','shampoo mino glow','shark power','shilajid seamoses',
  'silky','skala','skin doctor','slim cream','spray','teeth restoration','titan hel','tumeric','ultra',
  'unsi','vitamin c','xanjo'
);

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

ALTER TABLE `purchase_receipt_items` AUTO_INCREMENT = 1;
ALTER TABLE `purchase_receipts` AUTO_INCREMENT = 1;
ALTER TABLE `order_list` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `cart` AUTO_INCREMENT = 1;
ALTER TABLE `orders` AUTO_INCREMENT = 1;
ALTER TABLE `expenses` AUTO_INCREMENT = 1;
ALTER TABLE `inventory` AUTO_INCREMENT = 1;
ALTER TABLE `notifications` AUTO_INCREMENT = 1;
ALTER TABLE `admin_activity_log` AUTO_INCREMENT = 1;
ALTER TABLE `backup_logs` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Then run: php database/import_wholesale_receipts.php
