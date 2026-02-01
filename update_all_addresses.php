<?php
/**
 * Comprehensive script to update all attendance records with formatted addresses
 * Uses multiple geocoding services for better reliability
 */

require_once 'backend/config/database.php';

echo "=== Updating All Attendance Records with Addresses ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Connected to database successfully.\n\n";
    
    // Find records with coordinates but missing addresses
    $query = "SELECT id, check_in_latitude, check_in_longitude, check_in_address, 
                     check_out_latitude, check_out_longitude, check_out_address 
              FROM attendance 
              WHERE (check_in_latitude IS NOT NULL AND check_in_longitude IS NOT NULL AND (check_in_address IS NULL OR check_in_address = '')) 
                 OR (check_out_latitude IS NOT NULL AND check_out_longitude IS NOT NULL AND (check_out_address IS NULL OR check_out_address = ''))
              ORDER BY id DESC
              LIMIT 100"; // Process in batches
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalRecords = count($records);
    echo "Found $totalRecords records to update.\n\n";
    
    if ($totalRecords === 0) {
        echo "✓ All records already have addresses!\n";
        exit(0);
    }
    
    $updated = 0;
    $failed = 0;
    
    foreach ($records as $record) {
        $id = $record['id'];
        $updates = [];
        $params = [];
        
        // Process check-in address
        if ($record['check_in_latitude'] !== null && $record['check_in_longitude'] !== null 
            && ($record['check_in_address'] === null || $record['check_in_address'] === '')) {
            echo "Processing record #$id check-in location... ";
            
            $address = geocodeLocation(
                $record['check_in_latitude'], 
                $record['check_in_longitude']
            );
            
            if ($address) {
                $updates[] = "check_in_address = ?";
                $params[] = $address;
                echo "✓ Address: " . substr($address, 0, 60) . (strlen($address) > 60 ? "..." : "") . "\n";
            } else {
                // Fallback to simple formatted coordinates
                $address = sprintf("%.6f, %.6f", $record['check_in_latitude'], $record['check_in_longitude']);
                $updates[] = "check_in_address = ?";
                $params[] = $address;
                echo "⚠ Using coordinates as fallback\n";
                $failed++;
            }
            
            // Rate limiting - respect API limits
            sleep(1);
        }
        
        // Process check-out address
        if ($record['check_out_latitude'] !== null && $record['check_out_longitude'] !== null 
            && ($record['check_out_address'] === null || $record['check_out_address'] === '')) {
            echo "Processing record #$id check-out location... ";
            
            $address = geocodeLocation(
                $record['check_out_latitude'], 
                $record['check_out_longitude']
            );
            
            if ($address) {
                $updates[] = "check_out_address = ?";
                $params[] = $address;
                echo "✓ Address: " . substr($address, 0, 60) . (strlen($address) > 60 ? "..." : "") . "\n";
            } else {
                // Fallback to simple formatted coordinates
                $address = sprintf("%.6f, %.6f", $record['check_out_latitude'], $record['check_out_longitude']);
                $updates[] = "check_out_address = ?";
                $params[] = $address;
                echo "⚠ Using coordinates as fallback\n";
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
    if ($failed > 0) {
        echo "Records with geocoding issues: $failed (using coordinates as fallback)\n";
    }
    echo "\nRun this script again if you have more records to update.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Geocode location using multiple services with fallback
 */
function geocodeLocation($lat, $lon) {
    // Try Nominatim first (OpenStreetMap)
    $address = geocodeNominatim($lat, $lon);
    if ($address) {
        return $address;
    }
    
    // Try Google Maps Geocoding API (if you have an API key)
    // Uncomment and add your API key if you want to use Google Maps
    // $address = geocodeGoogle($lat, $lon, 'YOUR_API_KEY_HERE');
    // if ($address) {
    //     return $address;
    // }
    
    // Try alternative services
    $address = geocodeBigDataCloud($lat, $lon);
    if ($address) {
        return $address;
    }
    
    return null;
}

/**
 * Geocode using OpenStreetMap Nominatim
 */
function geocodeNominatim($lat, $lon) {
    try {
        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?lat=%s&lon=%s&format=json&addressdetails=1',
            $lat,
            $lon
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AttendanceSystem/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Nominatim cURL error: $error");
            return null;
        }
        
        if ($httpCode !== 200 || !$response) {
            error_log("Nominatim HTTP error: $httpCode");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['address'])) {
            return null;
        }
        
        return formatAddress($data['address'], $data['display_name'] ?? null);
        
    } catch (Exception $e) {
        error_log("Nominatim error: " . $e->getMessage());
        return null;
    }
}

/**
 * Geocode using BigDataCloud (free, no API key required)
 */
function geocodeBigDataCloud($lat, $lon) {
    try {
        $url = sprintf(
            'https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=%s&longitude=%s&localityLanguage=en',
            $lat,
            $lon
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            return null;
        }
        
        // Format address from BigDataCloud response
        $parts = [];
        if (isset($data['locality']) && $data['locality']) {
            $parts[] = $data['locality'];
        }
        if (isset($data['city']) && $data['city']) {
            $parts[] = $data['city'];
        }
        if (isset($data['principalSubdivision']) && $data['principalSubdivision']) {
            $parts[] = $data['principalSubdivision'];
        }
        if (isset($data['countryName']) && $data['countryName'] && $data['countryName'] !== 'India') {
            $parts[] = $data['countryName'];
        }
        
        if (!empty($parts)) {
            return implode(', ', array_unique($parts));
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("BigDataCloud error: " . $e->getMessage());
        return null;
    }
}

/**
 * Geocode using Google Maps (requires API key)
 */
function geocodeGoogle($lat, $lon, $apiKey) {
    if (empty($apiKey)) {
        return null;
    }
    
    try {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=%s',
            $lat,
            $lon,
            $apiKey
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['results'][0])) {
            return null;
        }
        
        // Get the formatted address from first result
        return $data['results'][0]['formatted_address'] ?? null;
        
    } catch (Exception $e) {
        error_log("Google Maps error: " . $e->getMessage());
        return null;
    }
}

/**
 * Format address from components
 */
function formatAddress($address, $displayName = null) {
    $parts = [];
    
    // Road/Street
    $road = $address['road'] ?? $address['street'] ?? null;
    if ($road) {
        $parts[] = $road;
    }
    
    // Suburb/Neighbourhood
    $suburb = $address['suburb'] ?? $address['neighbourhood'] ?? $address['hamlet'] ?? null;
    if ($suburb) {
        $parts[] = $suburb;
    }
    
    // City/Town/Village
    $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
    if ($city) {
        $parts[] = $city;
    }
    
    // State
    if (isset($address['state'])) {
        $parts[] = $address['state'];
    }
    
    // Postal code
    if (isset($address['postcode'])) {
        $parts[] = $address['postcode'];
    }
    
    if (!empty($parts)) {
        return implode(', ', $parts);
    }
    
    // Fallback to display name
    if ($displayName) {
        $displayParts = explode(',', $displayName);
        // Remove country if it's India
        if (count($displayParts) > 1 && trim(end($displayParts)) === 'India') {
            array_pop($displayParts);
        }
        $relevantParts = array_slice($displayParts, 0, 5);
        return implode(', ', array_map('trim', $relevantParts));
    }
    
    return null;
}
?>
