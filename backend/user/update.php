<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
$user = validateRequest();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->name) && !empty($data->email)) {
    // Check if email is already taken by another user
    $query = "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->bindParam(2, $data->user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Email is already taken by another user."
        ));
    } else {
        // Update user profile
        $query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->name);
        $stmt->bindParam(2, $data->email);
        $stmt->bindParam(3, $data->user_id);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Profile updated successfully."
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to update profile."
            ));
        }
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID, name and email are required."
    ));
}
?>
