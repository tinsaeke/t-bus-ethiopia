<?php
// Check cities in database
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🏙️ Cities in Database</h2>";
    
    $cities = $conn->query("SELECT * FROM cities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($cities) {
        echo "<p>Found " . count($cities) . " cities:</p>";
        echo "<ul>";
        foreach ($cities as $city) {
            echo "<li>{$city['name']} (ID: {$city['id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>No cities found in database!</p>";
        echo "<p><a href='add-sample-cities.php'>Click here to add sample cities</a></p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>