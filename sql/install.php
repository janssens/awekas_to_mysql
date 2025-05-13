<?php

require_once __DIR__ . '/../config.php';

// List of SQL files to execute in order
$sqlFiles = [
    'create_weather_data_table.sql',
    'create_weather_alerts_table.sql',
    'create_push_subscriptions_table.sql',
    'push_subscriptions_alerts.sql'
];

function executeSqlFile($db, $file) {
    echo "Executing $file...\n";
    
    try {
        $sql = file_get_contents(__DIR__ . '/' . $file);
        if ($sql === false) {
            throw new Exception("Could not read file: $file");
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

$success = true;
foreach ($sqlFiles as $file) {
    if (!executeSqlFile($db, $file)) {
        $success = false;
        break;
    }
}

echo "\n" . ($success ? "Installation completed successfully!" : "Installation failed!") . "\n"; 