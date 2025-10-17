<?php
include_once '../cors.php';
include_once '../config/database.php';

header('Content-Type: application/json');

// Get all headers for debugging
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 'Not provided';

// Try to get token
$token = null;
if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
    }
}

// Response data
$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'headers_received' => $headers,
    'auth_header' => $authHeader,
    'token_extracted' => $token ? 'Token found (length: ' . strlen($token) . ')' : 'No token found',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
];

// If token exists, try to verify it
if ($token) {
    try {
        $decoded = verifyJWT($token);
        if ($decoded) {
            $response['token_status'] = 'Valid token';
            $response['user_data'] = $decoded;
        } else {
            $response['token_status'] = 'Invalid token';
        }
    } catch (Exception $e) {
        $response['token_status'] = 'Token verification error: ' . $e->getMessage();
    }
} else {
    $response['token_status'] = 'No token to verify';
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>