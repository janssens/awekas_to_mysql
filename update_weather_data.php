<?php
require_once 'config.php';

$access_point = 'http://api.awekas.at/current.php?key=';
$str = file_get_contents($access_point.AWEKAS_KEY.'&lng='.AWEKAS_LANG);
$json = json_decode($str, true);

if (!isset($json['current']) || !is_array($json['current'])) {
    die("Error: No current weather data available\n");
}

// Get all column names from the weather_data table
$stmt = $db->query("SHOW COLUMNS FROM weather_data");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Remove 'id' and 'recorded_at' from columns as they are handled separately
$columns = array_diff($columns, ['id', 'recorded_at']);

// Prepare the data for insertion
$data = [];
$placeholders = [];
$values = [];

// Add recorded_at
if (isset($json['current']['datatimestamp'])) {
    $data[] = 'recorded_at';
    $placeholders[] = '?';
    $values[] = date('Y-m-d H:i:s', $json['current']['datatimestamp']);
}

// Add all non-null values from current data
foreach ($columns as $column) {
    if (isset($json['current'][$column]) && $json['current'][$column] !== '') {
        // Escape column names with backticks
        $data[] = "`$column`";
        $placeholders[] = '?';
        $values[] = $json['current'][$column];
    }
}

if (empty($data)) {
    die("Error: No valid data to insert\n");
}

// Prepare and execute the INSERT query
$sql = "INSERT INTO weather_data (" . implode(', ', $data) . ") 
        VALUES (" . implode(', ', $placeholders) . ")
        ON DUPLICATE KEY UPDATE " . 
        implode(', ', array_map(function($col) { 
            $col = trim($col, '`'); // Remove backticks if present
            return "`$col` = VALUES(`$col`)"; 
        }, $data));

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    echo "Weather data successfully updated\n";
} catch (PDOException $e) {
    die("Error inserting data: " . $e->getMessage() . "\n");
}
?> 