<?php
/**
 * Manual Holiday Management
 * Add, update, or delete holidays manually for countries not supported by API
 */

header('Content-Type: application/json');
include_once '../cors.php';
include_once '../config/database.php';

// Optional: Add authentication
// $user = validateRequest();

$database = new Database();
$db = $database->getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Add new holiday
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['holiday_date', 'holiday_name', 'country_code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Missing required field: {$field}"
                ]);
                exit();
            }
        }
        
        $holiday_date = $data['holiday_date'];
        $holiday_name = $data['holiday_name'];
        $holiday_type = $data['holiday_type'] ?? 'National';
        $country_code = strtoupper($data['country_code']);
        $description = $data['description'] ?? 'Manually added';
        
        // Validate date format
        $dateCheck = DateTime::createFromFormat('Y-m-d', $holiday_date);
        if (!$dateCheck || $dateCheck->format('Y-m-d') !== $holiday_date) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid date format. Use YYYY-MM-DD"
            ]);
            exit();
        }
        
        // Validate holiday type
        if (!in_array($holiday_type, ['National', 'Regional', 'Festival'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid holiday type. Must be: National, Regional, or Festival"
            ]);
            exit();
        }
        
        // Insert holiday
        $query = "INSERT INTO holidays (holiday_date, holiday_name, holiday_type, country_code, description) 
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    holiday_name = VALUES(holiday_name),
                    holiday_type = VALUES(holiday_type),
                    description = VALUES(description),
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $holiday_date);
        $stmt->bindParam(2, $holiday_name);
        $stmt->bindParam(3, $holiday_type);
        $stmt->bindParam(4, $country_code);
        $stmt->bindParam(5, $description);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Holiday added successfully",
                "data" => [
                    "holiday_date" => $holiday_date,
                    "holiday_name" => $holiday_name,
                    "holiday_type" => $holiday_type,
                    "country_code" => $country_code
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to add holiday"
            ]);
        }
        break;
        
    case 'PUT':
        // Update existing holiday
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Missing holiday ID"
            ]);
            exit();
        }
        
        $id = (int)$data['id'];
        $updates = [];
        $params = [];
        
        if (!empty($data['holiday_name'])) {
            $updates[] = "holiday_name = ?";
            $params[] = $data['holiday_name'];
        }
        
        if (!empty($data['holiday_type'])) {
            if (!in_array($data['holiday_type'], ['National', 'Regional', 'Festival'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid holiday type"
                ]);
                exit();
            }
            $updates[] = "holiday_type = ?";
            $params[] = $data['holiday_type'];
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $data['is_active'] ? 1 : 0;
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "No fields to update"
            ]);
            exit();
        }
        
        $params[] = $id;
        $query = "UPDATE holidays SET " . implode(", ", $updates) . " WHERE id = ?";
        
        $stmt = $db->prepare($query);
        if ($stmt->execute($params)) {
            echo json_encode([
                "success" => true,
                "message" => "Holiday updated successfully"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to update holiday"
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete holiday
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Missing holiday ID"
            ]);
            exit();
        }
        
        $id = (int)$data['id'];
        
        // Soft delete by setting is_active to false
        $query = "UPDATE holidays SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Holiday deleted successfully"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete holiday"
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method not allowed. Use POST to add, PUT to update, DELETE to remove"
        ]);
        break;
}
?>
