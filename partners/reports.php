<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isPartnerAdmin()) {
    redirect('login.php');
}

$pageTitle = "Reports - Partner Portal";

$database = new Database();
$db = $database->getConnection();
$company_id = $_SESSION['bus_company_id'];

// Date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get company report data
$revenue_data = $db->prepare("
    SELECT DATE(b.created_at) as date, SUM(b.total_price) as revenue, COUNT(*) as bookings
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    WHERE bus.bus_company_id = ? AND b.payment_status = 'paid' AND b.created_at BETWEEN ? AND ?
    GROUP BY DATE(b.created_at)
    ORDER BY date
");
$revenue_data->execute([$company_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$revenue_stats = $revenue_data->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$summary = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as total_revenue,
        AVG(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE NULL END) as avg_booking_value
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bus ON s.bus_id = bus.id
    WHERE bus.bus_company_id = ? AND b.created_at BETWEEN ? AND ?
");
$summary->execute([$company_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$summary_stats = $summary->fetch(PDO::FETCH_ASSOC);

// Popular routes
$popular_routes = $db->prepare("
    SELECT c1.name as from_city, c2.name as to_city, COUNT(*) as booking_count
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    JOIN buses bus ON s.bus_id = bus.id
    WHERE bus.bus_company_id = ? AND b.created_at BETWEEN ? AND ?
    GROUP BY s.from_city_id, s.to_city_id
    ORDER BY booking_count DESC
    LIMIT 5
");
$popular_routes->execute([$company_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$top_routes = $popular_routes->fetchAll(PDO::FETCH_ASSOC);
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
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Date Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $end_date; ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter Results
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Revenue</h5>
                                        <h3 class="card-text">ETB <?php echo number_format($summary_stats['total_revenue'] ?? 0, 2); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Bookings</h5>
                                        <h3 class="card-text"><?php echo number_format($summary_stats['total_bookings'] ?? 0); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Avg. Booking Value</h5>
                                        <h3 class="card-text">ETB <?php echo number_format($summary_stats['avg_booking_value'] ?? 0, 2); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Revenue & Bookings Trend</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Top Routes</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="routesChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Report Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Daily Performance Report</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="reportTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Bookings</th>
                                        <th>Revenue</th>
                                        <th>Avg. Revenue per Booking</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($revenue_stats)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No data available for the selected period</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($revenue_stats as $stat): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($stat['date'])); ?></td>
                                                <td><?php echo $stat['bookings']; ?></td>
                                                <td>ETB <?php echo number_format($stat['revenue'], 2); ?></td>
                                                <td>ETB <?php echo number_format($stat['revenue'] / $stat['bookings'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($revenue_stats)): ?>
                                <tfoot>
                                    <tr class="table-primary">
                                        <td><strong>Total</strong></td>
                                        <td><strong><?php echo array_sum(array_column($revenue_stats, 'bookings')); ?></strong></td>
                                        <td><strong>ETB <?php echo number_format(array_sum(array_column($revenue_stats, 'revenue')), 2); ?></strong></td>
                                        <td><strong>ETB <?php echo number_format(array_sum(array_column($revenue_stats, 'revenue')) / array_sum(array_column($revenue_stats, 'bookings')), 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($stat) { return "'" . date('M j', strtotime($stat['date'])) . "'"; }, $revenue_stats)); ?>],
                datasets: [{
                    label: 'Revenue (ETB)',
                    data: [<?php echo implode(',', array_column($revenue_stats, 'revenue')); ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                }, {
                    label: 'Bookings',
                    data: [<?php echo implode(',', array_column($revenue_stats, 'bookings')); ?>],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
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

        // Routes Chart
        const routesCtx = document.getElementById('routesChart').getContext('2d');
        const routesChart = new Chart(routesCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($route) { return "'" . $route['from_city'] . ' to ' . $route['to_city'] . "'"; }, $top_routes)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($top_routes, 'booking_count')); ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        function exportToExcel() {
            // Simple CSV export implementation
            const table = document.getElementById('reportTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(','));
            }

            const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "bus_report_<?php echo $start_date . '_to_' . $end_date; ?>.csv");
            document.body.appendChild(link);
            link.click();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>