<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Buses - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bus_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            // Check if bus has active schedules
            $checkQuery = "SELECT COUNT(*) FROM schedules WHERE bus_id = ? AND travel_date >= CURDATE()";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$bus_id]);
            $scheduleCount = $checkStmt->fetchColumn();
            
            if ($scheduleCount > 0) {
                $_SESSION['error'] = "Cannot delete bus with active schedules";
            } else {
                $deleteQuery = "DELETE FROM buses WHERE id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$bus_id]);
                $_SESSION['success'] = "Bus deleted successfully";
            }
        } elseif ($action == 'toggle') {
            $updateQuery = "UPDATE buses SET is_active = NOT is_active WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$bus_id]);
            $_SESSION['success'] = "Bus status updated";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('buses.php');
}

// Handle form submission
if ($_POST) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $bus_company_id = intval($_POST['bus_company_id']);
        $bus_number = sanitizeInput($_POST['bus_number']);
        $type = sanitizeInput($_POST['type']);
        $total_seats = intval($_POST['total_seats']);
        $amenities = isset($_POST['amenities']) ? json_encode($_POST['amenities']) : json_encode([]);
        
        if ($id > 0) {
            // Update existing bus
            $query = "UPDATE buses SET bus_company_id = ?, bus_number = ?, type = ?, total_seats = ?, amenities = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_company_id, $bus_number, $type, $total_seats, $amenities, $id]);
            $_SESSION['success'] = "Bus updated successfully";
        } else {
            // Create new bus
            $query = "INSERT INTO buses (bus_company_id, bus_number, type, total_seats, amenities) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_company_id, $bus_number, $type, $total_seats, $amenities]);
            $_SESSION['success'] = "Bus created successfully";
        }
        
        redirect('buses.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get all buses with company info
$buses = $db->query("
    SELECT b.*, bc.company_name,
           (SELECT COUNT(*) FROM schedules s WHERE s.bus_id = b.id AND s.travel_date >= CURDATE()) as active_schedules,
           (SELECT COUNT(*) FROM bookings bk 
            JOIN schedules s ON bk.schedule_id = s.id 
            WHERE s.bus_id = b.id AND bk.booking_status = 'confirmed') as total_bookings
    FROM buses b
    JOIN bus_companies bc ON b.bus_company_id = bc.id
    ORDER BY bc.company_name, b.bus_number
")->fetchAll(PDO::FETCH_ASSOC);

// Get companies for dropdown
$companies = $db->query("SELECT * FROM bus_companies WHERE is_active = TRUE ORDER BY company_name")->fetchAll();
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
                    <h1 class="h2">Manage Buses</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                        <i class="fas fa-plus"></i> Add New Bus
                    </button>
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

                <!-- Buses Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Buses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Bus Number</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Seats</th>
                                        <th>Amenities</th>
                                        <th>Active Schedules</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($buses)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                No buses found. <a href="#" data-bs-toggle="modal" data-bs-target="#busModal">Add your first bus</a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($buses as $bus): 
                                            $amenities = json_decode($bus['amenities'], true) ?: [];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $bus['bus_number']; ?></strong>
                                            </td>
                                            <td><?php echo $bus['company_name']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $bus['type'] == 'vip' ? 'warning' : ($bus['type'] == 'business' ? 'info' : 'secondary'); ?>">
                                                    <?php echo strtoupper($bus['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $bus['total_seats']; ?> seats</td>
                                            <td>
                                                <?php if (!empty($amenities)): ?>
                                                    <div class="amenity-icons">
                                                        <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                                                            <span class="badge bg-light text-dark me-1" title="<?php echo ucfirst($amenity); ?>">
                                                                <?php 
                                                                $icons = [
                                                                    'wifi' => 'wifi',
                                                                    'ac' => 'snowflake',
                                                                    'charging_port' => 'plug',
                                                                    'toilet' => 'restroom',
                                                                    'tv' => 'tv',
                                                                    'snacks' => 'cookie'
                                                                ];
                                                                $icon = $icons[$amenity] ?? 'check';
                                                                ?>
                                                                <i class="fas fa-<?php echo $icon; ?>"></i>
                                                            </span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($amenities) > 3): ?>
                                                            <span class="badge bg-light text-dark">+<?php echo count($amenities) - 3; ?> more</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No amenities</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $bus['active_schedules'] > 0 ? 'success' : 'secondary'; ?>">
                                                    <?php echo $bus['active_schedules']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $bus['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $bus['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-bus"
                                                            data-bus='<?php echo json_encode($bus); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="buses.php?action=toggle&id=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-outline-warning">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <a href="buses.php?action=delete&id=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure? This will permanently delete the bus.')">
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

    <!-- Bus Modal -->
    <div class="modal fade" id="busModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="busForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Bus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="busId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bus Company</label>
                                <select class="form-select" name="bus_company_id" required>
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>"><?php echo $company['company_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bus Number</label>
                                <input type="text" class="form-control" name="bus_number" required 
                                       placeholder="e.g., AA-1234">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bus Type</label>
                                <select class="form-select" name="type" required>
                                    <option value="standard">Standard</option>
                                    <option value="vip">VIP</option>
                                    <option value="business">Business</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Seats</label>
                                <input type="number" class="form-control" name="total_seats" min="1" max="100" 
                                       value="45" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Amenities</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="wifi">
                                            <label class="form-check-label" for="wifi">
                                                <i class="fas fa-wifi me-1"></i>WiFi
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="ac" checked>
                                            <label class="form-check-label" for="ac">
                                                <i class="fas fa-snowflake me-1"></i>Air Conditioning
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="charging_port" id="charging" checked>
                                            <label class="form-check-label" for="charging">
                                                <i class="fas fa-plug me-1"></i>Charging Ports
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="toilet" id="toilet">
                                            <label class="form-check-label" for="toilet">
                                                <i class="fas fa-restroom me-1"></i>Toilet
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="tv" id="tv">
                                            <label class="form-check-label" for="tv">
                                                <i class="fas fa-tv me-1"></i>TV
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="snacks" id="snacks">
                                            <label class="form-check-label" for="snacks">
                                                <i class="fas fa-cookie me-1"></i>Snacks
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Bus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Edit bus
        $('.edit-bus').click(function() {
            const bus = $(this).data('bus');
            $('#modalTitle').text('Edit Bus');
            $('#busId').val(bus.id);
            $('select[name="bus_company_id"]').val(bus.bus_company_id);
            $('input[name="bus_number"]').val(bus.bus_number);
            $('select[name="type"]').val(bus.type);
            $('input[name="total_seats"]').val(bus.total_seats);
            
            // Clear all checkboxes first
            $('input[name="amenities[]"]').prop('checked', false);
            
            // Check amenities
            const amenities = JSON.parse(bus.amenities || '[]');
            amenities.forEach(amenity => {
                $(`input[name="amenities[]"][value="${amenity}"]`).prop('checked', true);
            });
            
            $('#busModal').modal('show');
        });

        // Reset form when modal is hidden
        $('#busModal').on('hidden.bs.modal', function() {
            $('#modalTitle').text('Add New Bus');
            $('#busForm')[0].reset();
            $('#busId').val('');
            // Reset checkboxes to default
            $('input[name="amenities[]"][value="ac"]').prop('checked', true);
            $('input[name="amenities[]"][value="charging_port"]').prop('checked', true);
        });
    </script>
</body>
</html>