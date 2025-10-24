<?php
/**
 * Nager.Date API Service (SSL Verification Disabled)
 * USE THIS VERSION IF YOU'RE GETTING SSL CERTIFICATE ERRORS
 * 
 * Note: Disabling SSL verification is not recommended for production,
 * but can be necessary in local development environments (XAMPP on Windows)
 * 
 * For production, update your CA certificates instead.
 */

class NagerDateService {
    private $baseUrl = 'https://date.nager.at/api/v3';
    private $verifySsl = false; // Disabled for local development
    
    /**
     * Get available countries
     * @return array|false Array of countries or false on error
     */
    public function getAvailableCountries() {
        $url = $this->baseUrl . '/AvailableCountries';
        return $this->makeRequest($url);
    }
    
    /**
     * Get public holidays for a specific country and year
     * @param string $countryCode ISO 3166-1 alpha-2 country code (e.g., 'IN' for India, 'US' for USA)
     * @param int $year Year (e.g., 2025)
     * @return array|false Array of holidays or false on error
     */
    public function getPublicHolidays($countryCode, $year) {
        $url = $this->baseUrl . '/PublicHolidays/' . $year . '/' . strtoupper($countryCode);
        return $this->makeRequest($url);
    }
    
    /**
     * Check if a specific date is a public holiday
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param string $date Date in YYYY-MM-DD format
     * @return bool
     */
    public function isPublicHoliday($countryCode, $date) {
        $url = $this->baseUrl . '/IsTodayPublicHoliday/' . strtoupper($countryCode) . '?date=' . $date;
        $result = $this->makeRequest($url, false);
        return $result === true;
    }
    
    /**
     * Get next public holidays for a specific country
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return array|false Array of upcoming holidays or false on error
     */
    public function getNextPublicHolidays($countryCode) {
        $url = $this->baseUrl . '/NextPublicHolidays/' . strtoupper($countryCode);
        return $this->makeRequest($url);
    }
    
    /**
     * Get long weekends for a specific country and year
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param int $year Year
     * @return array|false Array of long weekends or false on error
     */
    public function getLongWeekends($countryCode, $year) {
        $url = $this->baseUrl . '/LongWeekend/' . $year . '/' . strtoupper($countryCode);
        return $this->makeRequest($url);
    }
    
    /**
     * Make HTTP GET request to the API
     * @param string $url API endpoint URL
     * @param bool $decodeJson Whether to decode JSON response
     * @return mixed Response data or false on error
     */
    private function makeRequest($url, $decodeJson = true) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl, // Disabled for local dev
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0, // Disabled for local dev
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: AttendanceSystem/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Check for cURL errors
        if ($error) {
            error_log("Nager.Date API Error: " . $error);
            return false;
        }
        
        // Check HTTP status code
        if ($httpCode === 204) {
            // No content (e.g., not a public holiday)
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("Nager.Date API HTTP Error: " . $httpCode . " for URL: " . $url);
            return false;
        }
        
        if ($decodeJson) {
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Nager.Date API JSON Error: " . json_last_error_msg());
                return false;
            }
            
            return $data;
        }
        
        return $response;
    }
    
    /**
     * Parse holiday data from API response to database format
     * @param array $holidays Raw holiday data from API
     * @param string $countryCode Country code
     * @return array Formatted holidays for database insertion
     */
    public function parseHolidaysForDB($holidays, $countryCode) {
        $parsedHolidays = [];
        
        foreach ($holidays as $holiday) {
            $type = 'National'; // Default type
            
            // Determine holiday type based on attributes
            if (isset($holiday['types'])) {
                if (in_array('Public', $holiday['types'])) {
                    $type = 'National';
                } elseif (in_array('Bank', $holiday['types'])) {
                    $type = 'Regional';
                } elseif (in_array('Optional', $holiday['types'])) {
                    $type = 'Festival';
                }
            }
            
            // Check if it's a global holiday
            if (isset($holiday['global']) && $holiday['global'] === true) {
                $type = 'National';
            } elseif (isset($holiday['counties']) && !empty($holiday['counties'])) {
                $type = 'Regional';
            }
            
            $parsedHolidays[] = [
                'date' => $holiday['date'],
                'name' => $holiday['name'] ?? $holiday['localName'],
                'type' => $type,
                'country_code' => strtoupper($countryCode)
            ];
        }
        
        return $parsedHolidays;
    }
}
?>
