<?php
include_once '../cors.php';
include_once '../config/database.php';

// Set timezone to India Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

$database = new Database();
$db = $database->getConnection();

// Validate JWT token
$user = validateRequest();

// Get parameters
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Today

if (!empty($user_id)) {
    // Get detailed attendance report with check-in/check-out times
    $query = "SELECT 
                a.date,
                a.time,
                a.check_in_time,
                a.check_out_time,
                a.total_hours,
                a.status,
                a.attendance_type,
                r.morning_time,
                r.evening_time,
                r.total_working_hours
              FROM attendance a
              LEFT JOIN reports r ON a.user_id = r.user_id AND a.date = r.report_date
              WHERE a.user_id = ? AND a.date BETWEEN ? AND ?
              ORDER BY a.date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $start_date);
    $stmt->bindParam(3, $end_date);
    $stmt->execute();
    
    $report_data = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate total hours if not already calculated
        $totalHours = $row['total_hours'];
        if (!$totalHours && $row['check_in_time'] && $row['check_out_time']) {
            try {
                $checkIn = new DateTime($row['check_in_time']);
                $checkOut = new DateTime($row['check_out_time']);
                $interval = $checkIn->diff($checkOut);
                $totalHours = round($interval->h + ($interval->i / 60), 2);
            } catch (Exception $e) {
                $totalHours = null;
            }
        }
        
        $report_data[] = array(
            "date" => $row['date'],
            "time" => $row['time'], // Keep for backward compatibility
            "check_in_time" => $row['check_in_time'],
            "check_out_time" => $row['check_out_time'],
            "total_hours" => $totalHours,
            "status" => $row['status'],
            "attendance_type" => $row['attendance_type'],
            "morning_time" => $row['morning_time'], // From reports table
            "evening_time" => $row['evening_time'], // From reports table
            "total_working_hours" => $row['total_working_hours'] // From reports table
        );
    }
    
    // Get summary for the period
    $query = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) as leave_days
              FROM attendance 
              WHERE user_id = ? AND date BETWEEN ? AND ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $start_date);
    $stmt->bindParam(3, $end_date);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convert strings to integers
    $total_days = (int)($summary['total_days'] ?? 0);
    $present_days = (int)($summary['present_days'] ?? 0);
    $late_days = (int)($summary['late_days'] ?? 0);
    $absent_days = (int)($summary['absent_days'] ?? 0);
    $leave_days = (int)($summary['leave_days'] ?? 0);
    
    // Calculate attendance percentage
    $total_working_days = $total_days;
    $present_count = $present_days + $late_days; // Late is still considered present
    $attendance_percentage = $total_working_days > 0 ? round(($present_count / $total_working_days) * 100, 2) : 0.0;
    
    echo json_encode(array(
        "success" => true,
        "data" => $report_data,
        "summary" => array(
            "total_days" => $total_days,
            "present_days" => $present_days,
            "late_days" => $late_days,
            "absent_days" => $absent_days,
            "leave_days" => $leave_days,
            "attendance_percentage" => $attendance_percentage
        ),
        "period" => array(
            "start_date" => $start_date,
            "end_date" => $end_date
        )
    ));
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "User ID is required."
    ));
}
?>
