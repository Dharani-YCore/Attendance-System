<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if all required fields are provided
if (!empty($data->email) && !empty($data->old_password) && !empty($data->new_password) && !empty($data->confirm_password)) {
    
    // Check if new password and confirm password match
    if ($data->new_password !== $data->confirm_password) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "New password and confirm password do not match."
        ));
        exit();
    }
    
    // Check if new password is different from old password
    if ($data->old_password === $data->new_password) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "New password must be different from old password."
        ));
        exit();
    }
    
    // Check if user exists and get current password
    $query = "SELECT id, name, email, password, is_first_login FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify old password
        if (!password_verify($data->old_password, $row['password'])) {
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "message" => "Old password is incorrect."
            ));
            exit();
        }
        
        // Hash new password
        $new_password_hash = password_hash($data->new_password, PASSWORD_BCRYPT);
        
        // Update password in database
        $query = "UPDATE users SET password = ?, is_first_login = FALSE WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $new_password_hash);
        $stmt->bindParam(2, $data->email);
        
        if ($stmt->execute()) {
            // Generate JWT token for automatic login after password change
            $token = generateJWT($row['id'], $row['email']);
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Password changed successfully. You can now login with your new password.",
                "token" => $token,
                "user" => array(
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "email" => $row['email']
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to update password. Please try again."
            ));
        }
    } else {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Email, old password, new password, and confirm password are required."
    ));
}
?>
