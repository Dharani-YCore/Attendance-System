@echo off
echo Starting Attendance System Server...

REM Kill any existing Apache processes
taskkill /f /im httpd.exe >nul 2>&1

REM Start XAMPP MySQL if not running
net start mysql >nul 2>&1

REM Start Apache
echo Starting Apache...
start "" "C:\xampp\apache\bin\httpd.exe"

REM Wait for Apache to start
timeout /t 3 /nobreak >nul

REM Test local connection
echo Testing server...
curl -s -I http://localhost/Attendance-System/ >nul
if %errorlevel% == 0 (
    echo ✓ Server started successfully!
    echo ✓ Local access: http://localhost/Attendance-System/
    echo ✓ Network access: http://192.168.0.15/Attendance-System/
    echo.
    echo Your phone should connect to: 192.168.0.15
    echo Make sure both devices are on the same WiFi network.
) else (
    echo ✗ Server failed to start. Check XAMPP Control Panel.
)

echo.
echo Press any key to exit...
pause >nul