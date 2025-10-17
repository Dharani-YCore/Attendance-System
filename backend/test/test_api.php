<?php
include_once '../cors.php';

// Test API endpoint that doesn't require database
header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (isset($data->action)) {
        switch ($data->action) {
            case 'test_login':
                // Simulate successful login
                echo json_encode(array(
                    "success" => true,
                    "message" => "Test login successful",
                    "token" => "test-jwt-token-" . time(),
                    "user" => array(
                        "id" => 1,
                        "name" => "Test User",
                        "email" => $data->email ?? "test@example.com"
                    )
                ));
                break;
                
            case 'test_attendance':
                // Simulate attendance marking
                echo json_encode(array(
                    "success" => true,
                    "message" => "Attendance marked successfully.",
                    "status" => "Present",
                    "time" => date('H:i:s')
                ));
                break;
                
            default:
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unknown action"
                ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Action required"
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Only POST method allowed"
    ));
}
?>