<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Reports & Analytics - T BUS";

$database = new Database();
$db = $database->getConnection();

// Date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get report data
$revenue_data = $db->prepare("
    SELECT DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as bookings
    FROM bookings 
    WHERE payment_status = 'paid' AND created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$revenue_data->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$revenue_stats = $revenue_data->fetchAll(PDO::FETCH_ASSOC);

// Top routes
$top_routes = $db->prepare("
    SELECT c1.name as from_city, c2.name as to_city, COUNT(*) as bookings, SUM(bk.total_price) as revenue
    FROM bookings bk
    JOIN schedules s ON bk.schedule_id = s.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE bk.payment_status = 'paid' AND bk.created_at BETWEEN ? AND ?
    GROUP BY s.from_city_id, s.to_city_id
    ORDER BY revenue DESC
    LIMIT 10
");
$top_routes->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$popular_routes = $top_routes->fetchAll(PDO::FETCH_ASSOC);

// Company performance
$company_performance = $db->prepare("
    SELECT bc.company_name, COUNT(*) as bookings, SUM(bk.total_price) as revenue
    FROM bookings bk
    JOIN schedules s ON bk.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_companies bc ON b.bus_company_id = bc.id
    WHERE bk.payment_status = 'paid' AND bk.created_at BETWEEN ? AND ?
    GROUP BY bc.id
    ORDER BY revenue DESC
");
$company_performance->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$company_stats = $company_performance->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$summary = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue,
        AVG(CASE WHEN payment_status = 'paid' THEN total_price ELSE NULL END) as avg_booking_value,
        COUNT(DISTINCT passenger_phone) as unique_customers
    FROM bookings 
    WHERE created_at BETWEEN ? AND ?
");
$summary->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$summary_stats = $summary->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports & Analytics</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo number_format($summary_stats['total_bookings']); ?></h4>
                                        <p class="mb-0">Total Bookings</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">ETB <?php echo number_format($summary_stats['total_revenue'], 2); ?></h4>
                                        <p class="mb-0">Total Revenue</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">ETB <?php echo number_format($summary_stats['avg_booking_value'], 2); ?></h4>
                                        <p class="mb-0">Avg. Booking Value</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?php echo number_format($summary_stats['unique_customers']); ?></h4>
                                        <p class="mb-0">Unique Customers</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Revenue & Bookings Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Performing Companies</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($company_stats as $company): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo $company['company_name']; ?></h6>
                                            <small class="text-muted"><?php echo $company['bookings']; ?> bookings</small>
                                        </div>
                                        <span class="badge bg-success rounded-pill">ETB <?php echo number_format($company['revenue'], 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Routes -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Most Popular Routes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Route</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                                <th>Avg. Revenue per Booking</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_routes as $route): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $route['from_city']; ?> → <?php echo $route['to_city']; ?></strong>
                                                </td>
                                                <td><?php echo number_format($route['bookings']); ?></td>
                                                <td class="text-success">ETB <?php echo number_format($route['revenue'], 2); ?></td>
                                                <td>ETB <?php echo number_format($route['revenue'] / $route['bookings'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, $revenue_stats)); ?>],
                datasets: [
                    {
                        label: 'Revenue (ETB)',
                        data: [<?php echo implode(',', array_column($revenue_stats, 'revenue')); ?>],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Bookings',
                        data: [<?php echo implode(',', array_column($revenue_stats, 'bookings')); ?>],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        yAxisID: 'y1',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (ETB)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Bookings'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>