<?php
include_once '../config/database.php';
include_once '../services/email_service.php';

$database = new Database();
$db = $database->getConnection();
$emailService = new EmailService();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email)) {
    // Check if user exists
    $query = "SELECT id, name FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate a random 4-digit OTP
        $otp = sprintf("%04d", mt_rand(1000, 9999));
        $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now
        
        // Store OTP in database (reset used=FALSE for new OTP)
        $query = "INSERT INTO password_resets (email, otp, expires_at, used) VALUES (?, ?, ?, FALSE)
                  ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), used = FALSE, created_at = NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->email);
        $stmt->bindParam(2, $otp);
        $stmt->bindParam(3, $expiry);
        
        if ($stmt->execute()) {
            // Send OTP via email
            $emailResult = $emailService->sendOTP($data->email, $row['name'], $otp);

            if ($emailResult['success']) {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "OTP sent to your email address."
                ));
            } else {
                http_response_code(500);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unable to send OTP. Please try again later.",
                    "error" => $emailResult['message'] // For debugging
                ));
            }
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to store OTP."
            ));
        }
    } else {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "User with this email does not exist."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Email is required."
    ));
}
?>
