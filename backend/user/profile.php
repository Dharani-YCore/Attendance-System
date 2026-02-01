<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
$user = validateRequest();

// Get parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (!empty($user_id)) {
    // Resolve CRM tables/columns
    $usersTable = env('USER_TABLE', 'users');
    $userIdCol = env('USER_ID_COL', 'id');
    $emailCol = env('USER_EMAIL_COL', 'email');
    $nameCol = env('USER_NAME_COL', '');
    $firstNameCol = env('USER_FIRST_NAME_COL', '');
    $lastNameCol = env('USER_LAST_NAME_COL', '');
    $attendanceTable = env('ATTENDANCE_TABLE', 'attendance');

    // Build name select
    if (!empty($firstNameCol) && !empty($lastNameCol)) {
        $nameSelect = "CONCAT(TRIM($firstNameCol), ' ', TRIM($lastNameCol)) AS name";
    } elseif (!empty($nameCol)) {
        $nameSelect = "$nameCol AS name";
    } else {
        $nameSelect = "$emailCol AS name"; // fallback
    }

    // Get user profile
    $query = "SELECT $userIdCol AS id, $nameSelect, $emailCol AS email, created_at FROM $usersTable WHERE $userIdCol = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent attendance statistics
        $query = "SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days
                  FROM $attendanceTable 
                  WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(array(
            "success" => true,
            "user" => array(
                "id" => $row['id'],
                "name" => $row['name'],
                "email" => $row['email'],
                "created_at" => $row['created_at']
            ),
            "stats" => $stats
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID is required."
    ));
}
?>
