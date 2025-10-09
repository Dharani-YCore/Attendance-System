# Smart Attendance System

A comprehensive attendance management system built with Flutter frontend and PHP backend, featuring QR code scanning for attendance marking.

## Features

### Frontend (Flutter)
- **Modern UI Design**: Clean and intuitive user interface with gradient backgrounds and card-based layouts
- **User Authentication**: Login, registration, password management with JWT tokens
- **QR Code Scanner**: Real-time QR code scanning for attendance marking
- **Dashboard**: User-friendly dashboard with attendance overview and quick actions
- **Attendance History**: View detailed attendance records with status indicators
- **Reports & Analytics**: Visual charts and statistics using FL Chart
- **Profile Management**: Edit user profile information and view attendance stats
- **Responsive Design**: Works on various screen sizes

### Backend (PHP)
- **RESTful API**: Clean API endpoints for all operations
- **JWT Authentication**: Secure token-based authentication
- **Database Integration**: MySQL database with proper relationships
- **Password Security**: Bcrypt password hashing
- **CORS Support**: Cross-origin resource sharing enabled
- **Error Handling**: Comprehensive error handling and validation

## Technology Stack

### Frontend
- **Flutter**: Cross-platform mobile development
- **Dart**: Programming language
- **HTTP**: API communication
- **QR Code Scanner**: QR code scanning functionality
- **Shared Preferences**: Local data storage
- **FL Chart**: Data visualization
- **Intl**: Date formatting

### Backend
- **PHP**: Server-side programming
- **MySQL**: Database management
- **JWT**: Authentication tokens
- **PDO**: Database abstraction layer

## Project Structure

```
Attendance-System/
├── lib/
│   ├── main.dart                 # App entry point
│   ├── services/
│   │   └── api_service.dart      # API communication service
│   └── screens/
│       ├── login_screen.dart     # Login interface
│       ├── register_screen.dart  # User registration
│       ├── dashboard_screen.dart # Main dashboard
│       ├── qr_scanner_screen.dart # QR code scanner
│       ├── attendance_history_screen.dart # History view
│       ├── reports_screen.dart   # Analytics & reports
│       └── profile_screen.dart   # User profile management
├── backend/
│   ├── config/
│   │   └── database.php          # Database configuration & JWT helpers
│   ├── auth/
│   │   ├── login.php            # User login endpoint
│   │   ├── register.php         # User registration endpoint
│   │   ├── set_password.php     # Password setting endpoint
│   │   └── forgot_password.php  # Password recovery endpoint
│   ├── attendance/
│   │   ├── mark.php             # Mark attendance endpoint
│   │   ├── history.php          # Get attendance history
│   │   └── report.php           # Generate reports
│   └── user/
│       ├── profile.php          # Get user profile
│       └── update.php           # Update user profile
└── attendance.sql               # Database schema
```

## Setup Instructions

### Prerequisites
- Flutter SDK (3.9.2 or higher)
- PHP (7.4 or higher)
- MySQL/MariaDB
- Web server (Apache/Nginx) or XAMPP/WAMP

### Database Setup
1. Create a MySQL database named `attendance_system`
2. Import the database schema:
   ```sql
   mysql -u username -pattendance_system < attendance.sql
   ```

### Backend Setup
1. Copy the `backend` folder to your web server directory
2. Update database credentials in `backend/config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "attendance_system";
   private $username = "your_username";
   private $password = "your_password";
   ```
3. Update the JWT secret key in the same file
4. Ensure your web server has proper permissions for the backend folder

### Frontend Setup
1. Navigate to the project directory:
   ```bash
   cd Attendance-System
   ```
2. Install dependencies:
   ```bash
   flutter pub get
   ```
3. Update the API base URL in `lib/services/api_service.dart`:
   ```dart
   static const String baseUrl = 'http://your-server-url/attendance_system';
   ```
4. Run the application:
   ```bash
   flutter run
   ```

## API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `POST /auth/set_password.php` - Set user password
- `POST /auth/forgot_password.php` - Password recovery

### Attendance
- `POST /attendance/mark.php` - Mark attendance
- `GET /attendance/history.php` - Get attendance history
- `GET /attendance/report.php` - Generate attendance reports

### User Management
- `GET /user/profile.php` - Get user profile
- `POST /user/update.php` - Update user profile

## Database Schema

### Users Table
- `id`: Primary key
- `name`: User's full name
- `email`: Unique email address
- `password`: Hashed password
- `created_at`: Registration timestamp

### Attendance Table
- `id`: Primary key
- `user_id`: Foreign key to users table
- `date`: Attendance date
- `time`: Attendance time
- `status`: Present/Late/Absent/On Leave
- `created_at`: Record creation timestamp

### Reports Table
- `id`: Primary key
- `user_id`: Foreign key to users table
- `report_date`: Report date
- `morning_time`: Morning check-in time
- `evening_time`: Evening check-out time
- `created_at`: Record creation timestamp

## Usage

1. **Registration**: New users can register with name, email, and password
2. **Login**: Existing users login with email and password
3. **Dashboard**: View attendance overview and quick actions
4. **Mark Attendance**: Use QR scanner to mark attendance
5. **View History**: Check detailed attendance records
6. **Generate Reports**: View analytics and attendance statistics
7. **Profile Management**: Update personal information

## Security Features

- JWT token-based authentication
- Password hashing with bcrypt
- Input validation and sanitization
- CORS protection
- SQL injection prevention with PDO prepared statements

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions, please create an issue in the repository or contact the development team.
