<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $booking_reference = isset($input['booking_reference']) ? sanitizeInput($input['booking_reference']) : '';
    $payment_method = isset($input['payment_method']) ? sanitizeInput($input['payment_method']) : '';
    $payment_reference = isset($input['payment_reference']) ? sanitizeInput($input['payment_reference']) : '';

    if (!$booking_reference || !$payment_method) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Update payment status
        $updateQuery = "
            UPDATE bookings 
            SET payment_status = 'paid', payment_method = ?, payment_reference = ?
            WHERE booking_reference LIKE ? AND payment_status = 'pending'
        ";

        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$payment_method, $payment_reference, $booking_reference . '%']);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment processed successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No pending bookings found or already processed'
            ]);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>