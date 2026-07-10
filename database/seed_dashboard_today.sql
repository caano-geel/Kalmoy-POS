-- Patch: populate Today's Expenses and Pending Orders for dashboard testing
-- Safe to re-run: uses new IDs only
SET FOREIGN_KEY_CHECKS=0;

-- Today's expenses (expense_date = CURDATE())
INSERT INTO `expenses` (`id`,`expense_date`,`category`,`description`,`amount`,`payment_method`,`created_by`,`created_by_name`,`date_created`) VALUES
(167, CURDATE(), 'Transport', 'Matatu fare for stock delivery to Eastleigh branch', 850, 'Cash', 1, 'Adminstrator Owner', CONCAT(CURDATE(), ' 09:15:00')),
(168, CURDATE(), 'Supplies', 'Receipt rolls and packaging bags for POS counter', 2400, 'M-Pesa', 3, 'Abdi Ali Farah', CONCAT(CURDATE(), ' 11:30:00')),
(169, CURDATE(), 'Utilities', 'Partial electricity token top-up for shop', 5000, 'M-Pesa', 1, 'Adminstrator Owner', CONCAT(CURDATE(), ' 13:45:00')),
(170, CURDATE(), 'Maintenance', 'Minor repair of display shelf in store', 3200, 'Cash', 8, 'Fadumo Yusuf Ahmed', CONCAT(CURDATE(), ' 15:20:00')),
(171, CURDATE(), 'Marketing', 'WhatsApp boost for weekend herbal promo', 1500, 'M-Pesa', 4, 'Amina Mohamed Hassan', CONCAT(CURDATE(), ' 17:00:00'));

-- Pending orders (status = 0, not yet completed)
INSERT INTO `orders` (`id`,`ref_code`,`client_id`,`delivery_address`,`payment_method`,`order_type`,`amount`,`discount_total`,`status`,`paid`,`date_created`) VALUES
(557, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00557'), 8, 'Nairobi, Kenya - Estate 4 - Abdi Ali Farah', 'M-Pesa', 2, 4500, 0, 0, 0, CONCAT(CURDATE(), ' 08:22:00')),
(558, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00558'), 12, 'Nairobi, Kenya - Estate 8 - Khadija Ali Mohamed', 'Cash', 1, 2600, 200, 0, 0, CONCAT(CURDATE(), ' 09:45:00')),
(559, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00559'), 15, 'Nairobi, Kenya - Estate 11 - Hassan Omar Jama', 'M-Pesa', 2, 6200, 0, 0, 0, CONCAT(CURDATE(), ' 10:30:00')),
(560, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00560'), 22, 'Nairobi, Kenya - Estate 6 - Maryan Yusuf Ahmed', 'Cash', 1, 1500, 0, 0, 0, CONCAT(CURDATE(), ' 11:15:00')),
(561, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00561'), 31, 'Nairobi, Kenya - Estate 3 - Fatima Hassan Abdi', 'M-Pesa', 2, 8400, 500, 0, 0, CONCAT(CURDATE(), ' 12:40:00')),
(562, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00562'), 38, 'Nairobi, Kenya - Estate 10 - Ahmed Farah Hassan', 'Cash', 1, 3200, 0, 0, 0, CONCAT(CURDATE(), ' 14:05:00')),
(563, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00563'), 44, 'Nairobi, Kenya - Estate 5 - Hawa Mohamed Ali', 'M-Pesa', 2, 5400, 0, 0, 0, CONCAT(CURDATE(), ' 15:50:00')),
(564, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00564'), 49, 'Nairobi, Kenya - Estate 9 - Nimco Salad Mohamed', 'Cash', 1, 2000, 100, 0, 0, CONCAT(CURDATE(), ' 16:35:00')),
(565, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00565'), 52, 'Nairobi, Kenya - Estate 12 - Sagal Ibrahim Ali', 'M-Pesa', 2, 7600, 0, 0, 0, CONCAT(CURDATE(), ' 17:20:00')),
(566, CONCAT(DATE_FORMAT(CURDATE(),'%Y%m'),'00566'), 6, 'Nairobi, Kenya - Estate 2 - Omar Hassan Ibrahim', 'Cash', 1, 3800, 0, 0, 0, CONCAT(CURDATE(), ' 18:10:00'));

-- Line items for pending orders
INSERT INTO `order_list` (`id`,`order_id`,`inventory_id`,`quantity`,`price`,`total`,`cost_price`) VALUES
(1346, 557, 22, 2, 1000, 2000, 500),
(1347, 557, 67, 1, 2500, 2500, 1250),
(1348, 558, 3, 1, 1000, 1000, 650),
(1349, 558, 32, 2, 900, 1800, 405),
(1350, 559, 14, 3, 1500, 4500, 675),
(1351, 559, 70, 1, 1800, 1800, 900),
(1352, 560, 9, 1, 1500, 1500, 825),
(1353, 561, 4, 2, 2000, 4000, 1300),
(1354, 561, 76, 2, 2500, 5000, 1250),
(1355, 562, 18, 2, 1200, 2400, 780),
(1356, 562, 55, 1, 800, 800, 440),
(1357, 563, 11, 2, 1500, 3000, 825),
(1358, 563, 59, 2, 1200, 2400, 780),
(1359, 564, 52, 1, 900, 900, 495),
(1360, 564, 33, 2, 600, 1200, 330),
(1361, 565, 74, 2, 2800, 5600, 1400),
(1362, 565, 25, 1, 2000, 2000, 1100),
(1363, 566, 7, 2, 1000, 2000, 650),
(1364, 566, 38, 1, 1800, 1800, 990);

SET FOREIGN_KEY_CHECKS=1;
