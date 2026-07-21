-- =====================================================================
-- EventPro - Event Management System
-- Database Schema
-- =====================================================================

CREATE DATABASE IF NOT EXISTS eventpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventpro;

-- ---------------------------------------------------------------------
-- Table: admins
-- ---------------------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    icon VARCHAR(60) DEFAULT 'fa-star',
    color VARCHAR(20) DEFAULT '#2563EB',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: events
-- ---------------------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    short_description VARCHAR(300) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    gallery TEXT DEFAULT NULL COMMENT 'JSON array of image paths',
    venue VARCHAR(255) NOT NULL,
    map_embed_url TEXT DEFAULT NULL,
    organizer VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    total_seats INT NOT NULL DEFAULT 0,
    available_seats INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rules TEXT DEFAULT NULL,
    schedule TEXT DEFAULT NULL COMMENT 'JSON array of {time,title}',
    status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_events_date (event_date),
    INDEX idx_events_status (status),
    INDEX idx_events_category (category_id),
    INDEX idx_events_price (price),
    FULLTEXT INDEX ft_events_search (title, venue, organizer)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: bookings
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    seats_booked INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    attendee_name VARCHAR(100) NOT NULL,
    attendee_email VARCHAR(150) NOT NULL,
    attendee_phone VARCHAR(20) NOT NULL,
    status ENUM('confirmed','completed','cancelled') NOT NULL DEFAULT 'confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_bookings_user (user_id),
    INDEX idx_bookings_event (event_id),
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_code (booking_code)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('user','admin') NOT NULL,
    actor_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_actor (actor_type, actor_id),
    INDEX idx_logs_created (created_at)
) ENGINE=InnoDB;

-- =====================================================================
-- SAMPLE DATA
-- =====================================================================

-- Default admin (password: Admin@123)
INSERT INTO admins (full_name, email, password, role) VALUES
('System Administrator', 'admin@eventpro.com', '$2y$10$GekqkRkKzS9ArDrKAn7ReOvrDhwsAWt5kZfVmFuMBCI4o4DLTjxBe', 'super_admin');

-- Categories
INSERT INTO categories (name, slug, icon, color) VALUES
('Music & Concerts', 'music-concerts', 'fa-music', '#2563EB'),
('Technology', 'technology', 'fa-laptop-code', '#06B6D4'),
('Business & Networking', 'business-networking', 'fa-briefcase', '#4F46E5'),
('Arts & Culture', 'arts-culture', 'fa-palette', '#F59E0B'),
('Sports & Fitness', 'sports-fitness', 'fa-dumbbell', '#22C55E'),
('Food & Drink', 'food-drink', 'fa-utensils', '#EF4444');

-- Sample users (password for all: User@123)
INSERT INTO users (full_name, email, phone, password) VALUES
('Aditi Sharma', 'aditi@example.com', '9876543210', '$2y$10$ImEsG8QpVooQwtLEzENy.u3C7DL52kXTzEj8rrJxlaBSnr.NI/3eC'),
('Rohan Mehta', 'rohan@example.com', '9876500011', '$2y$10$ImEsG8QpVooQwtLEzENy.u3C7DL52kXTzEj8rrJxlaBSnr.NI/3eC');

-- Sample events
INSERT INTO events (category_id, title, slug, description, short_description, venue, organizer, event_date, event_time, total_seats, available_seats, price, rules, status) VALUES
(1, 'Sunset Music Festival', 'sunset-music-festival', 'An unforgettable evening of live music featuring top artists across genres, food trucks, and art installations under the stars.', 'Live music, food trucks and art under the stars.', 'Marine Drive Grounds, Mumbai', 'EventPro Live', '2026-09-12', '18:00:00', 500, 480, 1499.00, 'No outside food or drinks. Entry with valid ticket only.', 'upcoming'),
(2, 'Future Tech Summit 2026', 'future-tech-summit-2026', 'A gathering of industry leaders discussing AI, cloud computing, and the future of software engineering.', 'AI, cloud and the future of engineering.', 'Bombay Exhibition Centre, Mumbai', 'TechForward Inc.', '2026-10-05', '09:30:00', 300, 300, 2999.00, 'Laptops recommended. Business casual attire.', 'upcoming'),
(3, 'Startup Networking Night', 'startup-networking-night', 'Connect with founders, investors, and industry experts over drinks and curated conversations.', 'Meet founders and investors over drinks.', 'WeWork BKC, Mumbai', 'Founders Circle', '2026-08-20', '19:00:00', 150, 140, 0.00, 'Free entry, registration required.', 'upcoming');

-- Sample bookings
INSERT INTO bookings (booking_code, user_id, event_id, seats_booked, total_amount, attendee_name, attendee_email, attendee_phone, status) VALUES
('EVT-2026-000001', 1, 1, 2, 2998.00, 'Aditi Sharma', 'aditi@example.com', '9876543210', 'confirmed'),
('EVT-2026-000002', 2, 3, 1, 0.00, 'Rohan Mehta', 'rohan@example.com', '9876500011', 'confirmed');
