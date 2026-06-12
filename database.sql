-- ============================================================
--  Portfolio Database — Nnassia Friday Okon
--  Run this once to set up all tables.
--  Default admin login: admin / Admin@1234
-- ============================================================
CREATE DATABASE IF NOT EXISTS nfo_portfolio;
USE nfo_portfolio;

-- Contact messages
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read','replied','archived') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Booking / job-opportunity requests
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    company VARCHAR(150),
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    project_type ENUM('Web Development','Mobile App','Full Stack','Consulting','Other') NOT NULL,
    budget VARCHAR(50),
    timeline VARCHAR(100),
    description TEXT NOT NULL,
    preferred_date DATE,
    status ENUM('pending','confirmed','declined','completed') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin accounts
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Site settings (editable from admin)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default admin — password: Admin@1234  (change immediately after first login)
INSERT INTO admin_users (username, password_hash, email)
VALUES ('admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','nnassia@dev.io')
ON DUPLICATE KEY UPDATE username = username;

-- Default site settings
INSERT INTO settings (setting_key, setting_value) VALUES
('hero_name',          'Nnassia Friday Okon'),
('hero_tagline',       'Full Stack Developer & Digital Craftsman'),
('about_text',         'Passionate full-stack developer building fast, scalable, and beautiful digital products from end to end.'),
('years_experience',   '5+'),
('projects_completed', '50+'),
('clients_worldwide',  '30+'),
('availability_status','open'),
('cv_url',             '#')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
