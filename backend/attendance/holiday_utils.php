<?php
/**
 * Holiday Utilities Endpoint
 * Provides utility functions for holiday management
 */

header('Content-Type: application/json');
include_once '../cors.php';
include_once '../config/database.php';
include_once '../services/nager_date_service.php';

// Optional: Add authentication
// $user = validateRequest();

$database = new Database();
$db = $database->getConnection();
$nagerService = new NagerDateService();

// Get action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'countries':
        // Get available countries from Nager.Date API
        $countries = $nagerService->getAvailableCountries();
        
        if ($countries === false) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to fetch available countries"
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "data" => $countries
            ]);
        }
        break;
        
    case 'next':
        // Get next upcoming holidays for a country
        $countryCode = isset($_GET['country_code']) ? strtoupper($_GET['country_code']) : 'IN';
        $nextHolidays = $nagerService->getNextPublicHolidays($countryCode);
        
        if ($nextHolidays === false) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to fetch next holidays"
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "data" => $nextHolidays
            ]);
        }
        break;
        
    case 'check':
        // Check if a specific date is a holiday
        $countryCode = isset($_GET['country_code']) ? strtoupper($_GET['country_code']) : 'IN';
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        $isHoliday = $nagerService->isPublicHoliday($countryCode, $date);
        
        echo json_encode([
            "success" => true,
            "data" => [
                "date" => $date,
                "country_code" => $countryCode,
                "is_holiday" => $isHoliday
            ]
        ]);
        break;
        
    case 'longweekends':
        // Get long weekends for a country and year
        $countryCode = isset($_GET['country_code']) ? strtoupper($_GET['country_code']) : 'IN';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        
        $longWeekends = $nagerService->getLongWeekends($countryCode, $year);
        
        if ($longWeekends === false) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to fetch long weekends"
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "data" => $longWeekends
            ]);
        }
        break;
        
    case 'db_stats':
        // Get statistics from database
        $query = "SELECT 
                    country_code,
                    COUNT(*) as total_holidays,
                    YEAR(MIN(holiday_date)) as earliest_year,
                    YEAR(MAX(holiday_date)) as latest_year
                  FROM holidays
                  WHERE is_active = 1
                  GROUP BY country_code";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "data" => $stats
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid action. Available actions: countries, next, check, longweekends, db_stats"
        ]);
        break;
}
?>
