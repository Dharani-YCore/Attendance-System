<?php
// Robust CORS configuration
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
$requestHeaders = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] : '';

// For preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($origin !== '*') {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: ' . ($requestHeaders ?: 'Content-Type, Authorization, X-Requested-With'));
    header('Access-Control-Max-Age: 86400'); // 24 hours
    http_response_code(200);
    exit();
}

// For actual requests
if ($origin !== '*') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');
?>
