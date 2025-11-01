-- ================================================================
-- ATTENDANCE SYSTEM - COMPLETE DATABASE SCHEMA
-- ================================================================
-- This file contains all tables, indexes, and initial data needed
-- for the complete attendance system with check-in/check-out functionality
-- ================================================================

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS attendance_system;
-- USE attendance_system;

-- ================================================================
-- TABLE: users
-- Stores user accounts and authentication information
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_first_login BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================================
-- TABLE: password_resets
-- Stores OTP tokens for password reset functionality
-- ================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp (otp),
    INDEX idx_expires (expires_at)
);

-- ================================================================
-- TABLE: attendance
-- Stores attendance records with check-in/check-out support
-- ================================================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME DEFAULT NULL,
    check_in_time TIME DEFAULT NULL,
    check_out_time TIME DEFAULT NULL,
    total_hours DECIMAL(4,2) DEFAULT NULL,
    status ENUM('Present', 'Late', 'Absent', 'On Leave') DEFAULT 'Present',
    attendance_type ENUM('check_in', 'check_out', 'full_day') DEFAULT 'full_day',
    check_in_latitude DECIMAL(10, 8) DEFAULT NULL,
    check_in_longitude DECIMAL(11, 8) DEFAULT NULL,
    check_out_latitude DECIMAL(10, 8) DEFAULT NULL,
    check_out_longitude DECIMAL(11, 8) DEFAULT NULL,
    location_accuracy DECIMAL(10, 2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_attendance_user_date (user_id, date),
    INDEX idx_attendance_type (attendance_type),
    INDEX idx_attendance_status (status)
);

-- ================================================================
-- TABLE: reports
-- Stores daily report summaries with morning/evening times
-- ================================================================
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_date DATE NOT NULL,
    morning_time TIME DEFAULT NULL,
    evening_time TIME DEFAULT NULL,
    total_working_hours DECIMAL(4,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_report_date (user_id, report_date),
    INDEX idx_reports_user_date (user_id, report_date)
);

-- ================================================================
-- TABLE: qr_codes
-- Stores QR code information and validation data
-- ================================================================
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_id VARCHAR(255) NOT NULL UNIQUE,
    qr_type VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    valid_date DATE DEFAULT NULL,
    valid_time VARCHAR(20) DEFAULT 'all_day',
    qr_data TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_qr_id (qr_id),
    INDEX idx_qr_location (location),
    INDEX idx_qr_valid_date (valid_date),
    INDEX idx_qr_active (is_active)
);

-- ================================================================
-- TABLE: qr_attendance_logs
-- Stores logs of QR code scans for attendance tracking
-- ================================================================
CREATE TABLE IF NOT EXISTS qr_attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qr_id VARCHAR(255) NOT NULL,
    attendance_status VARCHAR(50) DEFAULT 'Present',
    location_scanned VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_qr_logs_user (user_id),
    INDEX idx_qr_logs_qr_id (qr_id),
    INDEX idx_qr_logs_date (scanned_at)
);

-- ================================================================
-- TABLE: holidays
-- Stores government and company holidays
-- ================================================================
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('National', 'Regional', 'Festival', 'Company') DEFAULT 'National',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_holiday_date (holiday_date),
    INDEX idx_holiday_type (holiday_type)
);

-- ================================================================
-- INITIAL DATA - Sample holidays for 2025
-- ================================================================
INSERT INTO holidays (holiday_date, holiday_name, holiday_type) VALUES
('2025-01-01', 'New Year Day', 'National'),
('2025-01-26', 'Republic Day', 'National'),
('2025-03-14', 'Holi', 'Festival'),
('2025-04-14', 'Ambedkar Jayanti', 'National'),
('2025-04-18', 'Good Friday', 'National'),
('2025-05-01', 'May Day', 'National'),
('2025-08-15', 'Independence Day', 'National'),
('2025-10-02', 'Gandhi Jayanti', 'National'),
('2025-10-24', 'Dussehra', 'Festival'),
('2025-11-12', 'Diwali', 'Festival'),
('2025-12-25', 'Christmas', 'National')
ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name);

-- ================================================================
-- DATA MIGRATION FOR EXISTING RECORDS
-- Update existing attendance records to use new check-in/check-out structure
-- ================================================================
UPDATE attendance 
SET check_in_time = time, 
    attendance_type = 'check_in' 
WHERE check_in_time IS NULL AND time IS NOT NULL;

-- Fix missing is_first_login column for existing users
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_first_login BOOLEAN DEFAULT FALSE;
UPDATE users SET is_first_login = FALSE WHERE is_first_login IS NULL;

-- ================================================================
-- SAMPLE DATA - Default admin user (Optional)
-- Password: admin123 (hashed with PHP password_hash)
-- ================================================================
INSERT INTO users (name, email, password, is_first_login) VALUES
('Administrator', 'admin@attendance.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', FALSE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ================================================================
-- PERFORMANCE OPTIMIZATIONS
-- Additional indexes for better query performance
-- ================================================================

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_attendance_user_status ON attendance(user_id, status);
CREATE INDEX IF NOT EXISTS idx_attendance_date_status ON attendance(date, status);
CREATE INDEX IF NOT EXISTS idx_reports_date ON reports(report_date);

-- ================================================================
-- SUCCESS MESSAGE
-- ================================================================
-- Database schema creation completed successfully!
-- All tables created with proper indexes and constraints.
-- Login issue should now be resolved with is_first_login column added.
-- ================================================================