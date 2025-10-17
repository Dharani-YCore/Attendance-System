<?php
/**
 * Test script for Nager.Date API integration
 * Access this file directly in browser to test the API
 */

header('Content-Type: text/html; charset=utf-8');
include_once '../services/nager_date_service.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nager.Date API Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .holiday-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        .badge-national { background: #007bff; color: white; }
        .badge-regional { background: #6c757d; color: white; }
        .badge-festival { background: #ffc107; color: black; }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px dashed #dee2e6;
            border-radius: 5px;
        }
        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Nager.Date API Integration Test</h1>
        <div class="info">
            <strong>✅ Testing Free Public Holiday API (No Authentication Required)</strong><br>
            This page tests the Nager.Date API integration for your Attendance System.
        </div>

        <?php
        $nagerService = new NagerDateService();
        
        // Test 1: Get Available Countries
        echo '<div class="test-section">';
        echo '<h2>Test 1: Get Available Countries</h2>';
        $countries = $nagerService->getAvailableCountries();
        
        if ($countries !== false && !empty($countries)) {
            echo '<div class="success">✅ Successfully fetched ' . count($countries) . ' countries!</div>';
            echo '<p>Sample countries (showing first 10):</p>';
            echo '<pre>' . json_encode(array_slice($countries, 0, 10), JSON_PRETTY_PRINT) . '</pre>';
        } else {
            echo '<div class="error">❌ Failed to fetch countries. API might be down or internet connection issue.</div>';
        }
        echo '</div>';
        
        // Test 2: Get India Holidays 2025
        echo '<div class="test-section">';
        echo '<h2>Test 2: Get India (IN) Public Holidays 2025</h2>';
        $holidays = $nagerService->getPublicHolidays('IN', 2025);
        
        if ($holidays !== false && !empty($holidays)) {
            echo '<div class="success">✅ Successfully fetched ' . count($holidays) . ' holidays for India in 2025!</div>';
            echo '<div style="margin-top: 20px;">';
            
            foreach ($holidays as $holiday) {
                $type = isset($holiday['types']) && in_array('Public', $holiday['types']) ? 'National' : 'Festival';
                $badgeClass = 'badge-' . strtolower($type);
                
                echo '<div class="holiday-card">';
                echo '<span class="badge ' . $badgeClass . '">' . $type . '</span>';
                echo '<strong>' . htmlspecialchars($holiday['name']) . '</strong> ';
                echo '<span style="color: #6c757d;">(' . $holiday['date'] . ')</span>';
                if (isset($holiday['localName']) && $holiday['localName'] !== $holiday['name']) {
                    echo '<br><small>Local: ' . htmlspecialchars($holiday['localName']) . '</small>';
                }
                echo '</div>';
            }
            echo '</div>';
            
            echo '<h3>Raw API Response (first holiday):</h3>';
            echo '<pre>' . json_encode($holidays[0], JSON_PRETTY_PRINT) . '</pre>';
        } else {
            echo '<div class="error">❌ Failed to fetch holidays for India.</div>';
        }
        echo '</div>';
        
        // Test 3: Check if today is a holiday
        echo '<div class="test-section">';
        echo '<h2>Test 3: Check if Today is a Holiday in India</h2>';
        $today = date('Y-m-d');
        $isHoliday = $nagerService->isPublicHoliday('IN', $today);
        
        echo '<p>Checking date: <strong>' . $today . '</strong></p>';
        if ($isHoliday) {
            echo '<div class="success">✅ Yes! Today is a public holiday in India!</div>';
        } else {
            echo '<div class="info">ℹ️ Today is not a public holiday in India.</div>';
        }
        echo '</div>';
        
        // Test 4: Get Next Public Holidays
        echo '<div class="test-section">';
        echo '<h2>Test 4: Get Next Upcoming Holidays for India</h2>';
        $nextHolidays = $nagerService->getNextPublicHolidays('IN');
        
        if ($nextHolidays !== false && !empty($nextHolidays)) {
            echo '<div class="success">✅ Found ' . count($nextHolidays) . ' upcoming holidays!</div>';
            echo '<div style="margin-top: 20px;">';
            
            foreach ($nextHolidays as $holiday) {
                echo '<div class="holiday-card">';
                echo '<strong>' . htmlspecialchars($holiday['name']) . '</strong> ';
                echo '<span style="color: #6c757d;">(' . $holiday['date'] . ')</span>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="info">ℹ️ No upcoming holidays found or API error.</div>';
        }
        echo '</div>';
        
        // Test 5: Parse for Database
        echo '<div class="test-section">';
        echo '<h2>Test 5: Parse Holidays for Database Format</h2>';
        if ($holidays !== false && !empty($holidays)) {
            $parsed = $nagerService->parseHolidaysForDB($holidays, 'IN');
            echo '<div class="success">✅ Parsed ' . count($parsed) . ' holidays ready for database insertion!</div>';
            echo '<h3>Sample parsed data (first 3 holidays):</h3>';
            echo '<pre>' . json_encode(array_slice($parsed, 0, 3), JSON_PRETTY_PRINT) . '</pre>';
        }
        echo '</div>';
        
        // Instructions
        echo '<div class="test-section" style="background: #fff3cd; border-color: #ffc107;">';
        echo '<h2>📋 Next Steps</h2>';
        echo '<ol style="line-height: 1.8;">';
        echo '<li><strong>Update Database Schema:</strong> Run <code>migrate_holidays_table.sql</code></li>';
        echo '<li><strong>Sync Holidays:</strong> Use the sync endpoint to import holidays</li>';
        echo '<li><strong>Test Endpoints:</strong> Try the API endpoints in your application</li>';
        echo '</ol>';
        
        echo '<h3>Quick Test Commands:</h3>';
        echo '<pre style="background: #fff; color: #333; border: 1px solid #ddd;">';
        echo '# Sync India holidays for 2025
curl -X POST http://localhost/Attendance-System/backend/attendance/sync_holidays.php \\
  -H "Content-Type: application/json" \\
  -d \'{"country_code": "IN", "year": 2025}\'

# Get holidays from database
curl http://localhost/Attendance-System/backend/attendance/holidays.php?country_code=IN

# Get available countries
curl http://localhost/Attendance-System/backend/attendance/holiday_utils.php?action=countries';
        echo '</pre>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px;">
            <h3>🔗 Quick Links</h3>
            <button onclick="window.open('http://localhost/Attendance-System/backend/attendance/holiday_utils.php?action=countries', '_blank')">
                View Available Countries
            </button>
            <button onclick="window.open('http://localhost/Attendance-System/backend/attendance/holidays.php?country_code=IN', '_blank')">
                View Holidays from DB
            </button>
            <button onclick="syncHolidays()">
                Sync India Holidays (2025)
            </button>
        </div>
        
        <div id="syncResult" style="margin-top: 20px;"></div>
    </div>
    
    <script>
        function syncHolidays() {
            const resultDiv = document.getElementById('syncResult');
            resultDiv.innerHTML = '<div class="info">⏳ Syncing holidays from Nager.Date API...</div>';
            
            fetch('sync_holidays.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    country_code: 'IN',
                    year: 2025
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <strong>✅ Sync Successful!</strong><br>
                            Country: ${data.data.country_code}<br>
                            Year: ${data.data.year}<br>
                            Total Holidays: ${data.data.total_holidays}<br>
                            Synced: ${data.data.synced}<br>
                            Failed: ${data.data.failed}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="error">❌ ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            });
        }
    </script>
</body>
</html>
