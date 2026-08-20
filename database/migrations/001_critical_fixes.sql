-- ============================================
-- Critical missing tables and columns
-- ============================================

USE hotel_booking;

-- ---------- Settings ----------

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---------- Service Orders ----------

CREATE TABLE IF NOT EXISTS service_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    status ENUM('requested','fulfilled','cancelled') NOT NULL DEFAULT 'requested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- ---------- Room Type Photos caption column ----------

ALTER TABLE room_type_photos ADD COLUMN IF NOT EXISTS caption VARCHAR(255) DEFAULT NULL AFTER file_path;

-- ---------- Seed default settings ----------

INSERT INTO settings (setting_key, setting_value) VALUES
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('from_email', 'no-reply@hotel-booking.local'),
('from_name', 'Hotel Booking System'),
('hotel_name', 'VAREK HOTEL'),
('hotel_address', ''),
('hotel_phone', ''),
('hotel_email', 'info@hotel-booking.local'),
('check_in_time', '14:00'),
('check_out_time', '11:00'),
('enabled_payment_methods', 'card,cash,bank_transfer,mobile_money'),
('deposit_percentage', '0'),
('min_stay_nights', '1'),
('max_stay_nights', '30'),
('cancellation_window_hours', '24'),
('advance_booking_days', '365')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
