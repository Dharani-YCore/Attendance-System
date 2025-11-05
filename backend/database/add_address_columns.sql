-- Add address columns to attendance table for storing human-readable location
ALTER TABLE attendance 
ADD COLUMN IF NOT EXISTS check_in_address VARCHAR(500) DEFAULT NULL AFTER check_in_longitude,
ADD COLUMN IF NOT EXISTS check_out_address VARCHAR(500) DEFAULT NULL AFTER check_out_longitude;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_check_in_address ON attendance(check_in_address);
CREATE INDEX IF NOT EXISTS idx_attendance_check_out_address ON attendance(check_out_address);
