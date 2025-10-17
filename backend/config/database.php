<?php
require_once __DIR__ . '/env.php';


class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'attendance');
        $this->username = env('DB_USERNAME', 'root');
        $this->password = env('DB_PASSWORD', '');
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo json_encode(array("success" => false, "message" => "Connection error: " . $exception->getMessage()));
            exit();
        }

        return $this->conn;
    }
}

// OTP Helper Functions
function generateOTP($length = 6) {
    $otp = "";
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

function sendOTPEmail($email, $name, $otp) {
    require_once __DIR__ . '/../services/email_service.php';
    $emailService = new EmailService();
    return $emailService->sendOTP($email, $name, $otp);
}


// JWT Helper Functions
function generateJWT($userId, $email) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]);
    
    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);
    
    $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, env('JWT_SECRET_KEY', 'your-secret-key'), true);
    $signatureEncoded = base64url_encode($signature);
    
    return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
}

function verifyJWT($jwt) {
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) != 3) {
        return false;
    }
    
    $header = base64url_decode($tokenParts[0]);
    $payload = base64url_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    
    $payloadData = json_decode($payload);
    if (!$payloadData) {
        return false;
    }
    
    $expiration = $payloadData->exp;
    $isTokenExpired = ($expiration - time()) < 0;
    
    if ($isTokenExpired) {
        return false;
    }
    
    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode($payload);
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, env('JWT_SECRET_KEY', 'your-secret-key'), true);
    $base64UrlSignature = base64url_encode($signature);
    
    $isSignatureValid = ($base64UrlSignature === $signatureProvided);
    
    if ($isSignatureValid) {
        return $payloadData;
    } else {
        return false;
    }
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function getAuthToken() {
    // Try multiple methods to get the authorization header
    $authHeader = null;
    
    // Method 1: getallheaders() function
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }
    
    // Method 2: $_SERVER variables
    if (!$authHeader) {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    }
    
    // Method 3: apache_request_headers() function
    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }
    
    // Extract Bearer token
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

function validateRequest() {
    $token = getAuthToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Access denied. No token provided."));
        exit();
    }
    
    $decoded = verifyJWT($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Invalid token."));
        exit();
    }
    
    return $decoded;
}
?>
