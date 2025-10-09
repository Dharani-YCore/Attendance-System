<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->otp)) {
    // Check if OTP exists and is valid
    $query = "SELECT id, otp, expires_at, used FROM password_resets 
              WHERE email = ? AND used = FALSE 
              ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if OTP has expired
        if (strtotime($row['expires_at']) < time()) {
            echo json_encode(array(
                "success" => false,
                "message" => "OTP has expired. Please request a new one."
            ));
            exit();
        }
        
        // Verify OTP
        if ($row['otp'] === $data->otp) {
            // Mark OTP as used
            $updateQuery = "UPDATE password_resets SET used = TRUE WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(1, $row['id']);
            $updateStmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "OTP verified successfully.",
                "email" => $data->email
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid OTP. Please check and try again."
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "No valid OTP found for this email."
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Email and OTP are required."
    ));
}
?>
