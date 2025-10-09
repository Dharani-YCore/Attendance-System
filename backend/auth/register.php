<?php
include_once '../config/database.php';
include_once '../services/email_service.php';

$database = new Database();
$db = $database->getConnection();
$emailService = new EmailService();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    // Check if user already exists
    $query = "SELECT id FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "User with this email already exists."
        ));
    } else {
        // Hash password
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users SET name=:name, email=:email, password=:password";
        $stmt = $db->prepare($query);
        
        // Bind values
        $stmt->bindParam(":name", $data->name);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":password", $password_hash);
        
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            
            // Send welcome email
            $emailService->sendWelcomeEmail($data->email, $data->name);
            
            // Generate JWT token
            $token = generateJWT($user_id, $data->email);
            
            echo json_encode(array(
                "success" => true,
                "message" => "User registered successfully.",
                "token" => $token,
                "user" => array(
                    "id" => $user_id,
                    "name" => $data->name,
                    "email" => $data->email
                )
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to register user."
            ));
        }
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Name, email and password are required."
    ));
}
?>
