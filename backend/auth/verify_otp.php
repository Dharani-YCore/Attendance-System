<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && isset($data->otp)) {
    $email = trim($data->email);
    // Normalize OTP to 4-digit string (allow leading zeros)
    $inputOtp = trim((string)$data->otp);
    if (ctype_digit($inputOtp)) {
        $inputOtp = sprintf("%04d", (int)$inputOtp);
    }

    // Check if OTP exists and is valid
    $query = "SELECT id, otp, expires_at, used FROM password_resets 
              WHERE email = ? AND used = FALSE 
              ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email);
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
        
        // Verify OTP (string compare)
        if ((string)$row['otp'] === $inputOtp) {
            // Mark OTP as used
            $updateQuery = "UPDATE password_resets SET used = TRUE WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(1, $row['id']);
            $updateStmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "OTP verified successfully.",
                "email" => $email
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
