-- Migration script to update existing holidays table
-- Run this if you already have the holidays table

-- Add new columns to existing table
ALTER TABLE holidays 
ADD COLUMN IF NOT EXISTS country_code VARCHAR(2) DEFAULT 'IN' COMMENT 'ISO 3166-1 alpha-2 country code' AFTER holiday_type,
ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER country_code,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER description,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Drop old unique constraint on holiday_date only
ALTER TABLE holidays DROP INDEX IF EXISTS holiday_date;

-- Add new unique constraint on holiday_date and country_code combination
ALTER TABLE holidays ADD UNIQUE KEY IF NOT EXISTS unique_holiday (holiday_date, country_code);

-- Update existing records to have country_code 'IN' if NULL
UPDATE holidays SET country_code = 'IN' WHERE country_code IS NULL;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_country_date ON holidays(country_code, holiday_date);
CREATE INDEX IF NOT EXISTS idx_active_holidays ON holidays(is_active, holiday_date);
