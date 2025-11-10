<?php
// Add sample Ethiopian cities
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Adding Ethiopian Cities</h2>";
    
    $cities = [
        'Addis Ababa', 'Adama', 'Hawassa', 'Bahir Dar', 'Gondar',
        'Mekelle', 'Dire Dawa', 'Jimma', 'Arba Minch', 'Adigrat',
        'Debre Markos', 'Debre Birhan', 'Shashamane', 'Hosaena', 'Wolkite',
        'Ambo', 'Assela', 'Dessie', 'Harar', 'Jijiga'
    ];
    
    $added = 0;
    foreach ($cities as $city) {
        // Check if city already exists
        $check = $conn->prepare("SELECT id FROM cities WHERE name = ?");
        $check->execute([$city]);
        
        if (!$check->fetch()) {
            $stmt = $conn->prepare("INSERT INTO cities (name) VALUES (?)");
            $stmt->execute([$city]);
            $added++;
            echo "<p>✅ Added: $city</p>";
        } else {
            echo "<p>⏩ Already exists: $city</p>";
        }
    }
    
    echo "<h3>🎉 Added $added new cities!</h3>";
    echo "<p><a href='check-cities.php'>View all cities</a></p>";
    echo "<p><a href='public/index.php'>Go to homepage</a> to test the search</p>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>