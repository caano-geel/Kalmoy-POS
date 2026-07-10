-- Rebalance YTD operating expenses so net profit is positive and realistic.
-- Formula verified: Net Profit = Gross Profit - Operating Expenses
-- Run after seed_dummy_data.sql if YTD net profit is unrealistically negative.
--
-- Targets ~12% net margin on YTD revenue (scales 2026 expenses to ~60% of seeded values).

SET @ytd_start = DATE_FORMAT(CURDATE(), '%Y-01-01');
SET @ytd_end = CURDATE();

-- Preview (optional): SELECT SUM(amount) FROM expenses WHERE delete_flag=0 AND expense_date BETWEEN @ytd_start AND @ytd_end;

UPDATE `expenses`
SET `amount` = ROUND(`amount` * 0.60, 2)
WHERE `delete_flag` = 0
  AND `expense_date` >= @ytd_start
  AND `expense_date` <= @ytd_end;

-- Expected after rebalance (approximate, depends on current sales data):
-- YTD Gross Profit ~650k | YTD Expenses ~490k | YTD Net Profit ~160k (~12% of revenue)
