-- India Public Holidays 2025
-- Since India is not supported by Nager.Date API, use this SQL to add holidays manually

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
('2025-10-02', 'Dussehra', 'Festival', 'IN', 'Hindu festival celebrating the victory of Lord Rama over Ravana'),
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
SELECT COUNT(*) as 'India Holidays Added' FROM holidays WHERE country_code = 'IN' AND YEAR(holiday_date) = 2025;

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
