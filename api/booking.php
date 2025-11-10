<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $schedule_id = isset($input['schedule_id']) ? intval($input['schedule_id']) : 0;
    $passengers = isset($input['passengers']) ? $input['passengers'] : [];
    
    if (!$schedule_id || empty($passengers)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();
        $db->beginTransaction();

        // Verify schedule exists and has enough seats
        $scheduleQuery = "SELECT available_seats, price FROM schedules WHERE id = ? FOR UPDATE";
        $scheduleStmt = $db->prepare($scheduleQuery);
        $scheduleStmt->execute([$schedule_id]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule || $schedule['available_seats'] < count($passengers)) {
            throw new Exception("Not enough seats available");
        }

        $booking_reference = generateBookingReference();
        $bookings = [];

        // Create bookings for each passenger
        foreach ($passengers as $index => $passenger) {
            $booking_data = [
                'booking_reference' => $booking_reference . '-' . ($index + 1),
                'schedule_id' => $schedule_id,
                'passenger_full_name' => sanitizeInput($passenger['name']),
                'passenger_phone' => sanitizeInput($passenger['phone']),
                'seat_number' => intval($passenger['seat']),
                'total_price' => $schedule['price'],
                'payment_method' => 'telebirr', // Default for API
                'payment_status' => 'pending',
                'qr_code_data' => json_encode([
                    'reference' => $booking_reference . '-' . ($index + 1),
                    'passenger' => sanitizeInput($passenger['name']),
                    'seat' => intval($passenger['seat'])
                ])
            ];

            $insertQuery = "
                INSERT INTO bookings 
                (booking_reference, schedule_id, passenger_full_name, passenger_phone, 
                 seat_number, total_price, payment_method, payment_status, qr_code_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $db->prepare($insertQuery);
            $stmt->execute(array_values($booking_data));
            $bookings[] = $booking_data;
        }

        // Update available seats
        $updateQuery = "UPDATE schedules SET available_seats = available_seats - ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([count($passengers), $schedule_id]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'booking_reference' => $booking_reference,
            'bookings' => $bookings,
            'total_amount' => $schedule['price'] * count($passengers)
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>