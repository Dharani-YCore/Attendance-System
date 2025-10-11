<?php
// Test login credentials
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$email = 'abi@gmail.com';
$query = "SELECT id, name, email, password FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>User Found:</h2>";
    echo "ID: " . $row['id'] . "<br>";
    echo "Name: " . $row['name'] . "<br>";
    echo "Email: " . $row['email'] . "<br>";
    echo "Password Hash: " . substr($row['password'], 0, 30) . "...<br>";
    echo "Password is null: " . ($row['password'] === null ? 'YES' : 'NO') . "<br><br>";
    
    // Test password
    $test_password = 'abi123';
    if ($row['password'] === null) {
        echo "<strong>❌ Password is NULL - user needs to set password</strong><br>";
    } else if (password_verify($test_password, $row['password'])) {
        echo "<strong>✅ Password 'abi123' is CORRECT</strong><br>";
    } else {
        echo "<strong>❌ Password 'abi123' is INCORRECT</strong><br>";
        echo "Try hashing a new password:<br>";
        echo "New hash for 'abi123': " . password_hash($test_password, PASSWORD_BCRYPT);
    }
} else {
    echo "<h2>❌ User not found with email: $email</h2>";
}
?>
