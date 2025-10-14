<?php
// Initialize holidays table
include_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Create holidays table
    $query = "CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_date DATE NOT NULL UNIQUE,
        holiday_name VARCHAR(255) NOT NULL,
        holiday_type ENUM('National', 'Regional', 'Festival') DEFAULT 'National',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($query);
    echo "Holidays table created successfully.\n";
    
    // Insert holidays for 2025
    $holidays = [
        ['2025-01-01', 'New Year Day', 'National'],
        ['2025-01-26', 'Republic Day', 'National'],
        ['2025-03-14', 'Holi', 'Festival'],
        ['2025-04-14', 'Ambedkar Jayanti', 'National'],
        ['2025-04-18', 'Good Friday', 'National'],
        ['2025-05-01', 'May Day', 'National'],
        ['2025-08-15', 'Independence Day', 'National'],
        ['2025-10-02', 'Gandhi Jayanti', 'National'],
        ['2025-10-24', 'Dussehra', 'Festival'],
        ['2025-11-12', 'Diwali', 'Festival'],
        ['2025-12-25', 'Christmas', 'National']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO holidays (holiday_date, holiday_name, holiday_type) VALUES (?, ?, ?)");
    
    foreach ($holidays as $holiday) {
        $stmt->execute($holiday);
    }
    
    echo "Holidays inserted successfully.\n";
    echo "Total holidays added: " . count($holidays) . "\n";
    
} catch(PDOException $exception) {
    echo "Error: " . $exception->getMessage() . "\n";
}
?>
