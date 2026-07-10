<?php
/**
 * Customer debt (credit sales) ledger and payments.
 */
class CustomerDebtService {
    const WALKIN_EMAIL = 'pos.walkin@local';
    const LARGE_DEBT_THRESHOLD = 10000;

    public static function ensure_schema($conn){
        if(!isset($conn) || !$conn) return false;
        $sql1 = "CREATE TABLE IF NOT EXISTS `customer_debts` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $sql2 = "CREATE TABLE IF NOT EXISTS `debt_payments` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $conn->query($sql1) && $conn->query($sql2);
    }

    public static function tables_ready($conn){
        static $ready = null;
        if($ready !== null) return $ready;
        $ready = false;
        if(isset($conn) && $conn){
            $q = $conn->query("SHOW TABLES LIKE 'customer_debts'");
            $ready = ($q && $q->num_rows > 0);
        }
        return $ready;
    }

    public static function is_walkin_client($conn, $client_id){
        $client_id = (int)$client_id;
        if($client_id <= 0) return true;
        $q = $conn->query("SELECT email FROM clients WHERE id = '{$client_id}' AND delete_flag = 0 LIMIT 1");
        if(!$q || !$q->num_rows) return true;
        return strtolower(trim($q->fetch_assoc()['email'])) === self::WALKIN_EMAIL;
    }

    public static function registered_clients($conn){
        $email = $conn->real_escape_string(self::WALKIN_EMAIL);
        $rows = array();
        $q = $conn->query("SELECT id, firstname, lastname, contact, email,
            CONCAT(firstname,' ',lastname) AS fullname
            FROM clients WHERE delete_flag = 0 AND email != '{$email}' AND status = 1
            ORDER BY firstname, lastname");
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = $r;
        }
        return $rows;
    }

    public static function refresh_debt_statuses($conn){
        if(!self::tables_ready($conn)) return;
        $today = date('Y-m-d');
        $conn->query("UPDATE customer_debts SET status = 'paid' WHERE balance <= 0");
        $conn->query("UPDATE customer_debts SET status = 'overdue' WHERE balance > 0 AND due_date IS NOT NULL AND due_date < '{$today}'");
        $conn->query("UPDATE customer_debts SET status = 'due_today' WHERE balance > 0 AND due_date = '{$today}'");
        $conn->query("UPDATE customer_debts SET status = 'current' WHERE balance > 0 AND (due_date IS NULL OR due_date > '{$today}')");
    }

    public static function record_debt_sale($conn, $order_id, $client_id, $amount, $due_date = null){
        self::ensure_schema($conn);
        $order_id = (int)$order_id;
        $client_id = (int)$client_id;
        $amount = (float)$amount;
        if($order_id <= 0 || $client_id <= 0 || $amount <= 0){
            return array('status' => 'failed', 'msg' => 'Invalid debt sale.');
        }
        if(self::is_walkin_client($conn, $client_id)){
            return array('status' => 'failed', 'msg' => 'Walk-in customers cannot buy on credit.');
        }
        $due_sql = 'NULL';
        if($due_date !== null && $due_date !== ''){
            $due_sql = "'".$conn->real_escape_string($due_date)."'";
        }
        $sql = "INSERT INTO customer_debts SET
            client_id = '{$client_id}',
            order_id = '{$order_id}',
            amount = '{$amount}',
            balance = '{$amount}',
            status = 'current',
            due_date = {$due_sql},
            date_created = '".date('Y-m-d H:i:s')."'";
        if(!$conn->query($sql)){
            return array('status' => 'failed', 'msg' => 'Failed to record debt.');
        }
        self::refresh_debt_statuses($conn);
        return array('status' => 'success', 'debt_id' => (int)$conn->insert_id);
    }

    public static function apply_payment($conn, $client_id, $amount, $payment_method, $reference = '', $notes = '', $user = array()){
        self::ensure_schema($conn);
        $client_id = (int)$client_id;
        $amount = round((float)$amount, 2);
        if($client_id <= 0 || $amount <= 0){
            return array('status' => 'failed', 'msg' => 'Invalid payment.');
        }
        $allowed = array('Cash', 'M-Pesa');
        if(!in_array($payment_method, $allowed, true)){
            return array('status' => 'failed', 'msg' => 'Invalid payment method.');
        }
        $outstanding = self::client_outstanding($conn, $client_id);
        if($outstanding <= 0){
            return array('status' => 'failed', 'msg' => 'Customer has no outstanding debt.');
        }
        if($amount > $outstanding + 0.01){
            return array('status' => 'failed', 'msg' => 'Payment exceeds outstanding balance.');
        }
        $uid = isset($user['id']) ? (int)$user['id'] : 0;
        $uname = isset($user['name']) ? $conn->real_escape_string($user['name']) : '';
        $ref = $conn->real_escape_string($reference);
        $note = $conn->real_escape_string($notes);
        $method = $conn->real_escape_string($payment_method);
        $conn->begin_transaction();
        $ins = "INSERT INTO debt_payments SET
            client_id = '{$client_id}',
            amount = '{$amount}',
            payment_method = '{$method}',
            reference = '{$ref}',
            notes = '{$note}',
            created_by = ".($uid > 0 ? "'{$uid}'" : 'NULL').",
            created_by_name = '{$uname}',
            date_created = '".date('Y-m-d H:i:s')."'";
        if(!$conn->query($ins)){
            $conn->rollback();
            return array('status' => 'failed', 'msg' => 'Failed to save payment.');
        }
        $payment_id = (int)$conn->insert_id;
        $remaining = $amount;
        $q = $conn->query("SELECT id, balance FROM customer_debts
            WHERE client_id = '{$client_id}' AND balance > 0
            ORDER BY date_created ASC, id ASC");
        while($remaining > 0 && $q && ($row = $q->fetch_assoc())){
            $debt_id = (int)$row['id'];
            $bal = (float)$row['balance'];
            $apply = min($remaining, $bal);
            $new_bal = max(0, $bal - $apply);
            $status = $new_bal <= 0 ? 'paid' : 'current';
            $conn->query("UPDATE customer_debts SET balance = '{$new_bal}', status = '{$status}' WHERE id = '{$debt_id}'");
            $remaining -= $apply;
        }
        $conn->commit();
        self::refresh_debt_statuses($conn);
        return array(
            'status' => 'success',
            'payment_id' => $payment_id,
            'previous_balance' => $outstanding,
            'amount_paid' => $amount,
            'remaining_balance' => max(0, $outstanding - $amount),
        );
    }

    public static function delete_payment($conn, $payment_id){
        self::ensure_schema($conn);
        $payment_id = (int)$payment_id;
        $q = $conn->query("SELECT * FROM debt_payments WHERE id = '{$payment_id}' LIMIT 1");
        if(!$q || !$q->num_rows){
            return array('status' => 'failed', 'msg' => 'Payment not found.');
        }
        $payment = $q->fetch_assoc();
        $client_id = (int)$payment['client_id'];
        $amount = (float)$payment['amount'];
        $conn->begin_transaction();
        $conn->query("DELETE FROM debt_payments WHERE id = '{$payment_id}'");
        $remaining = $amount;
        $q2 = $conn->query("SELECT id, amount, balance FROM customer_debts
            WHERE client_id = '{$client_id}'
            ORDER BY date_created DESC, id DESC");
        while($remaining > 0 && $q2 && ($row = $q2->fetch_assoc())){
            $debt_id = (int)$row['id'];
            $max_restore = (float)$row['amount'] - (float)$row['balance'];
            if($max_restore <= 0) continue;
            $restore = min($remaining, $max_restore);
            $new_bal = (float)$row['balance'] + $restore;
            $conn->query("UPDATE customer_debts SET balance = '{$new_bal}', status = 'current' WHERE id = '{$debt_id}'");
            $remaining -= $restore;
        }
        if($remaining > 0){
            $conn->query("INSERT INTO customer_debts SET client_id = '{$client_id}', order_id = 0, amount = '{$remaining}', balance = '{$remaining}', status = 'current', date_created = '".date('Y-m-d H:i:s')."'");
        }
        $conn->commit();
        self::refresh_debt_statuses($conn);
        return array('status' => 'success');
    }

    public static function void_debt_for_order($conn, $order_id){
        if(!self::tables_ready($conn)) return;
        $order_id = (int)$order_id;
        $conn->query("DELETE FROM customer_debts WHERE order_id = '{$order_id}'");
    }

    public static function sync_order_debt($conn, $order_id, $client_id, $amount, $payment_method){
        self::ensure_schema($conn);
        $order_id = (int)$order_id;
        $client_id = (int)$client_id;
        $amount = round((float)$amount, 2);
        if(strcasecmp($payment_method, 'Debt') !== 0){
            self::void_debt_for_order($conn, $order_id);
            return array('status' => 'success');
        }
        if(self::is_walkin_client($conn, $client_id)){
            return array('status' => 'failed', 'msg' => 'Debt sales require a registered customer.');
        }
        $q = $conn->query("SELECT * FROM customer_debts WHERE order_id = '{$order_id}' LIMIT 1");
        if($q && $q->num_rows){
            $row = $q->fetch_assoc();
            $diff = $amount - (float)$row['amount'];
            $new_balance = max(0, (float)$row['balance'] + $diff);
            $conn->query("UPDATE customer_debts SET
                client_id = '{$client_id}',
                amount = '{$amount}',
                balance = '{$new_balance}'
                WHERE order_id = '{$order_id}'");
        } else {
            $result = self::record_debt_sale($conn, $order_id, $client_id, $amount, null);
            if($result['status'] !== 'success'){
                return $result;
            }
        }
        self::refresh_debt_statuses($conn);
        return array('status' => 'success');
    }

    public static function client_outstanding($conn, $client_id){
        if(!self::tables_ready($conn)) return 0;
        $client_id = (int)$client_id;
        $q = $conn->query("SELECT COALESCE(SUM(balance), 0) AS total FROM customer_debts WHERE client_id = '{$client_id}' AND balance > 0");
        return $q ? (float)$q->fetch_assoc()['total'] : 0;
    }

    public static function client_summary($conn, $client_id){
        self::refresh_debt_statuses($conn);
        $client_id = (int)$client_id;
        $outstanding = self::client_outstanding($conn, $client_id);
        $credit = 0;
        $paid = 0;
        $last_payment = null;
        if(self::tables_ready($conn)){
            $q1 = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM customer_debts WHERE client_id = '{$client_id}'");
            if($q1) $credit = (float)$q1->fetch_assoc()['total'];
            $q2 = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total, MAX(date_created) AS last_dt FROM debt_payments WHERE client_id = '{$client_id}'");
            if($q2){
                $r = $q2->fetch_assoc();
                $paid = (float)$r['total'];
                $last_payment = $r['last_dt'];
            }
        }
        $status = 'Clear';
        if($outstanding > 0){
            $status = 'Owing';
            $q3 = $conn->query("SELECT COUNT(*) AS c FROM customer_debts WHERE client_id = '{$client_id}' AND balance > 0 AND status = 'overdue'");
            if($q3 && (int)$q3->fetch_assoc()['c'] > 0) $status = 'Overdue';
        }
        return array(
            'outstanding' => $outstanding,
            'credit_given' => $credit,
            'total_paid' => $paid,
            'remaining' => $outstanding,
            'last_payment' => $last_payment,
            'credit_status' => $status,
        );
    }

    public static function client_statement($conn, $client_id){
        self::refresh_debt_statuses($conn);
        $client_id = (int)$client_id;
        $events = array();
        $q1 = $conn->query("SELECT cd.id, cd.order_id, cd.amount, cd.due_date, cd.date_created, o.ref_code
            FROM customer_debts cd
            LEFT JOIN orders o ON o.id = cd.order_id
            WHERE cd.client_id = '{$client_id}'
            ORDER BY cd.date_created ASC, cd.id ASC");
        while($q1 && ($r = $q1->fetch_assoc())){
            $desc = 'Sale';
            if(!empty($r['ref_code'])) $desc .= ' #'.$r['ref_code'];
            elseif((int)$r['order_id'] > 0) $desc .= ' #'.$r['order_id'];
            $events[] = array(
                'date' => $r['date_created'],
                'description' => $desc,
                'debit' => (float)$r['amount'],
                'credit' => 0,
                'sort' => strtotime($r['date_created']).'.1'.$r['id'],
            );
        }
        $q2 = $conn->query("SELECT id, amount, payment_method, reference, date_created FROM debt_payments
            WHERE client_id = '{$client_id}' ORDER BY date_created ASC, id ASC");
        while($q2 && ($r = $q2->fetch_assoc())){
            $desc = 'Payment ('.$r['payment_method'].')';
            if(!empty($r['reference'])) $desc .= ' '.$r['reference'];
            $events[] = array(
                'date' => $r['date_created'],
                'description' => $desc,
                'debit' => 0,
                'credit' => (float)$r['amount'],
                'sort' => strtotime($r['date_created']).'.2'.$r['id'],
            );
        }
        usort($events, function($a, $b){ return strcmp($a['sort'], $b['sort']); });
        $balance = 0;
        foreach($events as &$ev){
            $balance += $ev['debit'] - $ev['credit'];
            $ev['balance'] = $balance;
        }
        unset($ev);
        return $events;
    }

    public static function dashboard_stats($conn){
        self::refresh_debt_statuses($conn);
        if(!self::tables_ready($conn)){
            return array(
                'outstanding' => 0, 'credit_today' => 0, 'payments_today' => 0,
                'customers_owing' => 0, 'largest_debtor' => array('name' => '&mdash;', 'amount' => 0),
            );
        }
        $today = date('Y-m-d');
        $outstanding = (float)$conn->query("SELECT COALESCE(SUM(balance), 0) AS t FROM customer_debts WHERE balance > 0")->fetch_assoc()['t'];
        $credit_today = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS t FROM customer_debts WHERE DATE(date_created) = '{$today}'")->fetch_assoc()['t'];
        $payments_today = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS t FROM debt_payments WHERE DATE(date_created) = '{$today}'")->fetch_assoc()['t'];
        $customers_owing = (int)$conn->query("SELECT COUNT(DISTINCT client_id) AS t FROM customer_debts WHERE balance > 0")->fetch_assoc()['t'];
        $largest = array('name' => '&mdash;', 'amount' => 0, 'client_id' => 0);
        $q = $conn->query("SELECT cd.client_id, SUM(cd.balance) AS bal, CONCAT(c.firstname,' ',c.lastname) AS fullname
            FROM customer_debts cd
            INNER JOIN clients c ON c.id = cd.client_id
            WHERE cd.balance > 0
            GROUP BY cd.client_id
            ORDER BY bal DESC LIMIT 1");
        if($q && $q->num_rows){
            $r = $q->fetch_assoc();
            $largest = array('name' => $r['fullname'], 'amount' => (float)$r['bal'], 'client_id' => (int)$r['client_id']);
        }
        return array(
            'outstanding' => $outstanding,
            'credit_today' => $credit_today,
            'payments_today' => $payments_today,
            'customers_owing' => $customers_owing,
            'largest_debtor' => $largest,
        );
    }

    public static function customers_with_debt($conn){
        self::refresh_debt_statuses($conn);
        if(!self::tables_ready($conn)) return array();
        $email = $conn->real_escape_string(self::WALKIN_EMAIL);
        $sql = "SELECT c.id, c.firstname, c.lastname, c.contact,
            CONCAT(c.firstname,' ',c.lastname) AS fullname,
            COALESCE(SUM(cd.balance), 0) AS outstanding,
            MAX(cd.date_created) AS last_purchase,
            (SELECT MAX(dp.date_created) FROM debt_payments dp WHERE dp.client_id = c.id) AS last_payment,
            MAX(CASE WHEN cd.status = 'overdue' AND cd.balance > 0 THEN 1 ELSE 0 END) AS has_overdue,
            MAX(CASE WHEN cd.status = 'due_today' AND cd.balance > 0 THEN 1 ELSE 0 END) AS due_today
            FROM clients c
            INNER JOIN customer_debts cd ON cd.client_id = c.id
            WHERE c.delete_flag = 0 AND c.email != '{$email}'
            GROUP BY c.id
            HAVING outstanding > 0
            ORDER BY outstanding DESC, fullname ASC";
        $rows = array();
        $q = $conn->query($sql);
        while($q && ($r = $q->fetch_assoc())){
            $status = 'Owing';
            if((int)$r['has_overdue'] === 1) $status = 'Overdue';
            elseif((int)$r['due_today'] === 1) $status = 'Due Today';
            $r['status_label'] = $status;
            $rows[] = $r;
        }
        return $rows;
    }

    public static function notify_large_debt($amount, $customer_name, $ref){
        if($amount >= self::LARGE_DEBT_THRESHOLD){
            admin_notify('warning', 'Large Credit Sale', format_price($amount).' credit sale for '.$customer_name.' ('.$ref.')', base_url.'admin/?page=debt/customers', 'debt_sale_'.$ref);
        }
    }

    public static function notify_overdue_client($conn, $client_id){
        $client_id = (int)$client_id;
        $q = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullname FROM clients WHERE id = '{$client_id}' LIMIT 1");
        if(!$q || !$q->num_rows) return;
        $name = $q->fetch_assoc()['fullname'];
        $bal = self::client_outstanding($conn, $client_id);
        admin_notify('danger', 'Customer Overdue', $name.' owes '.format_price($bal), base_url.'admin/?page=debt/overdue', 'debt_overdue_'.$client_id);
    }

    public static function notify_large_payment($amount, $customer_name){
        if($amount >= self::LARGE_DEBT_THRESHOLD){
            admin_notify('success', 'Large Debt Payment', format_price($amount).' received from '.$customer_name, base_url.'admin/?page=debt/history', 'debt_pay_'.time());
        }
    }
}
