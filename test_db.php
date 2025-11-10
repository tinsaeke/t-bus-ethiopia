<?php
// Simple database connection test
echo "<h2>Database Connection Test</h2>";

try {
    $host = 'localhost';
    $dbname = 't_bus_ethiopia';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!<br>";
    
    // Check tables
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . implode(', ', $tables) . "<br>";
    
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "<br>";
}
?>