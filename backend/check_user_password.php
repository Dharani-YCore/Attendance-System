<?php
// Check password for a specific user
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$email = 'anu@gmail.com';
$query = "SELECT id, name, email, password, is_first_login FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>User Found: " . $row['name'] . "</h2>";
    echo "<strong>Email:</strong> " . $row['email'] . "<br>";
    echo "<strong>ID:</strong> " . $row['id'] . "<br>";
    echo "<strong>Is First Login:</strong> " . ($row['is_first_login'] ? 'YES' : 'NO') . "<br>";
    echo "<strong>Password is NULL:</strong> " . ($row['password'] === null ? 'YES' : 'NO') . "<br>";
    
    if ($row['password'] !== null) {
        // Check if it's hashed or plain text
        if (strlen($row['password']) === 60 && substr($row['password'], 0, 4) === '$2y$') {
            echo "<strong>Password Type:</strong> Hashed (bcrypt)<br>";
            echo "<strong>Password Hash:</strong> " . substr($row['password'], 0, 30) . "...<br><br>";
            
            // Test common passwords
            $testPasswords = ['anu123', 'anu@123', 'password', '123456', 'anu'];
            echo "<h3>Testing Common Passwords:</h3>";
            foreach ($testPasswords as $testPwd) {
                if (password_verify($testPwd, $row['password'])) {
                    echo "<strong style='color: green;'>✅ Password '$testPwd' is CORRECT!</strong><br>";
                } else {
                    echo "❌ '$testPwd' is incorrect<br>";
                }
            }
        } else {
            echo "<strong>Password Type:</strong> Plain Text (NOT HASHED)<br>";
            echo "<strong style='color: red;'>⚠️ SECURITY WARNING: Password is stored as plain text!</strong><br>";
            echo "<strong>Plain Text Password:</strong> " . $row['password'] . "<br>";
        }
    }
} else {
    echo "<h2 style='color: red;'>❌ User not found with email: $email</h2>";
    
    // Show all users
    echo "<h3>All users in database:</h3>";
    $query = "SELECT id, name, email, is_first_login FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>First Login</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . ($row['is_first_login'] ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
}
?>
