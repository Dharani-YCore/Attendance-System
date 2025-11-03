<?php
/**
 * Geocoding Service
 * Converts GPS coordinates to human-readable addresses using OpenStreetMap Nominatim API
 */

class GeocodingService {
    private $baseUrl = 'https://nominatim.openstreetmap.org/reverse';
    private $userAgent = 'AttendanceSystem/1.0';
    
    /**
     * Convert latitude and longitude to a human-readable address
     * 
     * @param float $latitude
     * @param float $longitude
     * @return string|null Returns formatted address or null on failure
     */
    public function reverseGeocode($latitude, $longitude) {
        if ($latitude === null || $longitude === null) {
            return null;
        }
        
        try {
            // Build URL with parameters
            $url = sprintf(
                '%s?lat=%s&lon=%s&format=json&addressdetails=1',
                $this->baseUrl,
                $latitude,
                $longitude
            );
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                error_log("Geocoding API error: HTTP $httpCode");
                return null;
            }
            
            // Parse response
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['address'])) {
                return null;
            }
            
            // Format address from components
            return $this->formatAddress($data['address'], $data['display_name'] ?? null);
            
        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Format address from Nominatim response
     * 
     * @param array $address Address components
     * @param string|null $displayName Full display name as fallback
     * @return string Formatted address
     */
    private function formatAddress($address, $displayName = null) {
        $parts = [];
        
        // Building number and street
        if (isset($address['house_number'])) {
            $parts[] = $address['house_number'];
        }
        
        // Road/Street
        $road = $address['road'] ?? $address['street'] ?? $address['pedestrian'] ?? null;
        if ($road) {
            $parts[] = $road;
        }
        
        // Neighborhood or suburb
        $area = $address['neighbourhood'] ?? $address['suburb'] ?? $address['quarter'] ?? $address['hamlet'] ?? null;
        if ($area) {
            $parts[] = $area;
        }
        
        // City/Town/Village
        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;
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
        
        // If we have parts, join them
        if (!empty($parts)) {
            return implode(', ', $parts);
        }
        
        // Fallback to display name (simplified version)
        if ($displayName) {
            // Try to extract the most relevant parts from display name
            $displayParts = explode(',', $displayName);
            $relevantParts = array_slice($displayParts, 0, 4); // Take first 4 parts
            $simplified = implode(',', $relevantParts);
            return strlen($simplified) > 200 ? substr($simplified, 0, 197) . '...' : $simplified;
        }
        
        return "Location: " . ($address['country'] ?? 'Unknown');
    }
    
    /**
     * Generate Plus Code from coordinates
     * Plus Codes are short codes that represent locations
     * 
     * @param float $latitude
     * @param float $longitude
     * @return string|null Plus Code or null if generation fails
     */
    public function getPlusCode($latitude, $longitude) {
        if ($latitude === null || $longitude === null) {
            return null;
        }
        
        try {
            // Google Plus Codes API endpoint
            $url = sprintf(
                'https://plus.codes/api?address=%s,%s',
                $latitude,
                $longitude
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['plus_code']['global_code'])) {
                    return $data['plus_code']['global_code'];
                }
            }
            
            // Fallback: Generate basic Plus Code using algorithm
            return $this->generatePlusCode($latitude, $longitude);
            
        } catch (Exception $e) {
            return $this->generatePlusCode($latitude, $longitude);
        }
    }
    
    /**
     * Simple Plus Code generator (8-character code)
     * Format: XXXX+XX
     * 
     * @param float $lat
     * @param float $lon
     * @return string
     */
    private function generatePlusCode($lat, $lon) {
        // Plus Code character set
        $chars = '23456789CFGHJMPQRVWX';
        
        // Normalize coordinates
        $lat = $lat + 90;
        $lon = $lon + 180;
        
        // Generate code
        $code = '';
        $precision = [20, 1, 0.05, 0.0025, 0.000125, 0.00000625, 0.0000003125, 0.000000015625];
        
        for ($i = 0; $i < 8; $i++) {
            if ($i === 4) {
                $code .= '+';
            }
            
            if ($i % 2 === 0) {
                $val = floor($lat / $precision[$i]);
                $lat -= $val * $precision[$i];
            } else {
                $val = floor($lon / $precision[$i]);
                $lon -= $val * $precision[$i];
            }
            
            $code .= $chars[$val % strlen($chars)];
        }
        
        return $code;
    }
    
    /**
     * Get formatted address with Plus Code
     * Format: "XXXX+XX Area, City, State"
     * 
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function getAddressWithPlusCode($latitude, $longitude) {
        $plusCode = $this->generatePlusCode($latitude, $longitude);
        $address = $this->reverseGeocode($latitude, $longitude);
        
        if ($address) {
            // Prepend Plus Code to address
            return $plusCode . ' ' . $address;
        }
        
        return $plusCode;
    }
    
    /**
     * Get a short address (just street and city)
     * 
     * @param float $latitude
     * @param float $longitude
     * @return string|null Returns short address or null on failure
     */
    public function getShortAddress($latitude, $longitude) {
        if ($latitude === null || $longitude === null) {
            return null;
        }
        
        try {
            $url = sprintf(
                '%s?lat=%s&lon=%s&format=json&addressdetails=1&zoom=18',
                $this->baseUrl,
                $latitude,
                $longitude
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['address'])) {
                return null;
            }
            
            $address = $data['address'];
            $parts = [];
            
            // Get main components
            if (isset($address['road'])) {
                $parts[] = $address['road'];
            }
            
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? null;
            if ($city) {
                $parts[] = $city;
            }
            
            return !empty($parts) ? implode(', ', $parts) : ($data['display_name'] ?? null);
            
        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            return null;
        }
    }
}
?>
