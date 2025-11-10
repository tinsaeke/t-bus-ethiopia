-- T BUS Database Schema for PHP/MySQL
CREATE DATABASE IF NOT EXISTS t_bus_ethiopia;
USE t_bus_ethiopia;

-- Cities table
CREATE TABLE cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_city_name (name)
);

-- Bus companies table
CREATE TABLE bus_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(200) NOT NULL,
    contact_person_name VARCHAR(100),
    contact_phone VARCHAR(20),
    logo_url VARCHAR(500),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('super_admin', 'partner_admin') NOT NULL,
    bus_company_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_company_id) REFERENCES bus_companies(id) ON DELETE SET NULL
);

-- Buses table
CREATE TABLE buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_company_id INT NOT NULL,
    bus_number VARCHAR(50) NOT NULL,
    type ENUM('standard', 'vip', 'business') DEFAULT 'standard',
    total_seats INT NOT NULL,
    amenities JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_company_id) REFERENCES bus_companies(id) ON DELETE CASCADE
);

-- Schedules table
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_id INT NOT NULL,
    from_city_id INT NOT NULL,
    to_city_id INT NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT NOT NULL,
    travel_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (from_city_id) REFERENCES cities(id),
    FOREIGN KEY (to_city_id) REFERENCES cities(id),
    INDEX idx_dates (travel_date, from_city_id, to_city_id)
);

-- Bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    schedule_id INT NOT NULL,
    passenger_full_name VARCHAR(100) NOT NULL,
    passenger_phone VARCHAR(20) NOT NULL,
    seat_number INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    booking_status ENUM('confirmed', 'cancelled', 'pending') DEFAULT 'confirmed',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('telebirr', 'cbe_birr', 'cash') NULL,
    payment_reference VARCHAR(100),
    qr_code_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    INDEX idx_booking_ref (booking_reference),
    INDEX idx_passenger_phone (passenger_phone)
);

-- Site content table
CREATE TABLE site_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page VARCHAR(50) NOT NULL,
    section VARCHAR(50) NOT NULL,
    content_title VARCHAR(200),
    content_text TEXT,
    content_html TEXT,
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_page_section (page, section)
);

-- Popular routes table
CREATE TABLE popular_routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_city_id INT NOT NULL,
    to_city_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_city_id) REFERENCES cities(id),
    FOREIGN KEY (to_city_id) REFERENCES cities(id)
);