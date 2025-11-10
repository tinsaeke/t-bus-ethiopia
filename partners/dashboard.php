<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isPartnerAdmin()) {
    redirect('login.php');
}

$pageTitle = "Partner Dashboard - T BUS";

// Partner-specific statistics
$database = new Database();
$db = $database->getConnection();
$company_id = $_SESSION['bus_company_id'];

// Get partner stats
$stats = [
    'total_bookings' => $db->query("SELECT COUNT(*) FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN buses bus ON s.bus_id = bus.id WHERE bus.bus_company_id = $company_id")->fetchColumn(),
    'total_revenue' => $db->query("SELECT COALESCE(SUM(b.total_price), 0) FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN buses bus ON s.bus_id = bus.id WHERE bus.bus_company_id = $company_id AND b.payment_status = 'paid'")->fetchColumn(),
    'active_buses' => $db->query("SELECT COUNT(*) FROM buses WHERE bus_company_id = $company_id AND is_active = TRUE")->fetchColumn(),
    'today_bookings' => $db->query("SELECT COUNT(*) FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN buses bus ON s.bus_id = bus.id WHERE bus.bus_company_id = $company_id AND DATE(b.created_at) = CURDATE()")->fetchColumn(),
    'pending_payments' => $db->query("SELECT COUNT(*) FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN buses bus ON s.bus_id = bus.id WHERE bus.bus_company_id = $company_id AND b.payment_status = 'pending'")->fetchColumn(),
    'active_schedules' => $db->query("SELECT COUNT(*) FROM schedules s JOIN buses b ON s.bus_id = b.id WHERE b.bus_company_id = $company_id AND s.travel_date >= CURDATE() AND s.is_active = TRUE")->fetchColumn()
];

// Recent bookings for this company
$recentBookings = $db->query("
    SELECT b.*, s.from_city_id, s.to_city_id, s.travel_date, s.departure_time,
           bus.bus_number, c1.name as from_city, c2.name as to_city
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE bus.bus_company_id = $company_id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            transition: transform 0.3s ease;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .content {
            background: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 content p-0">
                <?php include 'includes/header.php'; ?>
                
                <main class="p-4">
                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $stats['total_bookings']; ?></h3>
                                        <p class="mb-0">Total Bookings</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-ticket-alt stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3>ETB <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                        <p class="mb-0">Total Revenue</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $stats['active_buses']; ?></h3>
                                        <p class="mb-0">Active Buses</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-bus stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $stats['today_bookings']; ?></h3>
                                        <p class="mb-0">Today's Bookings</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-day stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $stats['pending_payments']; ?></h3>
                                        <p class="mb-0">Pending Payments</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card bg-secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3><?php echo $stats['active_schedules']; ?></h3>
                                        <p class="mb-0">Active Schedules</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar-alt stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings for <?php echo $_SESSION['company_name']; ?></h5>
                        <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentBookings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <h5>No Bookings Yet</h5>
                                <p class="text-muted">When customers book your buses, they will appear here.</p>
                                <a href="schedules.php" class="btn btn-primary">Create Your First Schedule</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Passenger</th>
                                            <th>Route</th>
                                            <th>Date & Time</th>
                                            <th>Seat</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $booking['passenger_full_name']; ?></strong>
                                                <br><small class="text-muted"><?php echo $booking['passenger_phone']; ?></small>
                                            </td>
                                            <td><?php echo $booking['from_city']; ?> → <?php echo $booking['to_city']; ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?>
                                                <br><small><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></small>
                                            </td>
                                            <td>#<?php echo $booking['seat_number']; ?></td>
                                            <td class="text-success">ETB <?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>