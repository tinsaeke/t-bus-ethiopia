<?php
session_start();

// Fix the database path - use absolute path
$config_path = __DIR__ . '/../config/database.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("Database configuration file not found at: " . $config_path);
}

function generateBookingReference() {
    return 'TB-' . strtoupper(uniqid());
}

function formatCurrency($amount) {
    return 'ETB ' . number_format($amount, 2);
}

function getCityName($city_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT name FROM cities WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$city_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : 'Unknown City';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
}

function isPartnerAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'partner_admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Database connection test function
function testDatabaseConnection() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        return $db !== null;
    } catch (Exception $e) {
        return false;
    }
}
?>