-- ============================================
-- Payment Processing Improvements
-- ============================================

USE hotel_booking;

-- Add deposit tracking to bookings
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER discount_amount;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER deposit_amount;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','partial','paid','refunded','failed') NOT NULL DEFAULT 'pending' AFTER amount_paid;

-- Add payment verification fields
ALTER TABLE payments ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) DEFAULT 'simulated' AFTER method;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS gateway_transaction_id VARCHAR(100) DEFAULT NULL AFTER transaction_ref;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS verified_at DATETIME DEFAULT NULL AFTER paid_at;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS verification_status ENUM('pending','verified','failed') NOT NULL DEFAULT 'verified' AFTER verified_at;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS webhook_payload TEXT DEFAULT NULL AFTER verification_status;

-- Create refunds table for audit trail
CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    requested_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    transaction_ref VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Create payment gateway configurations table
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    config JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default gateway configuration
INSERT INTO payment_gateways (name, is_active, config) VALUES
('simulated', 1, JSON_OBJECT('enabled', 1, 'test_mode', 1)),
('stripe', 0, JSON_OBJECT('enabled', 0, 'test_mode', 1, 'public_key', '', 'secret_key', '')),
('paypal', 0, JSON_OBJECT('enabled', 0, 'test_mode', 1, 'client_id', '', 'client_secret', '')),
('mobile_money', 0, JSON_OBJECT('enabled', 0, 'provider', 'mtn', 'api_key', '', 'api_secret', ''))
ON DUPLICATE KEY UPDATE config = VALUES(config);
