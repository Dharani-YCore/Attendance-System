<?php
include_once '../cors.php';
include_once '../config/database.php';

// Set timezone to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
try {
    $user = validateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Authentication failed. Please login again.",
        "error_code" => "AUTH_FAILED"
    ));
    exit;
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->status)) {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // If QR data is provided, validate it first
    if (isset($data->qr_data)) {
        $qrValidation = validateQRCode($data->qr_data, $db);
        if (!$qrValidation['valid']) {
            echo json_encode(array(
                "success" => false,
                "message" => $qrValidation['message']
            ));
            exit;
        }
        
        // Log QR scan
        logQRScan($data->user_id, $qrValidation['qr_info'], $db);
    }
    
    // Check today's attendance status
    $query = "SELECT id, check_in_time, check_out_time, attendance_type, status FROM attendance WHERE user_id = ? AND date = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->user_id);
    $stmt->bindParam(2, $today);
    $stmt->execute();
    
    $existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAttendance) {
        // Check if this is a check-out attempt
        if ($existingAttendance['check_out_time'] === null) {
            // This is a check-out scan
            $checkOutTime = $current_time;
            $checkInTime = $existingAttendance['check_in_time'];
            
            // Calculate total hours
            $totalHours = calculateTotalHours($checkInTime, $checkOutTime);
            
            // Update attendance record with check-out time
            $query = "UPDATE attendance SET check_out_time = ?, total_hours = ?, attendance_type = 'full_day' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $checkOutTime);
            $stmt->bindParam(2, $totalHours);
            $stmt->bindParam(3, $existingAttendance['id']);
            
            if ($stmt->execute()) {
                // Update reports table with check-out time (if columns exist)
                try {
                    $query = "UPDATE reports SET evening_time = ?, total_working_hours = ? WHERE user_id = ? AND report_date = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $checkOutTime);
                    $stmt->bindParam(2, $totalHours);
                    $stmt->bindParam(3, $data->user_id);
                    $stmt->bindParam(4, $today);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // If columns don't exist, just continue (migration needed)
                    if (strpos($e->getMessage(), 'Unknown column') === false) {
                        throw $e;
                    }
                }
                
                echo json_encode(array(
                    "success" => true,
                    "message" => "Check-out successful.",
                    "action" => "check_out",
                    "check_in_time" => $checkInTime,
                    "check_out_time" => $checkOutTime,
                    "total_hours" => $totalHours
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unable to record check-out."
                ));
            }
        } else {
            // Both check-in and check-out already done
            echo json_encode(array(
                "success" => false,
                "message" => "Attendance already completed for today. Check-in: " . $existingAttendance['check_in_time'] . ", Check-out: " . $existingAttendance['check_out_time']
            ));
        }
    } else {
        // This is a check-in scan
        // Determine status based on time
        $status = $data->status;
        $hour = (int)date('H');
        
        // Business logic for attendance status
        if ($hour >= 9 && $hour < 10) {
            $status = 'Present';
        } elseif ($hour >= 10 && $hour < 12) {
            $status = 'Late';
        } else {
            $status = $data->status; // Use provided status
        }
        
        // Insert check-in attendance record
        $query = "INSERT INTO attendance SET user_id=:user_id, date=:date, time=:time, check_in_time=:check_in_time, status=:status, attendance_type='check_in'";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":user_id", $data->user_id);
        $stmt->bindParam(":date", $today);
        $stmt->bindParam(":time", $current_time);
        $stmt->bindParam(":check_in_time", $current_time);
        $stmt->bindParam(":status", $status);
        
        if ($stmt->execute()) {
            // Also update or insert into reports table
            $query = "INSERT INTO reports (user_id, report_date, morning_time) VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE morning_time = VALUES(morning_time)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->user_id);
            $stmt->bindParam(2, $today);
            $stmt->bindParam(3, $current_time);
            $stmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "Check-in successful.",
                "action" => "check_in",
                "status" => $status,
                "check_in_time" => $current_time
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to record check-in."
            ));
        }
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID and status are required."
    ));
}

function validateQRCode($qrData, $db) {
    try {
        $qrInfo = json_decode($qrData, true);
        
        if (!$qrInfo) {
            return ['valid' => false, 'message' => 'Invalid QR code format'];
        }
        
        // If env restrictions are configured, only accept QR that matches them
        $allowedQrId = env('ALLOWED_QR_ID', null);
        $allowedQrData = env('ALLOWED_QR_DATA', null);
        $allowedQrHash = env('ALLOWED_QR_HASH', null);

        if ($allowedQrData || $allowedQrHash || $allowedQrId) {
            $raw = $qrData;
            $rawHash = hash('sha256', $raw);

            // Exact string/hash/id match checks
            $matchesData = $allowedQrData ? hash_equals($allowedQrData, $raw) : false;
            $matchesHash = $allowedQrHash ? hash_equals($allowedQrHash, $rawHash) : false;
            $matchesId = $allowedQrId ? (isset($qrInfo['id']) && hash_equals($allowedQrId, strval($qrInfo['id']))) : false;

            // Subset JSON match: if ALLOWED_QR_DATA is JSON and provided data is JSON, ensure all keys in allowed are equal in provided
            $matchesSubset = false;
            if (!$matchesData && $allowedQrData) {
                $allowedJson = json_decode($allowedQrData, true);
                if (is_array($allowedJson) && is_array($qrInfo)) {
                    $allMatch = true;
                    foreach ($allowedJson as $k => $v) {
                        if (!array_key_exists($k, $qrInfo) || strval($qrInfo[$k]) !== strval($v)) {
                            $allMatch = false;
                            break;
                        }
                    }
                    $matchesSubset = $allMatch;
                }
            }

            if (!($matchesData || $matchesHash || $matchesId || $matchesSubset)) {
                return ['valid' => false, 'message' => 'This QR code is not authorized for attendance'];
            }

            // If we have an env-authorized match, treat as valid immediately (no DB/id requirement)
            // Construct a simple qr_info payload for logging
            $location = $qrInfo['location'] ?? 'UNKNOWN';
            return [
                'valid' => true,
                'message' => 'Valid authorized QR code',
                'qr_info' => [
                    'qr_id' => 'STATIC_' . strtoupper(str_replace(' ', '_', $location)),
                    'qr_type' => $qrInfo['qr_type'] ?? ($qrInfo['type'] ?? 'static'),
                    'location' => $location,
                    'valid_date' => date('Y-m-d'),
                    'valid_time' => 'all_day',
                    'is_active' => 1
                ]
            ];
        }
        
        // Check if QR code exists in database (for non-static QR codes)
        $qr_id = isset($qrInfo['id']) ? $qrInfo['id'] : null;
        if (!$qr_id) {
            return ['valid' => false, 'message' => 'QR code missing ID field'];
        }
        
        $query = "SELECT * FROM qr_codes WHERE qr_id = ? AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $qr_id);
        $stmt->execute();
        
        $qrRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$qrRecord) {
            return ['valid' => false, 'message' => 'QR code not found or inactive'];
        }
        
        // Validate date
        $today = date('Y-m-d');
        if ($qrRecord['valid_date'] !== $today) {
            if ($qrRecord['valid_date'] < $today) {
                return ['valid' => false, 'message' => 'QR code has expired'];
            } else {
                return ['valid' => false, 'message' => 'QR code is not valid yet'];
            }
        }
        
        return [
            'valid' => true, 
            'message' => 'Valid QR code',
            'qr_info' => $qrRecord
        ];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'QR validation error: ' . $e->getMessage()];
    }
}

function calculateTotalHours($checkInTime, $checkOutTime) {
    try {
        $checkIn = new DateTime($checkInTime);
        $checkOut = new DateTime($checkOutTime);
        
        // Calculate the difference in hours
        $interval = $checkIn->diff($checkOut);
        $totalHours = $interval->h + ($interval->i / 60);
        
        // Round to 2 decimal places
        return round($totalHours, 2);
    } catch (Exception $e) {
        error_log('[TIME CALC ERROR] Failed to calculate total hours: ' . $e->getMessage());
        return 0.00;
    }
}

function logQRScan($userId, $qrInfo, $db) {
    try {
        $query = "INSERT INTO qr_attendance_logs 
                  (user_id, qr_id, attendance_status, location_scanned, ip_address) 
                  VALUES (?, ?, 'Present', ?, ?)";
        
        $stmt = $db->prepare($query);
        
        // Extract values to variables since bindParam requires references
        $qrId = $qrInfo['qr_id'];
        $location = $qrInfo['location'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt->bindParam(1, $userId);
        $stmt->bindParam(2, $qrId);
        $stmt->bindParam(3, $location);
        $stmt->bindParam(4, $ipAddress);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log('[QR LOG ERROR] Failed to log QR scan: ' . $e->getMessage());
        return false;
    }
}

?>
