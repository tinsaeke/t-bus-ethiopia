<?php
require_once '../includes/functions.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        $schedule_id = intval($_POST['schedule_id']);
        $selected_seats = explode(',', $_POST['selected_seats']);
        $passenger_names = $_POST['passenger_name'];
        $passenger_phones = $_POST['passenger_phone'];
        $payment_method = $_POST['payment_method'];
        $total_amount = floatval($_POST['total_amount']);
        
        // Verify schedule exists and has enough seats
        $scheduleQuery = "SELECT available_seats, price FROM schedules WHERE id = ? FOR UPDATE";
        $scheduleStmt = $db->prepare($scheduleQuery);
        $scheduleStmt->execute([$schedule_id]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule || $schedule['available_seats'] < count($selected_seats)) {
            throw new Exception("Not enough seats available");
        }
        
        $booking_reference = generateBookingReference();
        $bookings = [];
        
        // Create bookings for each passenger
        foreach ($selected_seats as $index => $seat_number) {
            $booking_data = [
                'booking_reference' => $booking_reference . '-' . ($index + 1),
                'schedule_id' => $schedule_id,
                'passenger_full_name' => sanitizeInput($passenger_names[$index]),
                'passenger_phone' => sanitizeInput($passenger_phones[$index]),
                'seat_number' => intval($seat_number),
                'total_price' => $schedule['price'],
                'payment_method' => $payment_method,
                'payment_status' => $payment_method == 'cash' ? 'pending' : 'paid',
                'qr_code_data' => json_encode([
                    'reference' => $booking_reference . '-' . ($index + 1),
                    'passenger' => sanitizeInput($passenger_names[$index]),
                    'seat' => intval($seat_number)
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
        $updateStmt->execute([count($selected_seats), $schedule_id]);
        
        $db->commit();
        
        // Redirect to success page
        $_SESSION['booking_success'] = [
            'reference' => $booking_reference,
            'bookings' => $bookings,
            'passenger_count' => count($selected_seats),
            'total_amount' => $total_amount,
            'schedule' => $schedule
        ];
        
        header("Location: booking-success.php");
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['booking_error'] = $e->getMessage();
        header("Location: booking.php?schedule_id=" . $schedule_id . "&passengers=" . count($selected_seats));
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>