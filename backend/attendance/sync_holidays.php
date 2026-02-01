<?php
/**
 * Holiday Sync Endpoint
 * Fetches holidays from Nager.Date API and syncs to database
 */

header('Content-Type: application/json');
include_once '../cors.php';
include_once '../config/database.php';
include_once '../services/nager_date_service.php';

// Validate the request (optional: can add admin authentication)
// $user = validateRequest();

$database = new Database();
$db = $database->getConnection();
$nagerService = new NagerDateService();

// Get parameters from request
$data = json_decode(file_get_contents("php://input"), true);

// Default to India (IN) if no country specified
$countryCode = isset($data['country_code']) ? strtoupper($data['country_code']) : 'IN';
$year = isset($data['year']) ? (int)$data['year'] : date('Y');

// Validate country code (2 characters)
if (strlen($countryCode) !== 2) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid country code. Must be 2 characters (ISO 3166-1 alpha-2)"
    ]);
    exit();
}

// Fetch holidays from Nager.Date API
$holidays = $nagerService->getPublicHolidays($countryCode, $year);

if ($holidays === false) {
    // Check if country exists but has no data
    $allCountries = $nagerService->getAvailableCountries();
    $countryExists = false;
    $countryName = '';
    
    if ($allCountries !== false) {
        foreach ($allCountries as $country) {
            if ($country['countryCode'] === $countryCode) {
                $countryExists = true;
                $countryName = $country['name'];
                break;
            }
        }
    }
    
    if ($countryExists) {
        // Country exists but no data available
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "No holiday data available for {$countryName} ({$countryCode}) in {$year}.",
            "suggestion" => "This country is listed in the API but doesn't have holiday data yet. Try a different year (2024, 2026) or use a well-supported country like US, GB, CA, AU, or DE. You can also add holidays manually using the database.",
            "available_countries_url" => "http://" . $_SERVER['HTTP_HOST'] . "/Attendance-System/backend/attendance/check_country_support.php",
            "data" => [
                "country_code" => $countryCode,
                "country_name" => $countryName,
                "year" => $year,
                "supported" => false
            ]
        ]);
    } else {
        // Invalid country code
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid country code: {$countryCode}",
            "suggestion" => "Please use a valid ISO 3166-1 alpha-2 country code (e.g., US, GB, CA, AU, DE)",
            "get_countries_url" => "http://" . $_SERVER['HTTP_HOST'] . "/Attendance-System/backend/attendance/holiday_utils.php?action=countries"
        ]);
    }
    exit();
}

if (empty($holidays)) {
    echo json_encode([
        "success" => true,
        "message" => "No holidays found for the specified country and year.",
        "data" => [
            "country_code" => $countryCode,
            "year" => $year,
            "count" => 0
        ]
    ]);
    exit();
}

// Parse holidays for database
$parsedHolidays = $nagerService->parseHolidaysForDB($holidays, $countryCode);

// Prepare insert/update query
$query = "INSERT INTO holidays (holiday_date, holiday_name, holiday_type, country_code, description) 
          VALUES (?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE 
            holiday_name = VALUES(holiday_name),
            holiday_type = VALUES(holiday_type),
            description = VALUES(description),
            updated_at = CURRENT_TIMESTAMP";

$stmt = $db->prepare($query);

$successCount = 0;
$errorCount = 0;
$errors = [];

// Begin transaction
$db->beginTransaction();

try {
    foreach ($parsedHolidays as $holiday) {
        $description = "Synced from Nager.Date API for " . $countryCode;
        
        $stmt->bindParam(1, $holiday['date']);
        $stmt->bindParam(2, $holiday['name']);
        $stmt->bindParam(3, $holiday['type']);
        $stmt->bindParam(4, $holiday['country_code']);
        $stmt->bindParam(5, $description);
        
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = $holiday['name'] . " (" . $holiday['date'] . ")";
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        "success" => true,
        "message" => "Holidays synced successfully",
        "data" => [
            "country_code" => $countryCode,
            "year" => $year,
            "total_holidays" => count($parsedHolidays),
            "synced" => $successCount,
            "failed" => $errorCount,
            "errors" => $errors
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
