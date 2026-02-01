# Attendance System Database Setup

## Overview
The complete attendance system database schema is now contained in a single file: `attendance_system_complete.sql`

## Quick Setup

### 1. Import the Complete Schema
```sql
-- Option 1: Create new database and import
CREATE DATABASE attendance_system;
USE attendance_system;
SOURCE attendance_system_complete.sql;

-- Option 2: Import into existing database
USE your_existing_database;
SOURCE attendance_system_complete.sql;
```

### 2. Via Command Line
```bash
# Create database and import
mysql -u root -p -e "CREATE DATABASE attendance_system;"
mysql -u root -p attendance_system < attendance_system_complete.sql

# Or import into existing database
mysql -u root -p your_database < attendance_system_complete.sql
```

### 3. Via phpMyAdmin
1. Open phpMyAdmin
2. Create new database `attendance_system` (or select existing)
3. Go to "Import" tab
4. Choose `attendance_system_complete.sql` file
5. Click "Go"

## What's Included

### ✅ Complete Database Schema
- **users** - User accounts and authentication
- **password_resets** - OTP tokens for password reset
- **attendance** - Attendance records with check-in/check-out support
- **reports** - Daily report summaries with working hours
- **qr_codes** - QR code information and validation
- **qr_attendance_logs** - QR scan tracking logs
- **holidays** - Government and company holidays

### ✅ Advanced Features
- **Performance Indexes** - Optimized for fast queries
- **Foreign Key Constraints** - Data integrity enforcement
- **Database Views** - Pre-built summary queries
- **Triggers** - Automatic data consistency maintenance
- **Stored Procedures** - Common operations automation

### ✅ Sample Data
- **2025 Holiday Calendar** - Indian national holidays
- **Default Admin User** - Email: `admin@attendance.local`, Password: `admin123`

### ✅ Check-In/Check-Out Features
- Automatic total hours calculation
- Support for partial day attendance
- Business logic for late arrival detection
- Complete attendance workflow tracking

## Post-Setup Configuration

### Update Database Connection
Make sure your `backend/config/database.php` matches your setup:
```php
private $host = "localhost";
private $db_name = "attendance_system";  // Your database name
private $username = "root";              // Your MySQL username
private $password = "";                  // Your MySQL password
```

### Test the Setup
1. Start your web server (Apache/Nginx)
2. Start MySQL service
3. Test the Flutter app login with:
   - Email: `admin@attendance.local`
   - Password: `admin123`

## Database Schema Overview

```
users
├── id (PK)
├── name, email, password
└── created_at, updated_at

attendance
├── id (PK)
├── user_id (FK → users.id)
├── date, check_in_time, check_out_time
├── total_hours, status, attendance_type
└── created_at, updated_at

reports
├── id (PK)
├── user_id (FK → users.id)
├── report_date, morning_time, evening_time
├── total_working_hours
└── created_at, updated_at

qr_codes
├── id (PK)
├── qr_id, qr_type, location
├── valid_date, valid_time
├── qr_data, is_active
└── created_at, updated_at

... and more
```

## Maintenance

### Cleanup Old Data
```sql
-- Clean expired password reset tokens
CALL CleanupExpiredTokens();

-- Generate user attendance report
CALL GenerateUserAttendanceReport(1, '2025-01-01', '2025-12-31');
```

### View Summary Data
```sql
-- Today's attendance summary
SELECT * FROM daily_attendance_summary;

-- User attendance statistics
SELECT * FROM user_attendance_summary;
```

## Success! 🎉
Your attendance system database is now ready with full check-in/check-out functionality!