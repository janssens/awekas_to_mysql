<?php

require_once __DIR__ . '/../config.php';

// Define the SQL directory path
$sqlDir = __DIR__;

// List of SQL files to execute in order
$sqlFiles = [
    'create_weather_data_table.sql',
    'create_alerts_table.sql',
    'create_push_subscriptions_table.sql',
    'create_push_subscriptions_alerts_table.sql',
    'create_telegram_config_table.sql'
];

function executeSqlFile($db, $sqlDir, $file) {
    echo "Executing $file...\n";
    
    try {
        $fullPath = $sqlDir . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($fullPath)) {
            throw new Exception("SQL file not found: $fullPath");
        }

        $sql = file_get_contents($fullPath);
        if ($sql === false) {
            throw new Exception("Could not read file: $fullPath");
        }

        // Split file into individual statements
        $statements = array_filter(
            array_map(
                'trim',
                explode(';', $sql)
            )
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
            }
        }
        
        echo "✓ Success\n";
        return true;
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "Starting database installation...\n\n";
echo "SQL directory: $sqlDir\n\n";

$success = true;
foreach ($sqlFiles as $file) {
    if (!executeSqlFile($db, $sqlDir, $file)) {
        $success = false;
        break;
    }
}

echo "\n" . ($success ? "Installation completed successfully!" : "Installation failed!") . "\n"; 