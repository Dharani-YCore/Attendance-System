<?php
header('Content-Type: application/json');
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$country_code = isset($_GET['country_code']) ? strtoupper($_GET['country_code']) : 'IN';
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Build query with optional filters
$query = "SELECT 
            id,
            holiday_date,
            holiday_name,
            holiday_type,
            country_code,
            description
          FROM holidays 
          WHERE holiday_date BETWEEN ? AND ?
            AND country_code = ?
            AND is_active = 1";

$params = [$start_date, $end_date, $country_code];

// Add type filter if specified
if ($type && in_array($type, ['National', 'Regional', 'Festival'])) {
    $query .= " AND holiday_type = ?";
    $params[] = $type;
}

$query .= " ORDER BY holiday_date";

$stmt = $db->prepare($query);
$stmt->execute($params);

$holidays = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $holidays[] = array(
        "id" => (int)$row['id'],
        "date" => $row['holiday_date'],
        "name" => $row['holiday_name'],
        "type" => $row['holiday_type'],
        "country_code" => $row['country_code'],
        "description" => $row['description']
    );
}

echo json_encode(array(
    "success" => true,
    "data" => $holidays,
    "meta" => [
        "start_date" => $start_date,
        "end_date" => $end_date,
        "country_code" => $country_code,
        "count" => count($holidays)
    ]
));
?>
