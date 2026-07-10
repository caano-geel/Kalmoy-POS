-- CBPOS: FULL catalog wipe (use only if you also want to remove categories)
-- Run AFTER clean_production_data.sql, or use this alone if products are already empty.
--
-- KEEPS: users, system_info, brands, Walk-in customer
-- REMOVES: all categories (re-add under Maintenance → Categories)

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `categories`;
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'categories' AS tbl, COUNT(*) AS row_count FROM `categories`;
