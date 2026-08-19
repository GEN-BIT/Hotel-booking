-- ============================================
-- Hotel Booking System — Sample Seed Data
-- ============================================
USE hotel_booking;

-- Amenities
INSERT INTO amenities (name, icon) VALUES
('Wi-Fi', 'wifi'),
('Breakfast Included', 'coffee'),
('Air Conditioning', 'ac'),
('Free Parking', 'parking'),
('Flat-screen TV', 'tv'),
('Mini Bar', 'bar'),
('Ocean View', 'view'),
('Room Service', 'bell');

-- Room Types
INSERT INTO room_types (name, description, base_price, max_occupancy) VALUES
('Standard Room', 'A comfortable room with all the essentials for a pleasant stay.', 60.00, 2),
('Deluxe Room', 'Spacious room with upgraded furnishings and a work desk.', 95.00, 2),
('Executive Suite', 'A separate living area plus bedroom, ideal for extended stays.', 150.00, 3),
('Family Room', 'Extra space and bedding to comfortably fit a family.', 130.00, 4),
('Presidential Suite', 'Our top-tier suite with premium amenities and panoramic views.', 300.00, 4);

-- Link amenities to room types
-- Standard Room (id 1): Wi-Fi, AC
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(1, 1), (1, 3);

-- Deluxe Room (id 2): Wi-Fi, Breakfast, AC, TV
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(2, 1), (2, 2), (2, 3), (2, 5);

-- Executive Suite (id 3): Wi-Fi, Breakfast, AC, TV, Mini Bar, Room Service
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(3, 1), (3, 2), (3, 3), (3, 5), (3, 6), (3, 8);

-- Family Room (id 4): Wi-Fi, Breakfast, AC, Parking, TV
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5);

-- Presidential Suite (id 5): all amenities
INSERT INTO room_amenities (room_type_id, amenity_id) VALUES
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6), (5, 7), (5, 8);

-- Physical Rooms
-- Standard Room (id 1) — floor 1
INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(1, '101', '1', 'available'),
(1, '102', '1', 'available'),
(1, '103', '1', 'available'),
(1, '104', '1', 'available');

-- Deluxe Room (id 2) — floor 2
INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(2, '201', '2', 'available'),
(2, '202', '2', 'available'),
(2, '203', '2', 'available');

-- Executive Suite (id 3) — floor 3
INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(3, '301', '3', 'available'),
(3, '302', '3', 'available');

-- Family Room (id 4) — floor 2
INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(4, '210', '2', 'available'),
(4, '211', '2', 'available');

-- Presidential Suite (id 5) — floor 4 (penthouse)
INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES
(5, '401', '4', 'available');
