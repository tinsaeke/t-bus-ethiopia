<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Super Admin Dashboard - T BUS";

// Dashboard statistics
$database = new Database();
$db = $database->getConnection();

// Get stats
$stats = [
    'total_bookings' => $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'total_revenue' => $db->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn(),
    'active_buses' => $db->query("SELECT COUNT(*) FROM buses WHERE is_active = TRUE")->fetchColumn(),
    'bus_companies' => $db->query("SELECT COUNT(*) FROM bus_companies WHERE is_active = TRUE")->fetchColumn(),
    'today_bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'pending_payments' => $db->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'pending'")->fetchColumn()
];

// Recent bookings
$recentBookings = $db->query("
    SELECT b.*, s.from_city_id, s.to_city_id, s.travel_date, s.departure_time,
           bc.company_name, c1.name as from_city, c2.name as to_city
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    JOIN bus_companies bc ON bus.bus_company_id = bc.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    ORDER BY b.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Revenue chart data (last 7 days)
$revenueData = $db->query("
    SELECT DATE(created_at) as date, SUM(total_price) as revenue
    FROM bookings 
    WHERE payment_status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
    <link href="../public/assets/css/style.css" rel="stylesheet">
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
        .sidebar {
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 0.75rem 1rem;
            margin: 0.2rem 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #34495e;
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        .navbar-brand {
            font-weight: bold;
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-bus"></i> T BUS
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-ticket-alt"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedules.php">
                                <i class="fas fa-calendar-alt"></i> Schedules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="companies.php">
                                <i class="fas fa-building"></i> Bus Companies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="buses.php">
                                <i class="fas fa-bus"></i> Buses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cities.php">
                                <i class="fas fa-city"></i> Cities
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="site-content.php">
                                <i class="fas fa-edit"></i> Site Content
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-warning" href="../includes/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content p-0">
                <!-- Top Navigation -->
                <nav class="navbar navbar-light bg-white border-bottom">
                    <div class="container-fluid">
                        <span class="navbar-brand">Super Admin Dashboard</span>
                        <div class="d-flex align-items-center">
                            <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="../includes/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid p-4">
                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3><?php echo $stats['total_bookings']; ?></h3>
                                            <p class="mb-0">Total Bookings</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-ticket-alt stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card bg-success">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3>ETB <?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                            <p class="mb-0">Total Revenue</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-money-bill-wave stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card bg-warning">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3><?php echo $stats['bus_companies']; ?></h3>
                                            <p class="mb-0">Bus Companies</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-building stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-info">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3><?php echo $stats['active_buses']; ?></h3>
                                            <p class="mb-0">Active Buses</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-bus stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-primary">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3><?php echo $stats['today_bookings']; ?></h3>
                                            <p class="mb-0">Today's Bookings</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-calendar-day stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-danger">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <h3><?php echo $stats['pending_payments']; ?></h3>
                                            <p class="mb-0">Pending Payments</p>
                                        </div>
                                        <div class="col-4 text-end">
                                            <i class="fas fa-clock stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Bookings -->
                    <div class="row g-4">
                        <!-- Revenue Chart -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Revenue Overview (Last 7 Days)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Bookings -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Bookings</h5>
                                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($booking['from_city']); ?> → <?php echo htmlspecialchars($booking['to_city']); ?></h6>
                                                    <small class="text-muted">
                                                        <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                            <?php echo strtoupper(htmlspecialchars($booking['payment_status'])); ?>
                                                        </span>
                                                    </small>
                                                </div>
                                                <p class="mb-1">
                                                    <small><?php echo htmlspecialchars($booking['passenger_full_name']); ?></small>
                                                </p>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?> 
                                                    at <?php echo date('h:i A', strtotime($booking['departure_time'])); ?>
                                                </small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, $revenueData)); ?>],
                datasets: [{
                    label: 'Daily Revenue (ETB)',
                    data: [<?php echo implode(',', array_column($revenueData, 'revenue')); ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Auto refresh dashboard every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>