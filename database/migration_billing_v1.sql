-- Kalmoy POS billing v1. Run once against the existing SaaS database.
-- Existing nullable unique provider columns intentionally permit multiple NULL values.
ALTER TABLE subscription_plans
    MODIFY price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
    MODIFY price_yearly DECIMAL(12,2) NOT NULL DEFAULT 0;

ALTER TABLE subscriptions
    ADD COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER plan_id;

ALTER TABLE subscription_payments
    MODIFY subscription_id INT DEFAULT NULL,
    MODIFY status ENUM('pending','paid','failed','cancelled','timeout','refunded') NOT NULL DEFAULT 'pending',
    ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'KES' AFTER amount,
    ADD COLUMN billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly' AFTER currency,
    ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL AFTER currency,
    ADD COLUMN merchant_request_id VARCHAR(120) DEFAULT NULL AFTER phone_number,
    ADD COLUMN checkout_request_id VARCHAR(120) DEFAULT NULL AFTER merchant_request_id,
    ADD COLUMN mpesa_receipt VARCHAR(120) DEFAULT NULL AFTER checkout_request_id,
    ADD COLUMN provider_result_code VARCHAR(30) DEFAULT NULL AFTER mpesa_receipt,
    ADD COLUMN provider_result_description VARCHAR(255) DEFAULT NULL AFTER provider_result_code,
    ADD COLUMN transaction_date DATETIME DEFAULT NULL AFTER provider_result_description,
    ADD COLUMN idempotency_key VARCHAR(80) DEFAULT NULL AFTER transaction_date,
    ADD COLUMN subscription_action_applied_at DATETIME DEFAULT NULL AFTER idempotency_key,
    ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD UNIQUE KEY uk_subpay_checkout_request (checkout_request_id),
    ADD UNIQUE KEY uk_subpay_mpesa_receipt (mpesa_receipt),
    ADD UNIQUE KEY uk_subpay_idempotency (idempotency_key),
    ADD KEY idx_subpay_status (status),
    ADD KEY idx_subpay_phone (phone_number);

CREATE TABLE IF NOT EXISTS payment_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL,
  event_type VARCHAR(40) NOT NULL,
  provider_event_key VARCHAR(160) DEFAULT NULL,
  payload_json LONGTEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_payment_event_provider (provider_event_key),
  KEY idx_payment_events_payment (payment_id),
  CONSTRAINT fk_payment_events_payment FOREIGN KEY (payment_id) REFERENCES subscription_payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
