<?php
include_once '../cors.php';
include_once '../config/database.php';

// JSON response
header("Content-Type: application/json; charset=UTF-8");

// Timezone
date_default_timezone_set('Asia/Kolkata');

try {
    $database = new Database();
    $db = $database->getConnection();

    // Resolve CRM table/column mappings from environment
    $attendanceTable = env('ATTENDANCE_TABLE', 'attendance');
    $usersTable = env('USER_TABLE', 'users');
    $userIdCol = env('USER_ID_COL', 'id');
    $userNameCol = env('USER_NAME_COL', '');
    $firstNameCol = env('USER_FIRST_NAME_COL', '');
    $lastNameCol = env('USER_LAST_NAME_COL', '');

    // Build name select
    if (!empty($firstNameCol) && !empty($lastNameCol)) {
        $nameSelect = "CONCAT(TRIM($firstNameCol), ' ', TRIM($lastNameCol)) AS name";
    } elseif (!empty($userNameCol)) {
        $nameSelect = "$userNameCol AS name";
    } else {
        $nameSelect = "'' AS name"; // fallback empty name
    }

    $today = date('Y-m-d');

    // Only today's rows with a check-in (logged in persons)
    $sql = "SELECT 
                a.user_id,
                $nameSelect,
                a.date,
                a.check_in_time,
                a.check_out_time,
                a.attendance_type,
                a.status
            FROM $attendanceTable a
            LEFT JOIN $usersTable u ON u.$userIdCol = a.user_id
            WHERE a.date = ? AND a.check_in_time IS NOT NULL
            ORDER BY a.check_in_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $today);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'date' => $today,
        'count' => count($rows),
        'data' => $rows,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}

?>


