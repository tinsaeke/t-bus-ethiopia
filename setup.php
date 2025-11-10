<?php
/**
 * T BUS Setup Script
 * Run this file once to verify your installation
 */

echo "<h2>🚌 T BUS Setup Verification</h2>";

// Check PHP Version
echo "<h3>1. PHP Version Check</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "✅ PHP version is compatible<br>";
} else {
    echo "❌ PHP version too old. Please upgrade to 7.4 or higher<br>";
}

// Check PHP Extensions
echo "<h3>2. PHP Extensions Check</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension is loaded<br>";
    } else {
        echo "❌ $ext extension is missing<br>";
    }
}

// Check Database Connection
echo "<h3>3. Database Connection Check</h3>";
try {
    // First, let's check if the config file exists
    if (!file_exists('config/database.php')) {
        throw new Exception("Database configuration file not found at config/database.php");
    }
    
    // Include the database configuration
    require_once 'config/database.php';
    
    // Check if Database class exists
    if (!class_exists('Database')) {
        throw new Exception("Database class not found. Please check config/database.php");
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection successful<br>";
        
        // Check if tables exist
        $tables = ['cities', 'bus_companies', 'users', 'buses', 'schedules', 'bookings', 'site_content', 'popular_routes'];
        foreach ($tables as $table) {
            $check = $db->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($check) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
        
        // Check if we have initial data
        echo "<h4>4. Initial Data Check</h4>";
        $cities_count = $db->query("SELECT COUNT(*) as count FROM cities")->fetch()['count'];
        echo "Cities in database: $cities_count<br>";
        
        $companies_count = $db->query("SELECT COUNT(*) as count FROM bus_companies")->fetch()['count'];
        echo "Bus companies: $companies_count<br>";
        
        $users_count = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
        echo "Users: $users_count<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    
    // Show debugging info
    echo "<h4>Debug Information:</h4>";
    echo "Config file exists: " . (file_exists('config/database.php') ? 'Yes' : 'No') . "<br>";
    echo "Current directory: " . __DIR__ . "<br>";
}

// Check File Permissions
echo "<h3>5. File Permissions Check</h3>";
$directories = ['admin', 'partners', 'public', 'includes', 'config'];
foreach ($directories as $dir) {
    if (is_dir($dir) && is_readable($dir)) {
        echo "✅ Directory '$dir' is accessible<br>";
    } else {
        echo "❌ Directory '$dir' has issues<br>";
    }
}

// Check if essential files exist
echo "<h3>6. Essential Files Check</h3>";
$essential_files = [
    'config/database.php',
    'includes/functions.php',
    'includes/auth.php',
    'admin/login.php',
    'public/index.php'
];

foreach ($essential_files as $file) {
    if (file_exists($file)) {
        echo "✅ File '$file' exists<br>";
    } else {
        echo "❌ File '$file' missing<br>";
    }
}

echo "<h3>🎯 Setup Complete!</h3>";
echo "<p>If you see all green checkmarks, your setup is ready!</p>";
echo "<p><strong>Access Links:</strong></p>";
echo "<ul>";
echo "<li><a href='public/index.php'>Public Website</a></li>";
echo "<li><a href='admin/login.php'>Admin Login</a></li>";
echo "<li><a href='partners/login.php'>Partner Login</a></li>";
echo "</ul>";

echo "<h4>Demo Login Credentials:</h4>";
echo "<strong>Super Admin:</strong> superadmin@tbus.et / admin123<br>";
echo "<strong>Partner Admin:</strong> selam@tbus.et / admin123<br>";

// Simple database test
echo "<h4>Quick Database Test:</h4>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Test cities
    $cities = $db->query("SELECT name FROM cities LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    echo "Sample cities: " . implode(', ', $cities) . "<br>";
    
    // Test users
    $users = $db->query("SELECT email, user_type FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Users in system:<br>";
    foreach ($users as $user) {
        echo "- {$user['email']} ({$user['user_type']})<br>";
    }
    
} catch (Exception $e) {
    echo "Database test failed: " . $e->getMessage() . "<br>";
}
?>