<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // Check if user exists
    $query = "SELECT id, name FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Hash password
        $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
        
        // Update password
        $query = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $password_hash);
        $stmt->bindParam(2, $data->email);
        
        if ($stmt->execute()) {
            // Set is_first_login to FALSE
            $query = "UPDATE users SET is_first_login = FALSE WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->email);
            $stmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "Password set successfully."
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to set password."
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Email and password are required."
    ));
}
?>
