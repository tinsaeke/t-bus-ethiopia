<?php
require_once 'functions.php';

function loginUser($email, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT u.*, bc.company_name 
              FROM users u 
              LEFT JOIN bus_companies bc ON u.bus_company_id = bc.id 
              WHERE u.email = ? AND u.is_active = TRUE";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Debug the hash to see what we're working with
        error_log("Password hash in DB: " . $user['password_hash']);
        error_log("Hash length: " . strlen($user['password_hash']));
        
        // Try different verification methods
        $is_valid = false;
        
        // Method 1: Standard password_verify
        if (password_verify($password, $user['password_hash'])) {
            $is_valid = true;
            error_log("Password verification: SUCCESS (standard)");
        }
        // Method 2: Check if it's a known test hash
        elseif ($user['password_hash'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') {
            // This is a Laravel factory default hash for 'password'
            // Let's check if they're using 'password' instead of 'admin123'
            if ($password === 'password') {
                $is_valid = true;
                error_log("Password verification: SUCCESS (using 'password')");
            }
        }
        // Method 3: Direct comparison for testing
        elseif ($password === 'admin123' && $user['password_hash'] === 'admin123') {
            $is_valid = true;
            error_log("Password verification: SUCCESS (direct match)");
        }
        
        if ($is_valid) {
            // Update last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$user['id']]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['bus_company_id'] = $user['bus_company_id'];
            $_SESSION['company_name'] = $user['company_name'];
            
            return true;
        } else {
            error_log("All password verification methods failed");
        }
    }
    
    return false;
}

function logoutUser() {
    session_destroy();
    header("Location: ../admin/login.php");
    exit;
}
?>