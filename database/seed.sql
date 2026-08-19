-- ============================================
-- Hotel Booking System — Sample Seed Data
-- ============================================
USE hotel_booking;

-- Amenities
INSERT INTO amenities (name, category, icon) VALUES
('Wi-Fi', 'Room Features', 'wifi'),
('Breakfast Included', 'Dining', 'coffee'),
('Air Conditioning', 'Room Features', 'ac'),
('Free Parking', 'Services', 'parking'),
('Flat-screen TV', 'Entertainment', 'tv'),
('Mini Bar', 'Dining', 'bar'),
('Ocean View', 'Room Features', 'view'),
('Room Service', 'Services', 'bell'),
('Safe', 'Security', 'lock'),
('Bathtub', 'Bathroom', 'bath');

-- Room Types
INSERT INTO room_types (name, description, base_price, max_occupancy, bed_type, room_size, building) VALUES
('Standard Room', 'A comfortable room with all the essentials for a pleasant stay.', 60.00, 2, 'Queen', '35 m²', 'Main Building'),
('Deluxe Room', 'Spacious room with upgraded furnishings and a work desk.', 95.00, 2, 'King', '45 m²', 'Main Building'),
('Executive Suite', 'A separate living area plus bedroom, ideal for extended stays.', 150.00, 3, 'King + Sofa Bed', '65 m²', 'Tower A'),
('Family Room', 'Extra space and bedding to comfortably fit a family.', 130.00, 4, '2 Queen Beds', '55 m²', 'Main Building'),
('Presidential Suite', 'Our top-tier suite with premium amenities and panoramic views.', 300.00, 4, 'California King', '95 m²', 'Penthouse');

-- Link amenities to room types
-- Standard Room (id 1): Wi-Fi, AC
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(1, 1), (1, 3);

-- Deluxe Room (id 2): Wi-Fi, Breakfast, AC, TV, Safe
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(2, 1), (2, 2), (2, 3), (2, 5), (2, 9);

-- Executive Suite (id 3): Wi-Fi, Breakfast, AC, TV, Mini Bar, Room Service, Safe, Bathtub
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(3, 1), (3, 2), (3, 3), (3, 5), (3, 6), (3, 8), (3, 9), (3, 10);

-- Family Room (id 4): Wi-Fi, Breakfast, AC, Parking, TV, Safe
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5), (4, 9);

-- Presidential Suite (id 5): all amenities
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6), (5, 7), (5, 8), (5, 9), (5, 10);

-- Physical Rooms
-- Standard Room (id 1) — floor 1
INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES
(1, '101', '1', 'Main Building', 'available', 'Recently renovated'),
(1, '102', '1', 'Main Building', 'available', ''),
(1, '103', '1', 'Main Building', 'available', ''),
(1, '104', '1', 'Main Building', 'available', 'Near elevator');

-- Deluxe Room (id 2) — floor 2
INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES
(2, '201', '2', 'Main Building', 'available', ''),
(2, '202', '2', 'Main Building', 'available', 'Corner room with extra space'),
(2, '203', '2', 'Main Building', 'available', '');

-- Executive Suite (id 3) — floor 3
INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES
(3, '301', '3', 'Tower A', 'available', 'City view'),
(3, '302', '3', 'Tower A', 'available', 'Ocean view');

-- Family Room (id 4) — floor 2
INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES
(4, '210', '2', 'Main Building', 'available', ''),
(4, '211', '2', 'Main Building', 'available', '');

-- Presidential Suite (id 5) — floor 4 (penthouse)
INSERT INTO rooms (room_type_id, room_number, floor, building, status, notes) VALUES
(5, '401', '4', 'Penthouse', 'available', 'Top floor, panoramic view');

-- Sample coupons
INSERT INTO coupons (code, discount_type, discount_value, max_uses, valid_from, valid_until, is_active) VALUES
('WELCOME10', 'percentage', 10, 100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
('SAVE20', 'fixed', 20, 50, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1),
('SUMMER25', 'percentage', 25, 200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1);

-- Sample activity log entries
INSERT INTO activity_log (actor_user_id, actor_name, action, description) VALUES
(NULL, 'System', 'system.init', 'Hotel booking system initialized with sample data'),
(1, 'Admin User', 'admin.login', 'Admin logged in from 127.0.0.1'),
(2, 'John Doe', 'booking.created', 'Booking HB-20240101-A1B2C created for Deluxe Room 201'),
(3, 'Jane Smith', 'booking.created', 'Booking HB-20240102-D4E5F created for Standard Room 101'),
(2, 'John Doe', 'review.created', 'Review submitted for Deluxe Room');

-- Sample users with different roles
INSERT INTO users (role_id, full_name, email, password_hash, is_verified, approval_status) VALUES
((SELECT id FROM roles WHERE name = 'admin'), 'Admin User', 'admin@hotel-booking.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'approved'),
((SELECT id FROM roles WHERE name = 'staff'), 'Staff User', 'staff@hotel-booking.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'approved'),
((SELECT id FROM roles WHERE name = 'guest'), 'Guest User', 'guest@hotel-booking.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'approved');

-- Sample staff records
INSERT INTO staff (user_id, position, is_active) VALUES
(2, 'Front Desk Manager', 1),
(3, 'Housekeeping Supervisor', 1);

-- Sample services
INSERT INTO services (name, description, price, is_active) VALUES
('Airport Transfer', 'Private car from/to airport', 45.00, 1),
('Late Check-out', 'Extend checkout until 2 PM', 25.00, 1),
('Spa Package', '60-minute full body massage', 80.00, 1),
('Breakfast Buffet', 'Full breakfast buffet for two', 30.00, 1),
('Extra Bed', 'Additional rollaway bed', 20.00, 1);

-- Sample room type photos (placeholder paths — replace with real uploads)
INSERT INTO room_type_photos (room_type_id, file_path, sort_order) VALUES
(1, 'uploads/rooms/standard_1.jpg', 0),
(1, 'uploads/rooms/standard_2.jpg', 1),
(2, 'uploads/rooms/deluxe_1.jpg', 0),
(2, 'uploads/rooms/deluxe_2.jpg', 1),
(3, 'uploads/rooms/executive_1.jpg', 0),
(4, 'uploads/rooms/family_1.jpg', 0),
(5, 'uploads/rooms/presidential_1.jpg', 0);

-- Sample seasonal pricing
INSERT INTO room_type_pricing (room_type_id, price, valid_from, valid_until, note) VALUES
(1, 75.00, '2025-12-20', '2026-01-05', 'Holiday season'),
(2, 120.00, '2025-12-20', '2026-01-05', 'Holiday season'),
(3, 180.00, '2025-12-20', '2026-01-05', 'Holiday season'),
(5, 400.00, '2025-12-20', '2026-01-05', 'Holiday season'),
(2, 110.00, '2026-06-01', '2026-08-31', 'Summer peak');
