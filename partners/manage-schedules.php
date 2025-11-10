<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isPartnerAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Schedules - Partner Portal";

$database = new Database();
$db = $database->getConnection();
$company_id = $_SESSION['bus_company_id'];

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
                $deleteQuery = "DELETE FROM schedules WHERE id = ? AND bus_id IN (SELECT id FROM buses WHERE bus_company_id = ?)";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$schedule_id, $company_id]);
                $_SESSION['success'] = "Schedule deleted successfully";
            }
        } elseif ($action == 'toggle') {
            $updateQuery = "UPDATE schedules SET is_active = NOT is_active WHERE id = ? AND bus_id IN (SELECT id FROM buses WHERE bus_company_id = ?)";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$schedule_id, $company_id]);
            $_SESSION['success'] = "Schedule status updated";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('manage-schedules.php');
}

// Handle form submission
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
        
        // Verify bus belongs to company
        $busCheck = $db->prepare("SELECT id FROM buses WHERE id = ? AND bus_company_id = ?");
        $busCheck->execute([$bus_id, $company_id]);
        if (!$busCheck->fetch()) {
            throw new Exception("Invalid bus selection");
        }

        if ($id > 0) {
            // Update existing schedule
            $query = "UPDATE schedules SET bus_id = ?, from_city_id = ?, to_city_id = ?, 
                     departure_time = ?, arrival_time = ?, price = ?, travel_date = ? 
                     WHERE id = ? AND bus_id IN (SELECT id FROM buses WHERE bus_company_id = ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_id, $from_city_id, $to_city_id, $departure_time, 
                          $arrival_time, $price, $travel_date, $id, $company_id]);
            $_SESSION['success'] = "Schedule updated successfully";
        } else {
            // Create new schedule
            $busQuery = "SELECT total_seats FROM buses WHERE id = ? AND bus_company_id = ?";
            $busStmt = $db->prepare($busQuery);
            $busStmt->execute([$bus_id, $company_id]);
            $bus = $busStmt->fetch(PDO::FETCH_ASSOC);
            
            $query = "INSERT INTO schedules (bus_id, from_city_id, to_city_id, departure_time, 
                     arrival_time, price, travel_date, available_seats) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_id, $from_city_id, $to_city_id, $departure_time, 
                          $arrival_time, $price, $travel_date, $bus['total_seats']]);
            $_SESSION['success'] = "Schedule created successfully";
        }
        
        redirect('manage-schedules.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get company schedules
$schedules = $db->query("
    SELECT s.*, bus.bus_number, bus.type, bus.total_seats,
           c1.name as from_city, c2.name as to_city,
           (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.booking_status != 'cancelled') as booking_count
    FROM schedules s
    JOIN buses bus ON s.bus_id = bus.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE bus.bus_company_id = $company_id
    ORDER BY s.travel_date DESC, s.departure_time DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get company buses and cities
$buses = $db->query("SELECT * FROM buses WHERE bus_company_id = $company_id AND is_active = TRUE ORDER BY bus_number")->fetchAll();
$cities = $db->query("SELECT * FROM cities ORDER BY name")->fetchAll();
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
                    <h1 class="h2">Manage Schedules</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                        <i class="fas fa-plus"></i> Add New Schedule
                    </button>
                </div>

                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Schedules Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Bus Schedules</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th>Date & Time</th>
                                        <th>Bus</th>
                                        <th>Price</th>
                                        <th>Bookings</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($schedules)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                No schedules found. <a href="#" data-bs-toggle="modal" data-bs-target="#scheduleModal">Create your first schedule</a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $schedule['from_city']; ?> → <?php echo $schedule['to_city']; ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($schedule['travel_date'])); ?><br>
                                                <small><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></small>
                                            </td>
                                            <td><?php echo $schedule['bus_number']; ?> (<?php echo ucfirst($schedule['type']); ?>)</td>
                                            <td class="text-success">ETB <?php echo number_format($schedule['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $schedule['booking_count']; ?> bookings</span><br>
                                                <small><?php echo $schedule['available_seats']; ?> seats available</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $schedule['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-schedule"
                                                            data-schedule='<?php echo json_encode($schedule); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="manage-schedules.php?action=toggle&id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-warning">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <a href="manage-schedules.php?action=delete&id=<?php echo $schedule['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure? This will delete the schedule.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="scheduleForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="scheduleId">
                        
                        <div class="mb-3">
                            <label class="form-label">Bus</label>
                            <select class="form-select" name="bus_id" required>
                                <option value="">Select Bus</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?php echo $bus['id']; ?>">
                                        <?php echo $bus['bus_number']; ?> (<?php echo ucfirst($bus['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row g-3">
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
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Travel Date</label>
                                <input type="date" class="form-control" name="travel_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price (ETB)</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Departure Time</label>
                                <input type="time" class="form-control" name="departure_time" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Arrival Time</label>
                                <input type="time" class="form-control" name="arrival_time" required>
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
        // Edit schedule
        $('.edit-schedule').click(function() {
            const schedule = $(this).data('schedule');
            $('#modalTitle').text('Edit Schedule');
            $('#scheduleId').val(schedule.id);
            $('select[name="bus_id"]').val(schedule.bus_id);
            $('select[name="from_city_id"]').val(schedule.from_city_id);
            $('select[name="to_city_id"]').val(schedule.to_city_id);
            $('input[name="travel_date"]').val(schedule.travel_date);
            $('input[name="price"]').val(schedule.price);
            $('input[name="departure_time"]').val(schedule.departure_time);
            $('input[name="arrival_time"]').val(schedule.arrival_time);
            
            $('#scheduleModal').modal('show');
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