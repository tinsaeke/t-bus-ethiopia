<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Schedules - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            // Check if schedule has bookings
            $checkQuery = "SELECT COUNT(*) FROM bookings WHERE schedule_id = ? AND booking_status != 'cancelled'";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$schedule_id]);
            $bookingCount = $checkStmt->fetchColumn();
            
            if ($bookingCount > 0) {
                $_SESSION['error'] = "Cannot delete schedule with active bookings";
            } else {
                $deleteQuery = "DELETE FROM schedules WHERE id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$schedule_id]);
                $_SESSION['success'] = "Schedule deleted successfully";
            }
        } elseif ($action == 'toggle') {
            $updateQuery = "UPDATE schedules SET is_active = NOT is_active WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$schedule_id]);
            $_SESSION['success'] = "Schedule status updated";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('schedules.php');
}

// Handle form submission for new/edit schedule
if ($_POST) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $bus_id = intval($_POST['bus_id']);
        $from_city_id = intval($_POST['from_city_id']);
        $to_city_id = intval($_POST['to_city_id']);
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $price = floatval($_POST['price']);
        $travel_date = $_POST['travel_date'];
        
        if ($id > 0) {
            // Update existing schedule
            $query = "UPDATE schedules SET bus_id = ?, from_city_id = ?, to_city_id = ?, 
                     departure_time = ?, arrival_time = ?, price = ?, travel_date = ? 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_id, $from_city_id, $to_city_id, $departure_time, 
                          $arrival_time, $price, $travel_date, $id]);
            $_SESSION['success'] = "Schedule updated successfully";
        } else {
            // Create new schedule
            // Get bus capacity
            $busQuery = "SELECT total_seats FROM buses WHERE id = ?";
            $busStmt = $db->prepare($busQuery);
            $busStmt->execute([$bus_id]);
            $bus = $busStmt->fetch(PDO::FETCH_ASSOC);
            
            $query = "INSERT INTO schedules (bus_id, from_city_id, to_city_id, departure_time, 
                     arrival_time, price, travel_date, available_seats) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_id, $from_city_id, $to_city_id, $departure_time, 
                          $arrival_time, $price, $travel_date, $bus['total_seats']]);
            $_SESSION['success'] = "Schedule created successfully";
        }
        
        redirect('schedules.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get schedules with filters
$company_filter = isset($_GET['company']) ? intval($_GET['company']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$route_filter = isset($_GET['route']) ? $_GET['route'] : '';

$whereConditions = [];
$params = [];

if ($company_filter) {
    $whereConditions[] = "bus.bus_company_id = ?";
    $params[] = $company_filter;
}

if ($date_filter) {
    $whereConditions[] = "s.travel_date = ?";
    $params[] = $date_filter;
}

if ($route_filter) {
    $whereConditions[] = "(s.from_city_id = ? OR s.to_city_id = ?)";
    $route_parts = explode('-', $route_filter);
    $params[] = $route_parts[0];
    $params[] = $route_parts[1];
}

$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get schedules
$query = "
    SELECT s.*, bus.bus_number, bus.type, bus.total_seats, bc.company_name,
           c1.name as from_city, c2.name as to_city,
           (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.booking_status != 'cancelled') as booking_count
    FROM schedules s
    JOIN buses bus ON s.bus_id = bus.id
    JOIN bus_companies bc ON bus.bus_company_id = bc.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    $whereClause
    ORDER BY s.travel_date DESC, s.departure_time DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get data for forms
$companies = $db->query("SELECT * FROM bus_companies WHERE is_active = TRUE ORDER BY company_name")->fetchAll();
$buses = $db->query("SELECT b.*, bc.company_name FROM buses b JOIN bus_companies bc ON b.bus_company_id = bc.id WHERE b.is_active = TRUE ORDER BY bc.company_name, b.bus_number")->fetchAll();
$cities = $db->query("SELECT * FROM cities ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <style>
        .schedule-card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .occupancy-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .occupancy-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.5s ease;
        }
        .low-occupancy { background: linear-gradient(90deg, #28a745, #20c997); }
        .medium-occupancy { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .high-occupancy { background: linear-gradient(90deg, #dc3545, #e83e8c); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Schedules</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="fas fa-plus"></i> Add New Schedule
                        </button>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Bus Company</label>
                                <select class="form-select" name="company">
                                    <option value="">All Companies</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo $company_filter == $company['id'] ? 'selected' : ''; ?>>
                                            <?php echo $company['company_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Travel Date</label>
                                <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Route</label>
                                <select class="form-select" name="route">
                                    <option value="">All Routes</option>
                                    <?php 
                                    $routes = $db->query("
                                        SELECT DISTINCT s.from_city_id, s.to_city_id, c1.name as from_city, c2.name as to_city
                                        FROM schedules s
                                        JOIN cities c1 ON s.from_city_id = c1.id
                                        JOIN cities c2 ON s.to_city_id = c2.id
                                        ORDER BY c1.name, c2.name
                                    ")->fetchAll();
                                    foreach ($routes as $route): ?>
                                        <option value="<?php echo $route['from_city_id'] . '-' . $route['to_city_id']; ?>" 
                                                <?php echo $route_filter == $route['from_city_id'] . '-' . $route['to_city_id'] ? 'selected' : ''; ?>>
                                            <?php echo $route['from_city']; ?> → <?php echo $route['to_city']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100 me-2">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="schedules.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Schedules Grid -->
                <div class="row">
                    <?php if (empty($schedules)): ?>
                        <div class="col-12">
                            <div class="card text-center py-5">
                                <div class="card-body">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h4>No schedules found</h4>
                                    <p class="text-muted">Try adjusting your filters or create a new schedule</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                        <i class="fas fa-plus"></i> Add New Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): 
                            $occupancy = ($schedule['booking_count'] / $schedule['total_seats']) * 100;
                            $occupancy_class = $occupancy < 50 ? 'low-occupancy' : ($occupancy < 80 ? 'medium-occupancy' : 'high-occupancy');
                        ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card schedule-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo $schedule['from_city']; ?> → <?php echo $schedule['to_city']; ?></h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input schedule-toggle" type="checkbox" 
                                               data-schedule-id="<?php echo $schedule['id']; ?>"
                                               <?php echo $schedule['is_active'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Date</small>
                                            <div class="fw-bold"><?php echo date('M d, Y', strtotime($schedule['travel_date'])); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Time</small>
                                            <div class="fw-bold">
                                                <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Bus</small>
                                            <div><?php echo $schedule['company_name']; ?></div>
                                            <small class="text-muted"><?php echo strtoupper($schedule['bus_number']); ?> • <?php echo ucfirst($schedule['type']); ?></small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Price</small>
                                            <div class="fw-bold text-success">ETB <?php echo number_format($schedule['price'], 2); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Occupancy</small>
                                            <small><?php echo $schedule['booking_count']; ?>/<?php echo $schedule['total_seats']; ?> seats</small>
                                        </div>
                                        <div class="occupancy-bar">
                                            <div class="occupancy-fill <?php echo $occupancy_class; ?>" 
                                                 style="width: <?php echo $occupancy; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?php echo $schedule['available_seats'] > 10 ? 'success' : ($schedule['available_seats'] > 0 ? 'warning' : 'danger'); ?>">
                                            <?php echo $schedule['available_seats']; ?> seats available
                                        </span>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-schedule"
                                                    data-schedule='<?php echo json_encode($schedule); ?>'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="schedules.php?action=delete&id=<?php echo $schedule['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="scheduleForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="scheduleId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bus Company</label>
                                <select class="form-select" id="companySelect" required>
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>"><?php echo $company['company_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bus</label>
                                <select class="form-select" name="bus_id" id="busSelect" required>
                                    <option value="">Select Bus</option>
                                    <?php foreach ($buses as $bus): ?>
                                        <option value="<?php echo $bus['id']; ?>" data-company="<?php echo $bus['bus_company_id']; ?>">
                                            <?php echo $bus['company_name']; ?> - <?php echo $bus['bus_number']; ?> (<?php echo ucfirst($bus['type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">From City</label>
                                <select class="form-select" name="from_city_id" required>
                                    <option value="">Select City</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city['id']; ?>"><?php echo $city['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To City</label>
                                <select class="form-select" name="to_city_id" required>
                                    <option value="">Select City</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city['id']; ?>"><?php echo $city['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Travel Date</label>
                                <input type="date" class="form-control" name="travel_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Departure Time</label>
                                <input type="time" class="form-control" name="departure_time" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Arrival Time</label>
                                <input type="time" class="form-control" name="arrival_time" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price (ETB)</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Filter buses by company
        $('#companySelect').change(function() {
            const companyId = $(this).val();
            $('#busSelect option').show();
            if (companyId) {
                $('#busSelect option').not('[data-company="' + companyId + '"]').hide();
                $('#busSelect option[value=""]').show();
            }
            $('#busSelect').val('');
        });

        // Edit schedule
        $('.edit-schedule').click(function() {
            const schedule = $(this).data('schedule');
            $('#modalTitle').text('Edit Schedule');
            $('#scheduleId').val(schedule.id);
            $('#companySelect').val(schedule.bus_company_id).trigger('change');
            setTimeout(() => {
                $('#busSelect').val(schedule.bus_id);
            }, 100);
            $('select[name="from_city_id"]').val(schedule.from_city_id);
            $('select[name="to_city_id"]').val(schedule.to_city_id);
            $('input[name="travel_date"]').val(schedule.travel_date);
            $('input[name="departure_time"]').val(schedule.departure_time);
            $('input[name="arrival_time"]').val(schedule.arrival_time);
            $('input[name="price"]').val(schedule.price);
            
            $('#scheduleModal').modal('show');
        });

        // Toggle schedule status
        $('.schedule-toggle').change(function() {
            const scheduleId = $(this).data('schedule-id');
            const isActive = $(this).is(':checked');
            
            $.post('ajax/toggle-schedule.php', {
                id: scheduleId,
                is_active: isActive
            }, function(response) {
                if (!response.success) {
                    alert('Error updating schedule status');
                    location.reload();
                }
            }).fail(function() {
                alert('Error updating schedule status');
                location.reload();
            });
        });

        // Reset form when modal is hidden
        $('#scheduleModal').on('hidden.bs.modal', function() {
            $('#modalTitle').text('Add New Schedule');
            $('#scheduleForm')[0].reset();
            $('#scheduleId').val('');
        });
    </script>
</body>
</html>