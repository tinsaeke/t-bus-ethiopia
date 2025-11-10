<?php
require_once '../includes/functions.php';

$booking_reference_base = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';
$phone_number = isset($_GET['phone']) ? sanitizeInput($_GET['phone']) : '';

// Debug: Check what values we're receiving
error_log("Received ref: " . $booking_reference_base);
error_log("Received phone: " . $phone_number);

if (empty($booking_reference_base) || empty($phone_number)) {
    $_SESSION['error'] = "Please provide both booking reference and phone number.";
    redirect('manage-booking.php');
}

$database = new Database();
$db = $database->getConnection();

// SIMPLIFIED QUERY - Remove the complex reference matching for now
$query = "
    SELECT b.*, 
           s.travel_date, s.departure_time, s.arrival_time,
           c1.name as from_city, c2.name as to_city,
           bc.company_name
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    JOIN bus_companies bc ON bus.bus_company_id = bc.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE b.booking_reference = ? AND b.passenger_phone = ?
";

$stmt = $db->prepare($query);
$stmt->execute([$booking_reference_base, $phone_number]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Check query results
error_log("Found tickets: " . count($tickets));

if (empty($tickets)) {
    // Try alternative matching if exact match fails
    $query_like = "
        SELECT b.*, 
               s.travel_date, s.departure_time, s.arrival_time,
               c1.name as from_city, c2.name as to_city,
               bc.company_name
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bus ON s.bus_id = bus.id
        JOIN bus_companies bc ON bus.bus_company_id = bc.id
        JOIN cities c1 ON s.from_city_id = c1.id
        JOIN cities c2 ON s.to_city_id = c2.id
        WHERE b.booking_reference LIKE ? AND b.passenger_phone = ?
    ";
    
    $stmt_like = $db->prepare($query_like);
    $stmt_like->execute(["%$booking_reference_base%", $phone_number]);
    $tickets = $stmt_like->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found tickets with LIKE: " . count($tickets));
}

if (empty($tickets)) {
    $_SESSION['error'] = "No booking found with the provided details. Please check and try again.";
    redirect('manage-booking.php');
}

$pageTitle = "Your Booking Details - T BUS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .ticket-card {
            border: 2px dashed #0d6efd;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .qr-code {
            width: 150px; height: 150px; background: #fff; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; border: 2px solid #dee2e6;
        }
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><strong>T BUS</strong></a>
            <div class="navbar-text"><span class="text-light">Your E-Tickets</span></div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Debug Information (remove in production) -->
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    Reference: <?php echo htmlspecialchars($booking_reference_base); ?><br>
                    Phone: <?php echo htmlspecialchars($phone_number); ?><br>
                    Tickets Found: <?php echo count($tickets); ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Booking Reference: <?php echo htmlspecialchars($booking_reference_base); ?></h3>
                    <a href="manage-booking.php" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i> Look up another booking
                    </a>
                </div>

                <!-- Tickets -->
                <?php foreach ($tickets as $ticket): ?>
                <div class="card ticket-card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-primary">E-Ticket</h4>
                                <p class="mb-3">
                                    <strong>Route:</strong> <?php echo htmlspecialchars($ticket['from_city']); ?> → <?php echo htmlspecialchars($ticket['to_city']); ?><br>
                                    <strong>Date:</strong> <?php echo date('l, M d, Y', strtotime($ticket['travel_date'])); ?>
                                </p>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Passenger:</strong><br>
                                        <?php echo htmlspecialchars($ticket['passenger_full_name']); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Seat Number:</strong><br>
                                        #<?php echo htmlspecialchars($ticket['seat_number']); ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Phone:</strong><br>
                                        <?php echo htmlspecialchars($ticket['passenger_phone']); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Reference:</strong><br>
                                        <?php echo htmlspecialchars($ticket['booking_reference']); ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-<?php echo $ticket['booking_status'] == 'confirmed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($ticket['booking_status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Payment:</strong><br>
                                        <span class="badge bg-<?php echo $ticket['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($ticket['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0">
                                    <small><i class="fas fa-info-circle"></i> Please show this ticket at the bus station. Arrive at least 30 minutes before departure.</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="qr-code mx-auto mb-3">
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                </div>
                                <small class="text-muted">Scan QR code at station</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Action Buttons -->
                <div class="text-center mt-4">
                    <button onclick="window.print()" class="btn btn-outline-primary me-2">
                        <i class="fas fa-print"></i> Print Tickets
                    </button>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print styling
        window.onbeforeprint = function() { 
            document.querySelector('.navbar').style.display = 'none';
            document.querySelector('.text-center').style.display = 'none';
            document.querySelector('.d-flex.justify-content-between').style.display = 'none';
            document.querySelector('.debug-info').style.display = 'none';
        };
        window.onafterprint = function() { 
            document.querySelector('.navbar').style.display = '';
            document.querySelector('.text-center').style.display = '';
            document.querySelector('.d-flex.justify-content-between').style.display = '';
            document.querySelector('.debug-info').style.display = '';
        };
    </script>
</body>
</html>