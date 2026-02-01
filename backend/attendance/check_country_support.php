<?php
/**
 * Check which countries have holiday data available
 * This helps identify which countries are actually supported by Nager.Date API
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Country Support Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .supported {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .not-supported {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 10px;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        .stats {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        select, button {
            padding: 10px;
            margin: 10px 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .popular {
            border-left-color: #ffc107 !important;
            background: #fff3cd !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Country Support Checker</h1>
        <p>Checking which countries have holiday data in Nager.Date API for year: <strong id="currentYear">2025</strong></p>
        
        <div>
            <label>Test Year: </label>
            <select id="yearSelect">
                <option value="2024">2024</option>
                <option value="2025" selected>2025</option>
                <option value="2026">2026</option>
            </select>
            <button onclick="checkCountries()">Check Countries</button>
        </div>

        <div id="stats" class="stats" style="display:none;">
            <h3>📊 Results:</h3>
            <p><strong>Supported:</strong> <span id="supportedCount">0</span> countries</p>
            <p><strong>Not Supported:</strong> <span id="notSupportedCount">0</span> countries</p>
        </div>

        <div id="loading" class="loading">
            <p>🔄 Loading countries from API...</p>
        </div>

        <div id="results"></div>
    </div>

    <script>
        const popularCountries = ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'BR', 'MX', 'AR', 'JP', 'CN', 'IN', 'SG', 'MY', 'ID', 'TH', 'VN', 'PH'];

        async function checkCountries() {
            const year = document.getElementById('yearSelect').value;
            document.getElementById('currentYear').textContent = year;
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').innerHTML = '';
            document.getElementById('stats').style.display = 'none';

            try {
                // Get all countries
                const countriesResponse = await fetch('https://date.nager.at/api/v3/AvailableCountries');
                const countries = await countriesResponse.json();

                let supported = 0;
                let notSupported = 0;
                const results = [];

                // Check each country
                for (const country of countries) {
                    const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/${country.countryCode}`);
                    const hasData = response.status === 200;
                    const holidayCount = hasData ? (await response.json()).length : 0;

                    if (hasData) {
                        supported++;
                    } else {
                        notSupported++;
                    }

                    results.push({
                        code: country.countryCode,
                        name: country.name,
                        hasData: hasData,
                        count: holidayCount,
                        isPopular: popularCountries.includes(country.countryCode)
                    });
                }

                // Update stats
                document.getElementById('supportedCount').textContent = supported;
                document.getElementById('notSupportedCount').textContent = notSupported;
                document.getElementById('stats').style.display = 'block';

                // Sort: supported first, then popular, then alphabetical
                results.sort((a, b) => {
                    if (a.hasData !== b.hasData) return b.hasData - a.hasData;
                    if (a.isPopular !== b.isPopular) return b.isPopular - a.isPopular;
                    return a.name.localeCompare(b.name);
                });

                // Display results
                const resultsDiv = document.getElementById('results');
                results.forEach(result => {
                    const div = document.createElement('div');
                    div.className = result.hasData ? 'supported' : 'not-supported';
                    if (result.isPopular) div.classList.add('popular');
                    
                    div.innerHTML = `
                        <div>
                            <strong>${result.name}</strong> (${result.code})
                            ${result.isPopular ? '⭐' : ''}
                            ${result.hasData ? ` - ${result.count} holidays` : ''}
                        </div>
                        <span class="badge ${result.hasData ? 'badge-success' : 'badge-danger'}">
                            ${result.hasData ? '✓ Supported' : '✗ No Data'}
                        </span>
                    `;
                    resultsDiv.appendChild(div);
                });

                document.getElementById('loading').style.display = 'none';

            } catch (error) {
                document.getElementById('loading').innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            }
        }

        // Auto-run on page load
        window.onload = () => checkCountries();
    </script>
</body>
</html>
