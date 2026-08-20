-- ============================================
-- Guest Document Management
-- ============================================

USE hotel_booking;

CREATE TABLE IF NOT EXISTS guest_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);
