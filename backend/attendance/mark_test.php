<?php
include_once '../cors.php';

// Test version - bypasses database connection
header('Content-Type: application/json');

// Simulate token validation (for testing only)
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "No token provided"
    ));
    exit;
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->status)) {
    $current_time = date('H:i:s');
    $hour = (int)date('H');
    
    // Determine status based on time
    if ($hour >= 9 && $hour < 10) {
        $status = 'Present';
    } elseif ($hour >= 10 && $hour < 12) {
        $status = 'Late';  
    } else {
        $status = 'Present'; // Default for testing
    }
    
    // Simulate successful attendance marking
    echo json_encode(array(
        "success" => true,
        "message" => "Test: Attendance marked successfully.",
        "status" => $status,
        "time" => $current_time,
        "note" => "This is test mode - no database connection"
    ));
    
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID and status are required."
    ));
}
?>