-- Holidays Table for Government Holidays
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type ENUM('National', 'Regional', 'Festival') DEFAULT 'National',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some common holidays for 2025
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
