-- ===============================================
-- ADD NEW USERS TO ATTENDANCE SYSTEM
-- ===============================================
-- Use this script in phpMyAdmin to add new users
-- Password is set to NULL so users must set password on first login

-- Example: Add individual users
INSERT INTO users (name, email, password) VALUES 
('John Doe', 'john.doe@company.com', NULL);

-- Example: Add multiple users at once
INSERT INTO users (name, email, password) VALUES 
('Jane Smith', 'jane.smith@company.com', NULL),
('Mike Johnson', 'mike.johnson@company.com', NULL),
('Sarah Wilson', 'sarah.wilson@company.com', NULL),
('David Brown', 'david.brown@company.com', NULL);

-- ===============================================
-- TEMPLATE FOR NEW USERS
-- ===============================================
-- Copy and modify this template for new users:
-- INSERT INTO users (name, email, password) VALUES 
-- ('Full Name', 'email@company.com', NULL);

-- ===============================================
-- NOTES:
-- ===============================================
-- 1. Always set password to NULL for new users
-- 2. Email must be unique (will get error if duplicate)
-- 3. Users will be prompted to set password on first login
-- 4. Name should be the full display name
-- 5. Email will be used as login username
