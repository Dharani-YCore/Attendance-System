<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // Check if user exists
    $query = "SELECT id FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Hash the new password
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        // Update user password
        $updateQuery = "UPDATE users SET password = ? WHERE email = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(1, $password_hash);
        $updateStmt->bindParam(2, $data->email);
        
        if ($updateStmt->execute()) {
            // Clean up used password reset records for this email
            $cleanupQuery = "DELETE FROM password_resets WHERE email = ?";
            $cleanupStmt = $db->prepare($cleanupQuery);
            $cleanupStmt->bindParam(1, $data->email);
            $cleanupStmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "Password reset successfully."
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Failed to reset password."
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
        "message" => "Email and new password are required."
    ));
}
?>
