<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Requested-By");
header("Access-Control-Allow-Credentials: true");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../config/database.php';
include_once '../services/email_service.php';

// Handle pre-flight requests (OPTIONS method)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST for this endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use POST."]);
    exit();
}

$raw = trim(file_get_contents("php://input"));
if ($raw === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Request body required."]);
    exit();
}

$data = json_decode($raw);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON in request body."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$emailService = new EmailService();

// Ensure email is provided and not blank
if (!isset($data->email) || trim($data->email) === '') {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Email is required."
    ));
    exit();
}

$email = trim($data->email);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid email format."
    ));
    exit();
}

// Ensure the user's email column is not NULL or empty before sending OTP
$query = "SELECT id, name, email FROM users WHERE email = ? AND email IS NOT NULL AND email <> '' LIMIT 0,1";
$stmt = $db->prepare($query);
$stmt->bindValue(1, $email);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate a random 4-digit OTP (allow leading zeros)
    $otp = sprintf("%04d", mt_rand(0, 9999));
    $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

    // Store OTP in database
    $query = "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at), created_at = NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->bindParam(2, $otp, PDO::PARAM_STR);
    $stmt->bindParam(3, $expiry, PDO::PARAM_STR);

    if ($stmt->execute()) {
        // Send OTP via email
        $emailResult = $emailService->sendOTP($email, $row['name'], $otp);

        if ($emailResult['success']) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "OTP sent to your email address successfully."
            ));
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Failed to send OTP email. Please check server configuration."
            ));
        }
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Failed to generate OTP."
        ));
    }
} else {
    http_response_code(404);
    echo json_encode(array(
        "success" => false,
        "message" => "User not found or email not set for this account."
    ));
}
?>