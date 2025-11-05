<?php
/**
 * Script to fetch and update addresses for existing attendance records
 * that only have coordinates but no address
 */

require_once 'backend/config/database.php';
require_once 'backend/services/geocoding_service.php';

echo "=== Updating Existing Attendance Records with Addresses ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    $geocoder = new GeocodingService();
    
    echo "Connected to database successfully.\n\n";
    
    // Find records with coordinates (update all to add Plus Codes)
    $query = "SELECT id, check_in_latitude, check_in_longitude, check_out_latitude, check_out_longitude 
              FROM attendance 
              WHERE check_in_latitude IS NOT NULL OR check_out_latitude IS NOT NULL
              LIMIT 100"; // Process in batches
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalRecords = count($records);
    echo "Found $totalRecords records to update.\n\n";
    
    if ($totalRecords === 0) {
        echo "All records are up to date!\n";
        exit(0);
    }
    
    $updated = 0;
    $failed = 0;
    
    foreach ($records as $record) {
        $id = $record['id'];
        $updates = [];
        $params = [];
        
        // Process check-in address with detailed format
        if ($record['check_in_latitude'] !== null && $record['check_in_longitude'] !== null) {
            echo "Processing record #$id check-in location... ";
            $address = $geocoder->reverseGeocode(
                $record['check_in_latitude'], 
                $record['check_in_longitude']
            );
            
            if ($address) {
                $updates[] = "check_in_address = ?";
                $params[] = $address;
                echo "✓ Got detailed address: " . substr($address, 0, 80) . (strlen($address) > 80 ? "..." : "") . "\n";
            } else {
                echo "✗ Failed to get address\n";
                $failed++;
            }
            
            // Rate limiting - 1 request per second
            sleep(1);
        }
        
        // Process check-out address with detailed format
        if ($record['check_out_latitude'] !== null && $record['check_out_longitude'] !== null) {
            echo "Processing record #$id check-out location... ";
            $address = $geocoder->reverseGeocode(
                $record['check_out_latitude'], 
                $record['check_out_longitude']
            );
            
            if ($address) {
                $updates[] = "check_out_address = ?";
                $params[] = $address;
                echo "✓ Got detailed address: " . substr($address, 0, 80) . (strlen($address) > 80 ? "..." : "") . "\n";
            } else {
                echo "✗ Failed to get address\n";
                $failed++;
            }
            
            // Rate limiting
            sleep(1);
        }
        
        // Update the record if we have addresses
        if (!empty($updates)) {
            $updateQuery = "UPDATE attendance SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $id;
            
            $updateStmt = $db->prepare($updateQuery);
            foreach ($params as $i => $param) {
                $updateStmt->bindValue($i + 1, $param);
            }
            
            if ($updateStmt->execute()) {
                $updated++;
            }
        }
    }
    
    echo "\n=== Update Complete ===\n";
    echo "Successfully updated: $updated records\n";
    echo "Failed: $failed records\n";
    echo "\nNote: Due to rate limiting (1 request/second), this process may take time.\n";
    echo "Run this script again if you have more records to update.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
