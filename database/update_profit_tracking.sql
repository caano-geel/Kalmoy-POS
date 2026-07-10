-- Profit tracking columns (safe additive migration)
ALTER TABLE `inventory`
  ADD COLUMN `cost_price` double NOT NULL DEFAULT 0 AFTER `price`;

ALTER TABLE `orders`
  ADD COLUMN `discount_total` double NOT NULL DEFAULT 0 AFTER `amount`;

ALTER TABLE `order_list`
  ADD COLUMN `cost_price` double DEFAULT NULL AFTER `total`;
