<?php


// Handle pre-flight requests (OPTIONS method)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- END OF HEADERS ---

// Include database and user object
include_once '../config/database.php';
include_once '../objects/user.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if (!empty($data->email) && !empty($data->password)) {
    $query = "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 0,1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $id = $row['id'];
        $name = $row['name'];
        $email = $row['email'];
        $password_hash = $row['password'];
        
        if (password_verify($data->password, $password_hash)) {
            $token = generateJWT($id, $email);
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Login successful.",
                "token" => $token,
                "user" => array(
                    "id" => $id,
                    "name" => $name,
                    "email" => $email
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "message" => "Invalid password."
            ));
        }
    } else {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "No user exists with this email ID."
        ));
    }
} else {
    http_response_code(400);
   echo json_encode(array(
        "success" => false,
        "message" => "Email and password are required."
    ));
}
?>
