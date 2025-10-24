<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Set timezone to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['qr_data']) || empty($input['qr_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'QR data is required']);
    exit;
}

try {
    // Parse QR data
    $qrData = json_decode($input['qr_data'], true);
    
    if (!$qrData) {
        throw new Exception('Invalid QR code format');
    }
    
    // Validate required fields
    $requiredFields = ['type', 'id', 'location', 'date', 'timestamp', 'version'];
    foreach ($requiredFields as $field) {
        if (!isset($qrData[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if QR code exists and is valid
    $checkQuery = "SELECT * FROM qr_codes WHERE 
                   qr_id = :qr_id AND 
                   is_active = 1";
    
    $stmt = $db->prepare($checkQuery);
    $stmt->bindParam(':qr_id', $qrData['id']);
    $stmt->execute();
    
    $qrRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$qrRecord) {
        // QR code doesn't exist, create it
        $insertQuery = "INSERT INTO qr_codes 
                       (qr_id, qr_type, location, valid_date, valid_time, qr_data, created_at, is_active) 
                       VALUES 
                       (:qr_id, :qr_type, :location, :valid_date, :valid_time, :qr_data, NOW(), 1)";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':qr_id', $qrData['id']);
        $insertStmt->bindParam(':qr_type', $qrData['type']);
        $insertStmt->bindParam(':location', $qrData['location']);
        $insertStmt->bindParam(':valid_date', $qrData['date']);
        $insertStmt->bindParam(':valid_time', $qrData['time']);
        $insertStmt->bindParam(':qr_data', $input['qr_data']);
        
        if (!$insertStmt->execute()) {
            throw new Exception('Failed to register QR code');
        }
        
        $qrRecord = [
            'qr_id' => $qrData['id'],
            'qr_type' => $qrData['type'],
            'location' => $qrData['location'],
            'valid_date' => $qrData['date'],
            'valid_time' => $qrData['time'],
            'is_active' => 1
        ];
    }
    
    // Validate QR code constraints
    $validationResult = validateQRConstraints($qrRecord);
    
    if (!$validationResult['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $validationResult['message'],
            'qr_info' => [
                'id' => $qrRecord['qr_id'],
                'type' => $qrRecord['qr_type'],
                'location' => $qrRecord['location'],
                'valid_date' => $qrRecord['valid_date']
            ]
        ]);
        exit;
    }
    
    // QR code is valid
    echo json_encode([
        'success' => true,
        'message' => 'QR code is valid',
        'qr_info' => [
            'id' => $qrRecord['qr_id'],
            'type' => $qrRecord['qr_type'],
            'location' => $qrRecord['location'],
            'valid_date' => $qrRecord['valid_date'],
            'valid_time' => $qrRecord['valid_time']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function validateQRConstraints($qrRecord) {
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    // Check if QR code is active
    if (!$qrRecord['is_active']) {
        return ['valid' => false, 'message' => 'This QR code has been deactivated'];
    }
    
    // Check date validity
    if ($qrRecord['valid_date'] && $qrRecord['valid_date'] !== $today) {
        if ($qrRecord['valid_date'] < $today) {
            return ['valid' => false, 'message' => 'This QR code has expired'];
        } else {
            return ['valid' => false, 'message' => 'This QR code is not valid yet'];
        }
    }
    
    // Check time validity (if specified)
    if ($qrRecord['valid_time'] && $qrRecord['valid_time'] !== 'all_day') {
        // For simplicity, we'll allow attendance within 2 hours of the specified time
        $validTime = strtotime($qrRecord['valid_time']);
        $currentTimeStamp = strtotime($currentTime);
        $timeDiff = abs($currentTimeStamp - $validTime);
        
        // Allow 2 hours window (7200 seconds)
        if ($timeDiff > 7200) {
            return ['valid' => false, 'message' => 'QR code is only valid around ' . date('g:i A', $validTime)];
        }
    }
    
    return ['valid' => true, 'message' => 'Valid QR code'];
}
?>