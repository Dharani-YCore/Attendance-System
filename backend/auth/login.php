<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // Check if user exists (include is_first_login field)
    $query = "SELECT id, name, email, password, is_first_login FROM users WHERE email = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if password is set (not null)
        if ($row['password'] === null) {
            echo json_encode(array(
                "success" => false,
                "message" => "Password not set. Please set your password first.",
                "action" => "set_password"
            ));
        } else if (password_verify($data->password, $row['password'])) {
            // Check if this is first-time login
            if ($row['is_first_login'] == 1) {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Please change your default password.",
                    "action" => "set_password"
                ));
            } else {
                // Generate JWT token
                $token = generateJWT($row['id'], $row['email']);
                
                echo json_encode(array(
                    "success" => true,
                    "message" => "Login successful.",
                    "token" => $token,
                    "user" => array(
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "email" => $row['email']
                    )
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid password."
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Email and password are required."
    ));
}
?>
