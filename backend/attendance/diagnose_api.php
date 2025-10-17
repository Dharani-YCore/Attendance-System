<?php
/**
 * Diagnostic Tool for Nager.Date API
 * Tests API connectivity and identifies issues
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Diagnostics</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .container {
            background: #252526;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #3e3e42;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        h2 {
            color: #569cd6;
            margin-top: 25px;
        }
        .test {
            background: #1e1e1e;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #3e3e42;
        }
        .test.pass {
            border-left-color: #4ec9b0;
        }
        .test.fail {
            border-left-color: #f48771;
        }
        .test.warn {
            border-left-color: #dcdcaa;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
            margin-right: 10px;
        }
        .status.pass {
            background: #4ec9b0;
            color: #1e1e1e;
        }
        .status.fail {
            background: #f48771;
            color: #1e1e1e;
        }
        .status.warn {
            background: #dcdcaa;
            color: #1e1e1e;
        }
        pre {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #3e3e42;
        }
        .info {
            color: #9cdcfe;
        }
        .error {
            color: #f48771;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #dcdcaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Nager.Date API Diagnostics</h1>
        <p>Running comprehensive tests to identify API connectivity issues...</p>

        <?php
        // Test 1: Check if cURL is enabled
        echo '<h2>Test 1: PHP cURL Extension</h2>';
        echo '<div class="test ';
        if (function_exists('curl_version')) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">cURL is enabled</span>';
            $curlVersion = curl_version();
            echo '<pre>Version: ' . $curlVersion['version'] . "\n";
            echo 'SSL Version: ' . $curlVersion['ssl_version'] . '</pre>';
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">cURL is NOT enabled - This is required!</span>';
            echo '<p class="warning">Fix: Enable cURL in php.ini by uncommenting: extension=curl</p>';
        }
        echo '</div>';

        // Test 2: Check OpenSSL
        echo '<h2>Test 2: OpenSSL Support</h2>';
        echo '<div class="test ';
        if (extension_loaded('openssl')) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">OpenSSL is enabled (required for HTTPS)</span>';
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">OpenSSL is NOT enabled - HTTPS requests will fail!</span>';
        }
        echo '</div>';

        // Test 3: Test basic internet connectivity
        echo '<h2>Test 3: Internet Connectivity</h2>';
        echo '<div class="test ';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.google.com");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 || $httpCode == 301 || $httpCode == 302) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">Internet connection is working</span>';
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">Cannot reach the internet</span>';
            if ($error) {
                echo '<pre class="error">Error: ' . htmlspecialchars($error) . '</pre>';
            }
        }
        echo '</div>';

        // Test 4: Check if Nager.Date API is accessible
        echo '<h2>Test 4: Nager.Date API Accessibility</h2>';
        echo '<div class="test ';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://date.nager.at/api/v3/AvailableCountries");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: AttendanceSystem/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">Nager.Date API is accessible!</span>';
            
            $countries = json_decode($response, true);
            if ($countries) {
                echo '<p class="info">Found ' . count($countries) . ' countries</p>';
                echo '<pre>Sample: ' . json_encode(array_slice($countries, 0, 5), JSON_PRETTY_PRINT) . '</pre>';
            }
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">Cannot reach Nager.Date API</span>';
            echo '<pre class="error">';
            echo 'HTTP Code: ' . $httpCode . "\n";
            if ($error) {
                echo 'cURL Error: ' . htmlspecialchars($error) . "\n";
            }
            echo 'URL: ' . $info['url'] . "\n";
            echo 'Connect Time: ' . $info['connect_time'] . 's' . "\n";
            echo 'Total Time: ' . $info['total_time'] . 's';
            echo '</pre>';
            
            // Try without SSL verification
            echo '<p class="warning">Trying without SSL verification...</p>';
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, "https://date.nager.at/api/v3/AvailableCountries");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
            
            $response2 = curl_exec($ch2);
            $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($httpCode2 == 200) {
                echo '<div class="test warn">';
                echo '<span class="status warn">⚠ WARN</span>';
                echo '<span class="warning">API works without SSL verification. This might be a certificate issue.</span>';
                echo '<p>Fix: Update your CA certificates or adjust SSL settings in php.ini</p>';
                echo '</div>';
            }
        }
        echo '</div>';

        // Test 5: Test specific country holidays
        echo '<h2>Test 5: Fetch India (IN) Holidays</h2>';
        echo '<div class="test ';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://date.nager.at/api/v3/PublicHolidays/2025/IN");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: AttendanceSystem/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">Successfully fetched India holidays!</span>';
            
            $holidays = json_decode($response, true);
            if ($holidays) {
                echo '<p class="info">Found ' . count($holidays) . ' holidays for India in 2025</p>';
                echo '<pre>' . json_encode(array_slice($holidays, 0, 3), JSON_PRETTY_PRINT) . '</pre>';
            }
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">Cannot fetch holidays for India</span>';
            echo '<pre class="error">';
            echo 'HTTP Code: ' . $httpCode . "\n";
            if ($error) {
                echo 'Error: ' . htmlspecialchars($error);
            }
            echo '</pre>';
        }
        echo '</div>';

        // Test 6: Check PHP allow_url_fopen
        echo '<h2>Test 6: PHP Configuration</h2>';
        echo '<div class="test ';
        $allowUrlFopen = ini_get('allow_url_fopen');
        if ($allowUrlFopen) {
            echo 'pass">';
            echo '<span class="status pass">✓ PASS</span>';
            echo '<span class="success">allow_url_fopen is enabled</span>';
        } else {
            echo 'warn">';
            echo '<span class="status warn">⚠ WARN</span>';
            echo '<span class="warning">allow_url_fopen is disabled (not required for cURL)</span>';
        }
        echo '</div>';

        // Test 7: Test service class
        echo '<h2>Test 7: NagerDateService Class</h2>';
        echo '<div class="test ';
        
        if (file_exists('../services/nager_date_service.php')) {
            include_once '../services/nager_date_service.php';
            
            if (class_exists('NagerDateService')) {
                $service = new NagerDateService();
                $testHolidays = $service->getPublicHolidays('IN', 2025);
                
                if ($testHolidays !== false && !empty($testHolidays)) {
                    echo 'pass">';
                    echo '<span class="status pass">✓ PASS</span>';
                    echo '<span class="success">Service class is working correctly!</span>';
                    echo '<p class="info">Fetched ' . count($testHolidays) . ' holidays using the service class</p>';
                } else {
                    echo 'fail">';
                    echo '<span class="status fail">✗ FAIL</span>';
                    echo '<span class="error">Service class exists but cannot fetch data</span>';
                }
            } else {
                echo 'fail">';
                echo '<span class="status fail">✗ FAIL</span>';
                echo '<span class="error">NagerDateService class not found</span>';
            }
        } else {
            echo 'fail">';
            echo '<span class="status fail">✗ FAIL</span>';
            echo '<span class="error">nager_date_service.php file not found</span>';
        }
        echo '</div>';

        // Summary
        echo '<h2>📊 Summary & Recommendations</h2>';
        echo '<div class="test warn">';
        echo '<p><strong>Common Issues & Solutions:</strong></p>';
        echo '<ol>';
        echo '<li><strong>SSL Certificate Issue:</strong> Update CA certificates or set <code>CURLOPT_SSL_VERIFYPEER => false</code> (temporary fix)</li>';
        echo '<li><strong>Firewall/Proxy:</strong> Check if your firewall blocks outgoing HTTPS requests</li>';
        echo '<li><strong>cURL not enabled:</strong> Enable in php.ini: <code>extension=curl</code></li>';
        echo '<li><strong>Timeout:</strong> Increase timeout in service class if network is slow</li>';
        echo '<li><strong>API Down:</strong> Check API status at <a href="https://date.nager.at" target="_blank">https://date.nager.at</a></li>';
        echo '</ol>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
