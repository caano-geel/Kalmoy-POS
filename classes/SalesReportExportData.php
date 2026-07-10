<?php
/**
 * Sales report row builder for exports (shared with Sales Report page logic).
 */
class SalesReportExportData {
    public static function customerName($row){
        if(!empty($row['delivery_address']) && preg_match('/Customer:\s*(.+)$/i', $row['delivery_address'], $matches)){
            return trim($matches[1]);
        }
        $name = trim($row['client_name']);
        if($name === '' || stripos($name, 'Walk-in') !== false) return 'Walk-in Customer';
        return $name;
    }

    public static function paymentLabel($method){
        if(strcasecmp($method, 'Cash') === 0) return 'Cash';
        if(strcasecmp($method, 'M-Pesa') === 0) return 'M-Pesa';
        return $method;
    }

    public static function costColumn($conn){
        static $column = false;
        if($column !== false) return $column;
        $column = null;
        if(!$conn) return $column;
        $check = $conn->query("SHOW COLUMNS FROM `inventory`");
        if($check){
            $allowed = array('cost_price', 'cost', 'buy_price');
            while($col = $check->fetch_assoc()){
                if(in_array(strtolower($col['Field']), $allowed, true)){
                    $column = $col['Field'];
                    break;
                }
            }
        }
        return $column;
    }

    public static function rows($conn, $date_start, $date_end, $payment_filter, $show_profit){
        $payment_sql = '';
        if($payment_filter !== '' && in_array($payment_filter, array('Cash', 'M-Pesa'), true)){
            $payment_esc = $conn->real_escape_string($payment_filter);
            $payment_sql = " AND o.payment_method = '{$payment_esc}' ";
        }
        $cost_column = $show_profit ? self::costColumn($conn) : null;
        if($show_profit && $cost_column && db_table_has_column('order_list', 'cost_price')){
            $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
        }elseif($show_profit && $cost_column){
            $cost_select = "i.`{$cost_column}` AS cost_price";
        }else{
            $cost_select = "NULL AS cost_price";
        }
        $discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';
        $sql = "SELECT s.date_created AS sale_date, o.id AS order_id, o.ref_code, o.payment_method, o.date_created AS order_date,
            o.delivery_address, {$discount_select}, ol.quantity, ol.price, p.name AS product_name,
            {$cost_select}, CONCAT(c.firstname,' ',c.lastname) AS client_name
            FROM sales s
            INNER JOIN orders o ON o.id = s.order_id
            INNER JOIN order_list ol ON ol.order_id = o.id
            INNER JOIN inventory i ON ol.inventory_id = i.id
            INNER JOIN products p ON p.id = i.product_id
            INNER JOIN clients c ON c.id = o.client_id
            WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'{$payment_sql}
            ORDER BY UNIX_TIMESTAMP(s.date_created) DESC, ol.id ASC";
        $qry = $conn->query($sql);
        $raw = array();
        while($qry && ($row = $qry->fetch_assoc())){
            $raw[] = $row;
        }
        $order_subtotals = array();
        foreach($raw as $row){
            $oid = $row['order_id'];
            if(!isset($order_subtotals[$oid])) $order_subtotals[$oid] = 0;
            $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
        }
        $rows = array();
        foreach($raw as $row){
            $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
            $line_total = (float)$row['quantity'] * (float)$row['price'];
            $profit_calculable = false;
            $line_profit = null;
            if($show_profit && array_key_exists('cost_price', $row) && $row['cost_price'] !== null && $row['cost_price'] !== '' && (float)$row['cost_price'] > 0){
                $profit_calculable = true;
                $line_profit = $line_total - ((float)$row['cost_price'] * (int)$row['quantity']);
            }
            $rows[] = array(
                'order_date' => $row['order_date'],
                'ref_code' => stripslashes($row['ref_code']),
                'product_name' => stripslashes($row['product_name']),
                'customer_name' => self::customerName($row),
                'payment_label' => self::paymentLabel($row['payment_method']),
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'line_total' => $line_total,
                'line_profit' => $line_profit,
                'profit_calculable' => $profit_calculable,
            );
        }
        return array('rows' => $rows);
    }
}
