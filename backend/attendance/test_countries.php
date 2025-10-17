<?php
/**
 * Test different countries and years to find what works
 */
header('Content-Type: application/json');

function testAPI($country, $year) {
    $ch = curl_init();
    $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}";
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: AttendanceSystem/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'country' => $country,
        'year' => $year,
        'http_code' => $httpCode,
        'success' => $httpCode == 200,
        'data_count' => $httpCode == 200 ? count(json_decode($response, true)) : 0,
        'url' => $url
    ];
}

// Test multiple scenarios
$tests = [];

// Test India with different years
$tests[] = testAPI('IN', 2024);
$tests[] = testAPI('IN', 2025);
$tests[] = testAPI('IN', 2026);

// Test other countries with 2025
$tests[] = testAPI('US', 2025);
$tests[] = testAPI('GB', 2025);
$tests[] = testAPI('CA', 2025);
$tests[] = testAPI('AU', 2025);
$tests[] = testAPI('DE', 2025);

echo json_encode([
    'success' => true,
    'results' => $tests,
    'recommendation' => 'Use countries and years where success=true and data_count>0'
], JSON_PRETTY_PRINT);
?>
