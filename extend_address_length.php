<?php
/**
 * Extend address column length to support more detailed addresses
 */

require_once 'backend/config/database.php';

echo "=== Extending Address Column Length ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Connected to database successfully.\n\n";
    
    // Extend check_in_address column
    echo "Extending check_in_address column to 1000 characters...\n";
    $sql1 = "ALTER TABLE attendance MODIFY COLUMN check_in_address VARCHAR(1000) DEFAULT NULL";
    $db->exec($sql1);
    echo "✓ Extended check_in_address column\n";
    
    // Extend check_out_address column
    echo "Extending check_out_address column to 1000 characters...\n";
    $sql2 = "ALTER TABLE attendance MODIFY COLUMN check_out_address VARCHAR(1000) DEFAULT NULL";
    $db->exec($sql2);
    echo "✓ Extended check_out_address column\n";
    
    echo "\n=== Column extension completed successfully! ===\n";
    echo "The system can now store detailed addresses up to 1000 characters.\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
