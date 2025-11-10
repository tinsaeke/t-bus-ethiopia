<?php
require_once '../includes/functions.php';

if (!isset($_SESSION['booking_success'])) {
    header("Location: index.php");
    exit;
}

$booking_data = $_SESSION['booking_success'];
unset($_SESSION['booking_success']);

$pageTitle = "Booking Confirmed - T BUS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <strong>T BUS</strong>
            </a>
            <div class="navbar-text">
                <span class="text-light">Booking Confirmed!</span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Message -->
                <div class="card border-success mb-4">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>
                        <h1 class="text-success mb-3">Booking Confirmed!</h1>
                        <p class="lead mb-4">Your bus tickets have been successfully booked</p>
                        <div class="alert alert-info">
                            <strong>Booking Reference:</strong> <?php echo $booking_data['reference']; ?><br>
                            <strong>Total Passengers:</strong> <?php echo $booking_data['passenger_count']; ?><br>
                            <strong>Total Amount:</strong> ETB <?php echo number_format($booking_data['total_amount'], 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Tickets -->
                <?php foreach ($booking_data['bookings'] as $ticket): ?>
                <div class="card border-success mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-success">E-Ticket</h4>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Passenger:</strong><br>
                                        <?php echo $ticket['passenger_full_name']; ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Seat Number:</strong><br>
                                        #<?php echo $ticket['seat_number']; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Phone:</strong><br>
                                        <?php echo $ticket['passenger_phone']; ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Reference:</strong><br>
                                        <?php echo $ticket['booking_reference']; ?>
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0">
                                    <small>
                                        <i class="fas fa-info-circle"></i>
                                        Please show this ticket at the bus station. 
                                        Arrive at least 30 minutes before departure.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="border rounded p-3 bg-light mb-3">
                                    <!-- QR Code placeholder -->
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                </div>
                                <small class="text-muted">Scan QR code at station</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Action Buttons -->
                <div class="text-center">
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
</body>
</html>