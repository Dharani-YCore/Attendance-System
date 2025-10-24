-- ================================================================
-- COMPLETE HOLIDAYS TABLE SETUP
-- Creates table with proper structure and populates with India holidays 2025
-- ================================================================

-- Drop existing table if you want to recreate (CAUTION: This deletes all existing holidays)
-- DROP TABLE IF EXISTS holidays;

-- Create holidays table with all required columns
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('National', 'Regional', 'Festival', 'Company') DEFAULT 'National',
    country_code VARCHAR(2) DEFAULT 'IN' COMMENT 'ISO 3166-1 alpha-2 country code',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_holiday (holiday_date, country_code),
    INDEX idx_holiday_date (holiday_date),
    INDEX idx_holiday_type (holiday_type),
    INDEX idx_country_date (country_code, holiday_date),
    INDEX idx_active_holidays (is_active, holiday_date)
);

-- Insert India holidays for 2025
INSERT INTO holidays (holiday_date, holiday_name, holiday_type, country_code, description) VALUES
-- National Holidays
('2025-01-26', 'Republic Day', 'National', 'IN', 'Celebrates the adoption of the Constitution of India'),
('2025-04-14', 'Ambedkar Jayanti', 'National', 'IN', 'Birth anniversary of Dr. B.R. Ambedkar'),
('2025-04-18', 'Good Friday', 'National', 'IN', 'Christian holiday commemorating the crucifixion of Jesus Christ'),
('2025-05-01', 'May Day', 'National', 'IN', 'International Workers Day / Labour Day'),
('2025-08-15', 'Independence Day', 'National', 'IN', 'Commemorates independence from British rule'),
('2025-10-02', 'Gandhi Jayanti', 'National', 'IN', 'Birth anniversary of Mahatma Gandhi'),
('2025-12-25', 'Christmas', 'National', 'IN', 'Christian holiday celebrating the birth of Jesus Christ'),

-- Hindu Festivals
('2025-01-14', 'Makar Sankranti', 'Festival', 'IN', 'Hindu festival marking the transition of the sun into Capricorn'),
('2025-03-14', 'Holi', 'Festival', 'IN', 'Hindu festival of colors celebrating spring and victory of good over evil'),
('2025-03-30', 'Ram Navami', 'Festival', 'IN', 'Hindu festival celebrating the birth of Lord Rama'),
('2025-08-27', 'Janmashtami', 'Festival', 'IN', 'Hindu festival celebrating the birth of Lord Krishna'),
('2025-10-12', 'Dussehra', 'Festival', 'IN', 'Hindu festival celebrating the victory of Lord Rama over Ravana'),
('2025-10-20', 'Diwali', 'Festival', 'IN', 'Hindu festival of lights celebrating the victory of light over darkness'),

-- Other Religious Festivals
('2025-04-10', 'Mahavir Jayanti', 'Festival', 'IN', 'Jain festival celebrating the birth of Lord Mahavir'),
('2025-11-05', 'Guru Nanak Jayanti', 'Festival', 'IN', 'Sikh festival celebrating the birth of Guru Nanak'),

-- Regional Holidays (popular ones)
('2025-04-13', 'Ugadi', 'Regional', 'IN', 'Telugu and Kannada New Year'),
('2025-04-14', 'Baisakhi', 'Regional', 'IN', 'Punjabi New Year and harvest festival'),
('2025-08-16', 'Raksha Bandhan', 'Regional', 'IN', 'Hindu festival celebrating the bond between brothers and sisters'),
('2025-09-15', 'Onam', 'Regional', 'IN', 'Harvest festival celebrated in Kerala'),
('2025-10-21', 'Govardhan Puja', 'Regional', 'IN', 'Hindu festival celebrated a day after Diwali')

ON DUPLICATE KEY UPDATE 
    holiday_name = VALUES(holiday_name),
    holiday_type = VALUES(holiday_type),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- Verify the insert
SELECT COUNT(*) as 'Total Holidays Added' FROM holidays WHERE country_code = 'IN' AND YEAR(holiday_date) = 2025;

-- Show all India holidays for 2025
SELECT 
    holiday_date as 'Date',
    holiday_name as 'Holiday',
    holiday_type as 'Type',
    description as 'Description'
FROM holidays 
WHERE country_code = 'IN' 
    AND YEAR(holiday_date) = 2025
    AND is_active = 1
ORDER BY holiday_date;
