-- ============================================
-- Email Notification Log
-- ============================================

USE hotel_booking;

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(150) NOT NULL,
    recipient_name VARCHAR(150) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    gateway VARCHAR(50) DEFAULT 'smtp',
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (recipient_email),
    INDEX (created_at)
);
