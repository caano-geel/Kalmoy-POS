-- Fix negative available stock: on_hand must be >= units sold (all non-cancelled orders)
-- Available = inventory.quantity - SUM(order_list.qty WHERE order.status != 4)

UPDATE inventory i
INNER JOIN (
    SELECT ol.inventory_id, SUM(ol.quantity) AS sold
    FROM order_list ol
    INNER JOIN orders o ON o.id = ol.order_id
    WHERE o.status != 4
    GROUP BY ol.inventory_id
) s ON s.inventory_id = i.id
SET i.quantity = s.sold
WHERE i.quantity < s.sold;
