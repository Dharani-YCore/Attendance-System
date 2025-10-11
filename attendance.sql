-- ==========================
-- Smart Attendance System DB (Updated with secure password storage)
-- ==========================

-- USERS TABLE (with secure password storage)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- store hashed password (bcrypt/argon2)
    is_first_login BOOLEAN DEFAULT TRUE, -- track if user needs to change default password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ATTENDANCE TABLE
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'On Leave') DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- REPORTS TABLE
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_date DATE NOT NULL,
    morning_time TIME,
    evening_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PASSWORD RESETS TABLE (for OTP functionality)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_email (email)
);

-- INDEXING for faster queries
CREATE INDEX idx_user_attendance ON attendance(user_id, date);
CREATE INDEX idx_user_reports ON reports(user_id, report_date);
CREATE INDEX idx_password_resets_email ON password_resets(email, expires_at);

-- ==========================
-- ALTER TABLE: Add is_first_login column to users table
-- ==========================
-- This column tracks whether a user needs to change their default password
-- Default value is TRUE (1) for new users or users with default passwords
ALTER TABLE users 
ADD COLUMN is_first_login BOOLEAN DEFAULT TRUE AFTER password;
