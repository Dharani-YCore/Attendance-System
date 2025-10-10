<?php
// --- CORS & CONTENT-TYPE HEADERS ---
// Allow requests from any origin. For production, you might want to restrict this to your app's domain.
header("Access-Control-Allow-Origin: *");
// Set content type to JSON
header("Content-Type: application/json; charset=UTF-8");
// Allow common HTTP methods.
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
// Set max age for pre-flight cache
header("Access-Control-Max-Age: 3600");
// Allow specific headers.
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
    // Check if user exists
    $query = "SELECT id, name, email, password FROM users WHERE email = ? LIMIT 0,1";
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
