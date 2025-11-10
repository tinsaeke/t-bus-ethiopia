USE t_bus_ethiopia;

-- Insert cities
INSERT INTO cities (name) VALUES 
('Addis Ababa'), ('Adama'), ('Hawassa'), ('Bahir Dar'), ('Gondar'),
('Mekelle'), ('Dire Dawa'), ('Jimma'), ('Arba Minch'), ('Adigrat');

-- Insert bus companies
INSERT INTO bus_companies (company_name, contact_person_name, contact_phone, description) VALUES 
('Selam Bus', 'Alemayehu Kebede', '+251911223344', 'Comfortable and reliable bus service'),
('Sky Bus', 'Meron Tekle', '+251922334455', 'Premium bus service with VIP options'),
('Abay Bus', 'Tesfaye Hailu', '+251933445566', 'Affordable travel across Ethiopia'),
('Ethio Bus', 'Hana Mohammed', '+251944556677', 'Your trusted travel partner');

-- Create users (password: admin123 for all)
INSERT INTO users (email, password_hash, user_type, full_name) VALUES 
('superadmin@tbus.et', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'T BUS Super Admin');

INSERT INTO users (email, password_hash, user_type, bus_company_id, full_name) VALUES 
('selam@tbus.et', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner_admin', 1, 'Selam Bus Manager'),
('sky@tbus.et', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner_admin', 2, 'Sky Bus Manager');

-- Insert buses
INSERT INTO buses (bus_company_id, bus_number, type, total_seats, amenities) VALUES 
(1, 'SEL-001', 'vip', 45, '["wifi", "ac", "charging_port", "toilet", "tv"]'),
(1, 'SEL-002', 'standard', 60, '["ac", "charging_port"]'),
(2, 'SKY-001', 'business', 35, '["wifi", "ac", "charging_port", "toilet", "tv", "snacks"]');

-- Insert schedules
INSERT INTO schedules (bus_id, from_city_id, to_city_id, departure_time, arrival_time, price, available_seats, travel_date) VALUES 
(1, 1, 3, '08:00:00', '12:00:00', 450.00, 45, CURDATE() + INTERVAL 1 DAY),
(2, 1, 2, '09:30:00', '11:00:00', 150.00, 60, CURDATE() + INTERVAL 1 DAY),
(3, 1, 4, '07:00:00', '12:30:00', 550.00, 35, CURDATE() + INTERVAL 1 DAY);

-- Site content
INSERT INTO site_content (page, section, content_title, content_text, display_order) VALUES 
('homepage', 'banner', 'Book Bus Tickets Easily', 'Travel across Ethiopia with comfort and reliability. Book your bus tickets online in just few clicks.', 1),
('homepage', 'how_it_works', 'How T BUS Works', 'Search, Book, and Travel - Simple and convenient bus booking platform.', 2);

-- Popular routes
INSERT INTO popular_routes (from_city_id, to_city_id, display_order) VALUES 
(1, 3, 1), (1, 4, 2), (1, 2, 3), (1, 5, 4);