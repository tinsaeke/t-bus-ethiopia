<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isPartnerAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Bookings - Partner Portal";

$database = new Database();
$db = $database->getConnection();
$company_id = $_SESSION['bus_company_id'];

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'cancel') {
            $updateQuery = "UPDATE bookings SET booking_status = 'cancelled' WHERE id = ? AND schedule_id IN (SELECT s.id FROM schedules s JOIN buses b ON s.bus_id = b.id WHERE b.bus_company_id = ?)";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$booking_id, $company_id]);
            $_SESSION['success'] = "Booking cancelled successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('bookings.php');
}

// Get company bookings
$bookings = $db->query("
    SELECT b.*, s.from_city_id, s.to_city_id, s.travel_date, s.departure_time,
           bus.bus_number, c1.name as from_city, c2.name as to_city
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE bus.bus_company_id = $company_id
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
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
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Bookings</h1>
                </div>

                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Company's Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <h5>No Bookings Found</h5>
                                <p class="text-muted">When customers book your buses, they will appear here.</p>
                                <a href="manage-schedules.php" class="btn btn-primary">Create Schedules</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Booking Ref</th>
                                            <th>Passenger</th>
                                            <th>Route</th>
                                            <th>Date & Time</th>
                                            <th>Bus</th>
                                            <th>Seat</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><small><?php echo $booking['booking_reference']; ?></small></td>
                                            <td>
                                                <strong><?php echo $booking['passenger_full_name']; ?></strong>
                                                <br><small><?php echo $booking['passenger_phone']; ?></small>
                                            </td>
                                            <td><?php echo $booking['from_city']; ?> → <?php echo $booking['to_city']; ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?>
                                                <br><small><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></small>
                                            </td>
                                            <td><?php echo $booking['bus_number']; ?></td>
                                            <td>#<?php echo $booking['seat_number']; ?></td>
                                            <td class="text-success">ETB <?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['booking_status'] == 'confirmed' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                                    <a href="bookings.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning"
                                                       onclick="return confirm('Cancel this booking?')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>