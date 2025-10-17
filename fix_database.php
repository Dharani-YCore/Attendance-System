<?php
// PHP script to fix the missing is_first_login column
// Run this from browser: http://your-server/Attendance-System/fix_database.php

include_once 'backend/config/database.php';

echo "<h2>Attendance System Database Fix</h2>\n";
echo "<p>Fixing missing 'is_first_login' column...</p>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if column already exists
    $checkQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'users' 
                   AND COLUMN_NAME = 'is_first_login'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: green;'>✓ Column 'is_first_login' already exists!</p>\n";
    } else {
        // Add the missing column
        echo "<p>Adding missing 'is_first_login' column...</p>\n";
        $alterQuery = "ALTER TABLE users ADD COLUMN is_first_login BOOLEAN DEFAULT FALSE";
        $db->exec($alterQuery);
        echo "<p style='color: green;'>✓ Column 'is_first_login' added successfully!</p>\n";
        
        // Update existing users
        echo "<p>Updating existing users...</p>\n";
        $updateQuery = "UPDATE users SET is_first_login = FALSE WHERE is_first_login IS NULL";
        $db->exec($updateQuery);
        echo "<p style='color: green;'>✓ Existing users updated!</p>\n";
    }
    
    echo "<h3>Database Structure Verification:</h3>\n";
    echo "<p>Checking users table structure...</p>\n";
    
    $describeQuery = "DESCRIBE users";
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3 style='color: green;'>✅ Fix Complete!</h3>\n";
    echo "<p>The login issue should now be resolved. You can now:</p>\n";
    echo "<ul>\n";
    echo "<li>Login with your existing credentials</li>\n";
    echo "<li>Test the Flutter app login functionality</li>\n";
    echo "<li>Delete this fix_database.php file for security</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please make sure:</p>\n";
    echo "<ul>\n";
    echo "<li>MySQL server is running</li>\n";
    echo "<li>Database connection settings are correct in backend/config/database.php</li>\n";
    echo "<li>The 'users' table exists in your database</li>\n";
    echo "</ul>\n";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background-color: #f5f5f5; 
}
table { 
    background: white; 
    margin: 10px 0; 
}
th { 
    background-color: #007bff; 
    color: white; 
    padding: 8px; 
}
td { 
    padding: 5px; 
}
</style>