<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Validate JWT token (auth protected)
$decoded = validateRequest();

try {
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM users");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(array(
        "success" => true,
        "total" => intval($row['total'])
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Failed to fetch total users: " . $e->getMessage()
    ));
}
?>
