<?php
// Check users in database
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Users in Database</h2>";
    $users = $conn->query("SELECT id, email, password_hash, user_type, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Email</th><th>Password Hash</th><th>User Type</th><th>Full Name</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td style='font-size: 10px;'>{$user['password_hash']}</td>";
        echo "<td>{$user['user_type']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h2>Password Test</h2>";
    $test_password = "admin123";
    foreach ($users as $user) {
        $is_valid = password_verify($test_password, $user['password_hash']);
        echo "User: {$user['email']} - Password 'admin123' valid: " . ($is_valid ? 'YES' : 'NO') . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>