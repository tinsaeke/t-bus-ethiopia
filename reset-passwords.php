<?php
// Reset all user passwords to 'admin123'
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Update all users
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?");
    $result = $stmt->execute([$password_hash]);
    
    if ($result) {
        echo "<h2>✅ Passwords Reset Successfully!</h2>";
        echo "All user passwords have been reset to: <strong>admin123</strong>";
        echo "<br><br>";
        echo "New password hash: " . $password_hash;
        
        // Show updated users
        echo "<h3>Updated Users:</h3>";
        $users = $conn->query("SELECT email, user_type FROM users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            echo "{$user['email']} ({$user['user_type']})<br>";
        }
        
        echo "<br><a href='../admin/login.php'>Try Login Now</a>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>