<?php
require_once '../includes/functions.php';
$pageTitle = "Manage My Booking - T BUS";

$error = null;
if (isset($_POST['booking_reference']) && isset($_POST['phone_number'])) {
    $booking_reference = sanitizeInput($_POST['booking_reference']);
    $phone_number = sanitizeInput($_POST['phone_number']);

    // Redirect to the ticket details page
    redirect("ticket-details.php?ref=" . urlencode($booking_reference) . "&phone=" . urlencode($phone_number));
}
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
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><strong>T BUS</strong></a>
            <a href="index.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Manage Your Booking</h2>
                        <p class="text-center text-muted mb-4">Enter your booking reference and phone number to retrieve your ticket details.</p>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="booking_reference" class="form-label">Booking Reference</label>
                                <input type="text" class="form-control" id="booking_reference" name="booking_reference" placeholder="e.g., TB-64F8A..." required>
                            </div>
                            <div class="mb-4">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="The phone number used during booking" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search"></i> Find My Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
