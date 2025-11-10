<?php
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$company_id = intval($_POST['id']);
$is_active = $_POST['is_active'] === 'true';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "UPDATE bus_companies SET is_active = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$is_active, $company_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>