<?php
include_once '../cors.php';

// Test login - bypasses database connection
header('Content-Type: application/json');

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // For testing, accept any login
    if ($data->email && $data->password) {
        // Create a simple test token
        $token = "test-jwt-" . base64_encode($data->email . time());
        
        echo json_encode(array(
            "success" => true,
            "message" => "Test login successful",
            "token" => $token,
            "user" => array(
                "id" => 1,
                "name" => "Test User",
                "email" => $data->email
            ),
            "note" => "This is test mode - no database connection"
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid credentials"
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Email and password are required"
    ));
}
?>