<?php
// Simple script to reset password for testing
// DO NOT use in production!

include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Set a test password: "password123"
$email = 'abi@gmail.com';
$new_password = 'password123';
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update the user's password
$query = "UPDATE users SET password = ?, is_first_login = FALSE WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $password_hash);
$stmt->bindParam(2, $email);

if ($stmt->execute()) {
    if ($stmt->rowCount() > 0) {
        echo "✅ Password reset successfully!\n";
        echo "Email: " . $email . "\n";
        echo "New Password: " . $new_password . "\n";
        echo "\nYou can now login with these credentials.\n";
    } else {
        echo "❌ User not found: " . $email . "\n";
        echo "Please check if the user exists in the database.\n";
    }
} else {
    echo "❌ Error updating password.\n";
}
?>
