<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
$user = validateRequest();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->status)) {
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Check if attendance already marked for today
    $query = "SELECT id FROM attendance WHERE user_id = ? AND date = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->user_id);
    $stmt->bindParam(2, $today);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Attendance already marked for today."
        ));
    } else {
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
        
        // Insert attendance record
        $query = "INSERT INTO attendance SET user_id=:user_id, date=:date, time=:time, status=:status";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":user_id", $data->user_id);
        $stmt->bindParam(":date", $today);
        $stmt->bindParam(":time", $current_time);
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
                "message" => "Attendance marked successfully.",
                "status" => $status,
                "time" => $current_time
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to mark attendance."
            ));
        }
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID and status are required."
    ));
}
?>
