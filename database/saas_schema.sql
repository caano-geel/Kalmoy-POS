-- Kalmoy POS — Multi-tenant SaaS schema (MVP).
-- Run via: php database/install_saas.php
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS subscription_payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS platform_audit_log;
DROP TABLE IF EXISTS platform_users;
DROP TABLE IF EXISTS business_settings;
DROP TABLE IF EXISTS businesses;
DROP TABLE IF EXISTS debt_payments;
DROP TABLE IF EXISTS customer_debts;
DROP TABLE IF EXISTS purchase_receipt_items;
DROP TABLE IF EXISTS purchase_receipts;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS admin_activity_log;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS order_list;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS brands;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE businesses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  logo_path VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'KES',
  status ENUM('active','trial','suspended','expired','cancelled','inactive') NOT NULL DEFAULT 'trial',
  owner_user_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_business_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE platform_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_platform_email (email),
  UNIQUE KEY uk_platform_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE platform_audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform_user_id INT DEFAULT NULL,
  username VARCHAR(100) DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  details TEXT DEFAULT NULL,
  business_id INT DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_platform_audit_business (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL,
  price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
  price_yearly DECIMAL(12,2) NOT NULL DEFAULT 0,
  trial_days INT NOT NULL DEFAULT 14,
  max_users INT DEFAULT NULL,
  max_products INT DEFAULT NULL,
  features_json TEXT DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_plan_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  plan_id INT NOT NULL,
  status ENUM('trial','active','expired','suspended','cancelled') NOT NULL DEFAULT 'trial',
  billing_cycle ENUM('monthly','yearly','trial') NOT NULL DEFAULT 'trial',
  trial_ends_at DATETIME DEFAULT NULL,
  current_period_start DATETIME DEFAULT NULL,
  current_period_end DATETIME DEFAULT NULL,
  cancelled_at DATETIME DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sub_business (business_id),
  KEY idx_sub_status (status),
  CONSTRAINT fk_sub_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscription_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  subscription_id INT NOT NULL,
  plan_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(50) NOT NULL DEFAULT 'manual',
  reference VARCHAR(120) DEFAULT NULL,
  payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'paid',
  notes TEXT DEFAULT NULL,
  created_by_platform_user_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_subpay_business (business_id),
  CONSTRAINT fk_subpay_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_subpay_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE business_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  meta_field VARCHAR(100) NOT NULL,
  meta_value TEXT NOT NULL,
  UNIQUE KEY uk_business_setting (business_id, meta_field),
  KEY idx_business_settings_bid (business_id),
  CONSTRAINT fk_settings_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(250) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  type TINYINT(1) NOT NULL DEFAULT 2 COMMENT '1=Owner,2=Cashier,3=Manager,4=Stock Manager,5=Accountant',
  status TINYINT(1) NOT NULL DEFAULT 1,
  permissions TEXT DEFAULT NULL,
  date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_business_username (business_id, username),
  KEY idx_users_business (business_id),
  CONSTRAINT fk_users_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE brands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  delete_flag TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_brands_business (business_id),
  CONSTRAINT fk_brands_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  category VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  delete_flag TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_categories_business (business_id),
  CONSTRAINT fk_categories_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  brand_id INT NOT NULL,
  category_id INT NOT NULL,
  name VARCHAR(250) NOT NULL,
  barcode VARCHAR(100) DEFAULT NULL,
  specs TEXT NOT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  delete_flag TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_business_barcode (business_id, barcode),
  KEY idx_products_business (business_id),
  CONSTRAINT fk_products_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  variant VARCHAR(200) NOT NULL DEFAULT 'Default',
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  price DOUBLE NOT NULL DEFAULT 0,
  cost_price DOUBLE DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_inventory_business (business_id),
  KEY idx_inventory_product (product_id),
  CONSTRAINT fk_inventory_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  firstname VARCHAR(200) NOT NULL,
  lastname VARCHAR(200) NOT NULL,
  gender VARCHAR(20) DEFAULT NULL,
  contact VARCHAR(50) DEFAULT NULL,
  email VARCHAR(200) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  default_delivery_address TEXT DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  delete_flag TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_clients_business (business_id),
  CONSTRAINT fk_clients_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  ref_code VARCHAR(50) NOT NULL,
  client_id INT NOT NULL,
  delivery_address TEXT DEFAULT NULL,
  payment_method VARCHAR(50) DEFAULT NULL,
  order_type TINYINT(1) NOT NULL DEFAULT 1,
  amount DOUBLE NOT NULL DEFAULT 0,
  discount_total DOUBLE NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 0,
  paid TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_business_ref (business_id, ref_code),
  KEY idx_orders_business (business_id),
  CONSTRAINT fk_orders_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_client FOREIGN KEY (client_id) REFERENCES clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_list (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  order_id INT NOT NULL,
  inventory_id INT NOT NULL,
  quantity INT NOT NULL,
  price DOUBLE NOT NULL,
  total DOUBLE NOT NULL,
  cost_price DOUBLE DEFAULT NULL,
  KEY idx_order_list_business (business_id),
  CONSTRAINT fk_order_list_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_list_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_list_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  order_id INT NOT NULL,
  total_amount DOUBLE NOT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sales_business (business_id),
  CONSTRAINT fk_sales_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_sales_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  client_id INT NOT NULL,
  inventory_id INT NOT NULL,
  price DOUBLE NOT NULL,
  quantity INT NOT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cart_business (business_id),
  CONSTRAINT fk_cart_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  expense_date DATE NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  amount DOUBLE NOT NULL,
  payment_method VARCHAR(50) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_by_name VARCHAR(150) DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  delete_flag TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_expenses_business (business_id),
  CONSTRAINT fk_expenses_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_debts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  client_id INT NOT NULL,
  order_id INT DEFAULT NULL,
  amount DOUBLE NOT NULL DEFAULT 0,
  balance DOUBLE NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'open',
  due_date DATE DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_debts_business (business_id),
  KEY idx_debts_client (client_id),
  CONSTRAINT fk_debts_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE debt_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  client_id INT NOT NULL,
  amount DOUBLE NOT NULL,
  payment_method VARCHAR(50) DEFAULT NULL,
  reference VARCHAR(120) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_by_name VARCHAR(150) DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_debtpay_business (business_id),
  CONSTRAINT fk_debtpay_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  receipt_no VARCHAR(80) NOT NULL,
  supplier VARCHAR(200) DEFAULT NULL,
  receipt_date DATE DEFAULT NULL,
  customer VARCHAR(200) DEFAULT NULL,
  total_amount DOUBLE NOT NULL DEFAULT 0,
  currency VARCHAR(10) DEFAULT 'KES',
  notes TEXT DEFAULT NULL,
  date_imported DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_business_receipt (business_id, receipt_no),
  KEY idx_purchases_business (business_id),
  CONSTRAINT fk_purchases_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_receipt_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  purchase_receipt_id INT NOT NULL,
  line_no INT NOT NULL DEFAULT 1,
  item_code VARCHAR(80) DEFAULT NULL,
  item_name VARCHAR(250) NOT NULL,
  quantity DOUBLE NOT NULL DEFAULT 0,
  unit_price DOUBLE NOT NULL DEFAULT 0,
  line_total DOUBLE NOT NULL DEFAULT 0,
  product_id INT DEFAULT NULL,
  inventory_id INT DEFAULT NULL,
  variant VARCHAR(200) DEFAULT NULL,
  KEY idx_purchase_items_business (business_id),
  CONSTRAINT fk_purchase_items_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchase_items_receipt FOREIGN KEY (purchase_receipt_id) REFERENCES purchase_receipts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'info',
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  ref_key VARCHAR(100) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_business (business_id),
  CONSTRAINT fk_notifications_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE backup_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  file_size BIGINT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_by_name VARCHAR(150) DEFAULT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'success',
  message TEXT DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_backup_business (business_id),
  CONSTRAINT fk_backup_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin_activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  username VARCHAR(100) DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  details TEXT DEFAULT NULL,
  date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_business (business_id),
  CONSTRAINT fk_activity_business FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Legacy compatibility view: system_info removed; business_settings used per tenant.
