<?php
/**
 * Migration script to add address columns to attendance table
 * Run this once to update the database schema
 */

require_once 'backend/config/database.php';

echo "=== Attendance System Address Migration ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Connected to database successfully.\n\n";
    
    // Check if columns already exist
    $checkQuery = "SHOW COLUMNS FROM attendance LIKE 'check_in_address'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        echo "✓ Address columns already exist. No migration needed.\n";
        exit(0);
    }
    
    echo "Adding address columns to attendance table...\n";
    
    // Add check_in_address column
    $sql1 = "ALTER TABLE attendance 
             ADD COLUMN check_in_address VARCHAR(500) DEFAULT NULL AFTER check_in_longitude";
    $db->exec($sql1);
    echo "✓ Added check_in_address column\n";
    
    // Add check_out_address column
    $sql2 = "ALTER TABLE attendance 
             ADD COLUMN check_out_address VARCHAR(500) DEFAULT NULL AFTER check_out_longitude";
    $db->exec($sql2);
    echo "✓ Added check_out_address column\n";
    
    // Add indexes for better performance
    try {
        $sql3 = "CREATE INDEX idx_attendance_check_in_address ON attendance(check_in_address(100))";
        $db->exec($sql3);
        echo "✓ Added index on check_in_address\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "  Index on check_in_address already exists\n";
    }
    
    try {
        $sql4 = "CREATE INDEX idx_attendance_check_out_address ON attendance(check_out_address(100))";
        $db->exec($sql4);
        echo "✓ Added index on check_out_address\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "  Index on check_out_address already exists\n";
    }
    
    echo "\n=== Migration completed successfully! ===\n";
    echo "\nNext steps:\n";
    echo "1. The system will now automatically fetch addresses for new attendance records\n";
    echo "2. Old records will show coordinates until they are updated\n";
    echo "3. You can delete this migration file after running it\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
