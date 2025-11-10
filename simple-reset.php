<?php
// Simple password reset - set all passwords to a properly hashed version
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Password Reset</h2>";
    
    // Test different password options
    $passwords_to_try = ['admin123', 'password', '123456'];
    
    foreach ($passwords_to_try as $test_password) {
        $hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "Testing password: '{$test_password}'<br>";
        echo "Hash: {$hash}<br>";
        
        // Test if it verifies
        $verifies = password_verify($test_password, $hash);
        echo "Verifies: " . ($verifies ? 'YES' : 'NO') . "<br><br>";
    }
    
    // Reset all passwords to a properly hashed 'admin123'
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "<h3>Resetting all passwords to 'admin123'</h3>";
    echo "New hash: {$new_hash}<br>";
    
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?");
    $result = $stmt->execute([$new_hash]);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ PASSWORDS RESET SUCCESSFULLY!";
        echo "</div>";
        
        echo "<p>All user passwords have been set to: <strong>admin123</strong></p>";
        
        // Show users
        $users = $conn->query("SELECT email, user_type FROM users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Updated Users:</h4>";
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>{$user['email']} ({$user['user_type']}) - Password: admin123</li>";
        }
        echo "</ul>";
        
        echo "<a href='../admin/login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
        echo "🚀 TRY LOGIN NOW";
        echo "</a>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>