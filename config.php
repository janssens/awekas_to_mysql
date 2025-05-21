<?php
// Function to load environment variables from .env file
function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/.env';
    }
    
    if (!file_exists($path)) {
        die(".env file not found at: $path\n");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if they exist
        if (preg_match('/^"(.+)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        $_ENV[$name] = $value;
        putenv("$name=$value");
        define($name, $value); // Keep define for backward compatibility
    }
}

// Load environment variables
loadEnv();

// Set timezone
if (isset($_ENV['TIMEZONE'])) {
    date_default_timezone_set($_ENV['TIMEZONE']);
} else {
    date_default_timezone_set('Europe/Paris'); // Default timezone
}

// Create database connection
try {
    $db = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'], 
        $_ENV['DB_USER'], 
        $_ENV['DB_PASS']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}
?> 