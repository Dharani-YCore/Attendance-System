@echo off
echo Fixing Apache for network access...

REM Stop Apache if running
taskkill /f /im httpd.exe >nul 2>&1

REM Wait a moment
timeout /t 2 /nobreak >nul

REM Add Windows Firewall exception for Apache
netsh advfirewall firewall delete rule name="Apache HTTP Server" >nul 2>&1
netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow program="C:\xampp\apache\bin\httpd.exe" enable=yes

REM Add port 80 exception
netsh advfirewall firewall delete rule name="Apache Port 80" >nul 2>&1
netsh advfirewall firewall add rule name="Apache Port 80" dir=in action=allow protocol=TCP localport=80

echo Starting Apache...
start "" "C:\xampp\apache\bin\httpd.exe"

REM Wait for Apache to start
timeout /t 5 /nobreak >nul

echo Testing local access...
curl -I http://localhost/Attendance-System/ 2>nul
if %errorlevel% == 0 (
    echo ✓ Local access: OK
) else (
    echo ✗ Local access: FAILED
)

echo Testing network access...
curl -I http://192.168.0.15/Attendance-System/ 2>nul
if %errorlevel% == 0 (
    echo ✓ Network access: OK
    echo Your phone can now connect to: http://192.168.0.15/Attendance-System/
) else (
    echo ✗ Network access: FAILED
    echo Check Windows Firewall settings manually
)

echo.
echo Apache setup complete!
echo Press any key to continue...
pause >nul