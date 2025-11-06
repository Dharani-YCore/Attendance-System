<?php
include_once '../cors.php';
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if all required fields are provided
if (!empty($data->email) && !empty($data->old_password) && !empty($data->new_password) && !empty($data->confirm_password)) {
    
    // Check if new password and confirm password match
    if ($data->new_password !== $data->confirm_password) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "New password and confirm password do not match."
        ));
        exit();
    }
    
    // Check if new password is different from old password
    if ($data->old_password === $data->new_password) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "New password must be different from old password."
        ));
        exit();
    }
    
    // Resolve CRM table/column mappings from environment
    $userTable = env('USER_TABLE', 'users');
    $idCol = env('USER_ID_COL', 'id');
    $emailCol = env('USER_EMAIL_COL', 'email');
    $passwordCol = env('USER_PASSWORD_COL', 'password');
    $isFirstLoginCol = env('USER_IS_FIRST_LOGIN_COL', 'is_first_login');

    $nameCol = env('USER_NAME_COL', '');
    $firstNameCol = env('USER_FIRST_NAME_COL', '');
    $lastNameCol = env('USER_LAST_NAME_COL', '');

    // Name selection: prefer CONCAT(first,last) if provided; else single name column; else fallback to email
    if (!empty($firstNameCol) && !empty($lastNameCol)) {
        $nameSelect = "CONCAT(TRIM($firstNameCol), ' ', TRIM($lastNameCol)) AS name";
    } elseif (!empty($nameCol)) {
        $nameSelect = "$nameCol AS name";
    } else {
        $nameSelect = "$emailCol AS name";
    }

    // Check if user exists and get current password
    $selectCols = "$idCol AS id, $emailCol AS email, $passwordCol AS password, $nameSelect";
    if (!empty($isFirstLoginCol)) {
        $selectCols .= ", $isFirstLoginCol AS is_first_login";
    }
    $query = "SELECT $selectCols FROM $userTable WHERE $emailCol = ? LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify old password
        // Handle both hashed passwords and plain text (for first-time users)
        $passwordMatch = false;
        
        if ($row['password'] === null) {
            // Password is NULL - reject with clear message
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "message" => "No password set. Please contact administrator."
            ));
            exit();
        } else if (password_verify($data->old_password, $row['password'])) {
            // Hashed password matched
            $passwordMatch = true;
        } else if ($data->old_password === $row['password']) {
            // Plain text password matched (for first-time login users)
            $passwordMatch = true;
        }
        
        if (!$passwordMatch) {
            http_response_code(401);
            echo json_encode(array(
                "success" => false,
                "message" => "Old password is incorrect."
            ));
            exit();
        }
        
        // Hash new password
        $new_password_hash = password_hash($data->new_password, PASSWORD_BCRYPT);
        
        // Update password in database and clear first-login flag if column exists
        if (!empty($isFirstLoginCol)) {
            $query = "UPDATE $userTable SET $passwordCol = ?, $isFirstLoginCol = 0 WHERE $emailCol = ?";
        } else {
            $query = "UPDATE $userTable SET $passwordCol = ? WHERE $emailCol = ?";
        }
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $new_password_hash);
        $stmt->bindParam(2, $data->email);
        
        if ($stmt->execute()) {
            // Generate JWT token for automatic login after password change
            $token = generateJWT($row['id'], $row['email']);
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Password changed successfully. You can now login with your new password.",
                "token" => $token,
                "user" => array(
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "email" => $row['email']
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Unable to update password. Please try again."
            ));
        }
    } else {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Email, old password, new password, and confirm password are required."
    ));
}
?>
