<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isPartnerAdmin()) {
    redirect('login.php');
}

$pageTitle = "My Buses - Partner Portal";

$database = new Database();
$db = $database->getConnection();
$company_id = $_SESSION['bus_company_id'];

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
                $deleteQuery = "DELETE FROM buses WHERE id = ? AND bus_company_id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$bus_id, $company_id]);
                $_SESSION['success'] = "Bus deleted successfully";
            }
        } elseif ($action == 'toggle') {
            $updateQuery = "UPDATE buses SET is_active = NOT is_active WHERE id = ? AND bus_company_id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$bus_id, $company_id]);
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
        $bus_number = sanitizeInput($_POST['bus_number']);
        $type = sanitizeInput($_POST['type']);
        $total_seats = intval($_POST['total_seats']);
        $amenities = isset($_POST['amenities']) ? json_encode($_POST['amenities']) : json_encode([]);
        
        if ($id > 0) {
            // Update existing bus
            $query = "UPDATE buses SET bus_number = ?, type = ?, total_seats = ?, amenities = ? WHERE id = ? AND bus_company_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$bus_number, $type, $total_seats, $amenities, $id, $company_id]);
            $_SESSION['success'] = "Bus updated successfully";
        } else {
            // Create new bus
            $query = "INSERT INTO buses (bus_company_id, bus_number, type, total_seats, amenities) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$company_id, $bus_number, $type, $total_seats, $amenities]);
            $_SESSION['success'] = "Bus created successfully";
        }
        
        redirect('buses.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get company buses
$buses = $db->query("
    SELECT b.*,
           (SELECT COUNT(*) FROM schedules s WHERE s.bus_id = b.id AND s.travel_date >= CURDATE()) as active_schedules
    FROM buses b
    WHERE b.bus_company_id = $company_id
    ORDER BY b.bus_number
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
                    <h1 class="h2">My Buses</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                        <i class="fas fa-plus"></i> Add New Bus
                    </button>
                </div>

                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Buses Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Company's Buses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($buses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bus fa-3x text-muted mb-3"></i>
                                <h5>No Buses Found</h5>
                                <p class="text-muted">Add your first bus to start creating schedules.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal">
                                    <i class="fas fa-plus"></i> Add Your First Bus
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Bus Number</th>
                                            <th>Type</th>
                                            <th>Seats</th>
                                            <th>Amenities</th>
                                            <th>Active Schedules</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($buses as $bus): 
                                            $amenities = json_decode($bus['amenities'], true) ?: [];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $bus['bus_number']; ?></strong>
                                            </td>
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
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bus Modal -->
    <div class="modal fade" id="busModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="busForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Bus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="busId">
                        
                        <div class="mb-3">
                            <label class="form-label">Bus Number</label>
                            <input type="text" class="form-control" name="bus_number" required 
                                   placeholder="e.g., SEL-001">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bus Type</label>
                            <select class="form-select" name="type" required>
                                <option value="standard">Standard</option>
                                <option value="vip">VIP</option>
                                <option value="business">Business</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Total Seats</label>
                            <input type="number" class="form-control" name="total_seats" min="1" max="100" 
                                   value="45" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amenities</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="wifi">
                                        <label class="form-check-label" for="wifi">WiFi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="ac" checked>
                                        <label class="form-check-label" for="ac">Air Conditioning</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="charging_port" id="charging" checked>
                                        <label class="form-check-label" for="charging">Charging Ports</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="toilet" id="toilet">
                                        <label class="form-check-label" for="toilet">Toilet</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="tv" id="tv">
                                        <label class="form-check-label" for="tv">TV</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="snacks" id="snacks">
                                        <label class="form-check-label" for="snacks">Snacks</label>
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