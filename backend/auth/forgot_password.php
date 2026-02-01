<?php
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../objects/user.php';
require_once '../services/email_service.php';

// Set timezone to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
// Resolve CRM table/column mappings
$usersTable = env('USER_TABLE', 'users');
$userIdCol = env('USER_ID_COL', 'id');
$emailCol = env('USER_EMAIL_COL', 'email');
$nameCol = env('USER_NAME_COL', '');
$firstNameCol = env('USER_FIRST_NAME_COL', '');
$lastNameCol = env('USER_LAST_NAME_COL', '');

// Build name select
if (!empty($firstNameCol) && !empty($lastNameCol)) {
    $nameSelect = "CONCAT(TRIM($firstNameCol), ' ', TRIM($lastNameCol)) AS name";
} elseif (!empty($nameCol)) {
    $nameSelect = "$nameCol AS name";
} else {
    $nameSelect = "$emailCol AS name"; // fallback
}

$query = "SELECT $userIdCol AS id, $nameSelect, $emailCol AS email FROM $usersTable WHERE LOWER(TRIM($emailCol)) = LOWER(TRIM(?)) AND $emailCol IS NOT NULL AND $emailCol <> '' LIMIT 0,1";
$stmt = $db->prepare($query);
$stmt->bindValue(1, $email);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate a random 4-digit OTP (allow leading zeros)
    $otp = sprintf("%04d", mt_rand(0, 9999));
    $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

    // Store OTP in database (reset used=FALSE for new OTP)
    $passwordResetsTable = env('PASSWORD_RESETS_TABLE', 'password_resets');
    $resetsEmailCol = env('PASSWORD_RESETS_EMAIL_COL', 'email');
    $resetsOtpCol = env('PASSWORD_RESETS_OTP_COL', 'otp');
    $resetsExpiresCol = env('PASSWORD_RESETS_EXPIRES_COL', 'expires_at');
    $resetsUsedCol = env('PASSWORD_RESETS_USED_COL', 'used');

    $query = "INSERT INTO $passwordResetsTable ($resetsEmailCol, $resetsOtpCol, $resetsExpiresCol, $resetsUsedCol) VALUES (?, ?, ?, FALSE)
              ON DUPLICATE KEY UPDATE $resetsOtpCol = VALUES($resetsOtpCol), $resetsExpiresCol = VALUES($resetsExpiresCol), $resetsUsedCol = FALSE, created_at = NOW()";
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
            // Development-friendly fallback: if email isn't configured, still return the OTP
            $devMode = env('DEV_MODE', 'true');
            if ($devMode === 'true') {
                error_log('[DEV_MODE] Forgot password OTP for ' . $data->email . ' is: ' . $otp);
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "OTP generated (email not sent in DEV_MODE).",
                    "otp" => $otp
                ));
            } else {
                http_response_code(500);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unable to send OTP. Please try again later.",
                    "error" => $emailResult['message']
                ));
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Failed to generate OTP."
        ));
    }
} else {
    // Do not reveal whether the email exists (security best practice)
    $devMode = env('DEV_MODE', 'true');
    if ($devMode === 'true') {
        error_log('[DEV_MODE] Forgot password requested for non-existing email: ' . $email);
    }
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "If an account exists for this email, an OTP has been sent."
    ));
}
?>