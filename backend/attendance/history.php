<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
$user = validateRequest();

// Get parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;

if (!empty($user_id)) {
    // Get attendance history
    $query = "SELECT date, time, status, created_at FROM attendance 
              WHERE user_id = ? 
              ORDER BY date DESC, time DESC 
              LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $attendance_records = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $attendance_records[] = array(
            "date" => $row['date'],
            "time" => $row['time'],
            "status" => $row['status'],
            "created_at" => $row['created_at']
        );
    }
    
    // Get summary statistics
    $query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
              FROM attendance 
              WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(array(
        "success" => true,
        "data" => $attendance_records,
        "summary" => $summary
    ));
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID is required."
    ));
}
?>
