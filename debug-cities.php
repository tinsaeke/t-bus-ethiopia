<?php
// Debug cities dropdown issue
echo "<h2>🔧 Debug Cities Dropdown</h2>";

try {
    // Test database connection
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if cities table exists
    $tables = $conn->query("SHOW TABLES LIKE 'cities'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Cities table does not exist!</p>";
    } else {
        echo "<p style='color: green;'>✅ Cities table exists</p>";
    }
    
    // Get cities count
    $cities_count = $conn->query("SELECT COUNT(*) FROM cities")->fetchColumn();
    echo "<p>Cities in database: " . $cities_count . "</p>";
    
    // Show actual cities
    $cities = $conn->query("SELECT * FROM cities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($cities) {
        echo "<h3>🏙️ Cities in Database:</h3>";
        echo "<ul>";
        foreach ($cities as $city) {
            echo "<li>ID: {$city['id']} - Name: {$city['name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No cities found in database</p>";
        echo "<p><a href='add-sample-cities.php'>Click here to add sample cities</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test the Database class
echo "<h3>Testing Database Class</h3>";
$config_path = __DIR__ . '/config/database.php';
if (file_exists($config_path)) {
    echo "<p style='color: green;'>✅ config/database.php exists</p>";
    
    require_once $config_path;
    
    if (class_exists('Database')) {
        echo "<p style='color: green;'>✅ Database class exists</p>";
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                echo "<p style='color: green;'>✅ Database class connection successful</p>";
                
                // Test cities query with Database class
                $cities = $db->query("SELECT * FROM cities ORDER BY name")->fetchAll();
                echo "<p>Cities via Database class: " . count($cities) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Database class connection failed</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database class error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database class not found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ config/database.php not found at: " . $config_path . "</p>";
}
?>