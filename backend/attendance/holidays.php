<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get holidays for the specified date range
$query = "SELECT 
            holiday_date,
            holiday_name,
            holiday_type
          FROM holidays 
          WHERE holiday_date BETWEEN ? AND ?
          ORDER BY holiday_date";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $start_date);
$stmt->bindParam(2, $end_date);
$stmt->execute();

$holidays = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $holidays[] = array(
        "date" => $row['holiday_date'],
        "name" => $row['holiday_name'],
        "type" => $row['holiday_type']
    );
}

echo json_encode(array(
    "success" => true,
    "data" => $holidays
));
?>
