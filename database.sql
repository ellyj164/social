-- Create the database
CREATE DATABASE IF NOT EXISTS kakaweti_spin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kakaweti_spin;

-- Users table (people who play)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    device_fingerprint VARCHAR(255),
    has_selected TINYINT(1) DEFAULT 0,
    has_been_selected TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Participants table (names on the wheel)
CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    is_selected TINYINT(1) DEFAULT 0,
    selected_at TIMESTAMP NULL,
    selected_by_user_id INT NULL,
    has_made_selection TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (selected_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Game Pairings table (final results - who selected whom)
-- Each participant appears exactly once as selector and once as selected
CREATE TABLE IF NOT EXISTS game_pairings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selector_name VARCHAR(100) NOT NULL,
    selected_name VARCHAR(100) NOT NULL,
    selector_user_id INT,
    device_fingerprint VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_selector (selector_name),  -- Each person can only select once
    UNIQUE KEY unique_selected (selected_name),  -- Each person can only be selected once
    UNIQUE KEY unique_device (device_fingerprint),  -- Each device can only select once
    FOREIGN KEY (selector_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Selections table (comprehensive tracking as per requirements)
CREATE TABLE IF NOT EXISTS selections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    selector_first_name VARCHAR(100) NOT NULL,
    selector_user_id INT,
    selected_name VARCHAR(100) NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    screen_resolution VARCHAR(20),
    timezone VARCHAR(100),
    language VARCHAR(50),
    canvas_fingerprint VARCHAR(64),
    webgl_fingerprint VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_selector (selector_first_name),
    UNIQUE KEY unique_selected (selected_name),
    UNIQUE KEY unique_device (device_fingerprint),
    FOREIGN KEY (selector_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Login sessions for tracking
CREATE TABLE IF NOT EXISTS login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Spin results history
CREATE TABLE IF NOT EXISTS spin_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selected_name VARCHAR(100) NOT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    email_sent_at TIMESTAMP NULL,
    spin_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default participants
INSERT INTO participants (name) VALUES 
('Peter'), ('Joseph'), ('Peace'), ('Cartine'), ('Chantal'), ('Annet'), ('Lydia'),
('Steve'), ('Elyse'), ('Safari'), ('Sam'), ('Abuba'), ('Philippe'), ('Veronique'),
('Gorette'), ('Anthony'), ('Arlette'), ('Jambo');

-- Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_first_name ON users(first_name);
CREATE INDEX idx_users_device ON users(device_fingerprint);
CREATE INDEX idx_participants_name ON participants(name);
CREATE INDEX idx_game_pairings_selector ON game_pairings(selector_name);
CREATE INDEX idx_game_pairings_selected ON game_pairings(selected_name);
CREATE INDEX idx_login_sessions_fingerprint ON login_sessions(device_fingerprint);
CREATE INDEX idx_selections_selector ON selections(selector_first_name);
CREATE INDEX idx_selections_device ON selections(device_fingerprint);

-- Device locks table (for blocking devices from multiple selections)
CREATE TABLE IF NOT EXISTS device_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_fingerprint VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    selected_name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    screen_resolution VARCHAR(20),
    timezone VARCHAR(100),
    language VARCHAR(50),
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);