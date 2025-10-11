<?php
// Add is_first_login column to users table
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if column exists
$query = "SHOW COLUMNS FROM users LIKE 'is_first_login'";
$stmt = $db->prepare($query);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Add column
    $query = "ALTER TABLE users ADD COLUMN is_first_login BOOLEAN DEFAULT TRUE AFTER password";
    $db->exec($query);
    echo "<h2>✅ Column 'is_first_login' added successfully!</h2>";
    
    // Set existing users with passwords to is_first_login = TRUE
    $query = "UPDATE users SET is_first_login = TRUE WHERE password IS NOT NULL";
    $db->exec($query);
    echo "<p>All existing users marked as first-time login</p>";
} else {
    echo "<h2>ℹ️ Column 'is_first_login' already exists</h2>";
}

// Show current users
echo "<h3>Current Users:</h3>";
$query = "SELECT id, name, email, is_first_login FROM users";
$stmt = $db->prepare($query);
$stmt->execute();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>First Login</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>" . ($row['is_first_login'] ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
