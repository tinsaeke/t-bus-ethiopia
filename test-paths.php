<?php
echo "<h2>Path Test</h2>";

// Test config path
$config_path = __DIR__ . '/config/database.php';
echo "Config path: " . $config_path . "<br>";
echo "Config exists: " . (file_exists($config_path) ? 'Yes' : 'No') . "<br>";

// Test includes path  
$includes_path = __DIR__ . '/includes/functions.php';
echo "Includes path: " . $includes_path . "<br>";
echo "Includes exists: " . (file_exists($includes_path) ? 'Yes' : 'No') . "<br>";

// Test database connection
if (file_exists($config_path)) {
    require_once $config_path;
    try {
        $database = new Database();
        $db = $database->getConnection();
        echo "Database connection: ✅ Success<br>";
        
        // Test tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . implode(', ', $tables) . "<br>";
    } catch (Exception $e) {
        echo "Database connection: ❌ Failed - " . $e->getMessage() . "<br>";
    }
}
?>