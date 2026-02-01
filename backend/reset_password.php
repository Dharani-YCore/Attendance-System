<?php
// Reset password for abi@gmail.com
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$email = 'abi@gmail.com';
$password = 'abi123';
$hashed = password_hash($password, PASSWORD_BCRYPT);

$query = "UPDATE users SET password = ? WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $hashed);
$stmt->bindParam(2, $email);

if ($stmt->execute()) {
    echo "<h2>✅ Password Updated Successfully!</h2>";
    echo "Email: $email<br>";
    echo "New Password: $password<br>";
    echo "Hash: " . substr($hashed, 0, 30) . "...<br><br>";
    
    // Verify it works
    $verify_query = "SELECT password FROM users WHERE email = ?";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(1, $email);
    $verify_stmt->execute();
    $row = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($password, $row['password'])) {
        echo "<strong style='color:green'>✅ Verification Successful - Password is correct!</strong>";
    } else {
        echo "<strong style='color:red'>❌ Verification Failed</strong>";
    }
} else {
    echo "<h2>❌ Error updating password</h2>";
}
?>
