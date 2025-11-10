<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Cities - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $city_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            // Check if city is used in schedules
            $checkQuery = "SELECT COUNT(*) FROM schedules WHERE from_city_id = ? OR to_city_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$city_id, $city_id]);
            $usageCount = $checkStmt->fetchColumn();
            
            if ($usageCount > 0) {
                $_SESSION['error'] = "Cannot delete city that is used in schedules";
            } else {
                $deleteQuery = "DELETE FROM cities WHERE id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$city_id]);
                $_SESSION['success'] = "City deleted successfully";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('cities.php');
}

// Handle form submission
if ($_POST) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = sanitizeInput($_POST['name']);
        
        if ($id > 0) {
            // Update existing city
            $query = "UPDATE cities SET name = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $id]);
            $_SESSION['success'] = "City updated successfully";
        } else {
            // Create new city
            $query = "INSERT INTO cities (name) VALUES (?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name]);
            $_SESSION['success'] = "City created successfully";
        }
        
        redirect('cities.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get all cities
$cities = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM schedules WHERE from_city_id = c.id OR to_city_id = c.id) as usage_count
    FROM cities c
    ORDER BY c.name
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
                    <h1 class="h2">Manage Cities</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cityModal">
                        <i class="fas fa-plus"></i> Add New City
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

                <!-- Cities Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Cities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>City Name</th>
                                        <th>Usage Count</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cities)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                No cities found. <a href="#" data-bs-toggle="modal" data-bs-target="#cityModal">Add your first city</a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cities as $city): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $city['name']; ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $city['usage_count']; ?> schedules</span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($city['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-city"
                                                            data-city='<?php echo json_encode($city); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="cities.php?action=delete&id=<?php echo $city['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure? This will delete the city permanently.')">
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

    <!-- City Modal -->
    <div class="modal fade" id="cityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="cityForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New City</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="cityId">
                        
                        <div class="mb-3">
                            <label class="form-label">City Name</label>
                            <input type="text" class="form-control" name="name" required 
                                   placeholder="e.g., Addis Ababa">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save City</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Edit city
        $('.edit-city').click(function() {
            const city = $(this).data('city');
            $('#modalTitle').text('Edit City');
            $('#cityId').val(city.id);
            $('input[name="name"]').val(city.name);
            $('#cityModal').modal('show');
        });

        // Reset form when modal is hidden
        $('#cityModal').on('hidden.bs.modal', function() {
            $('#modalTitle').text('Add New City');
            $('#cityForm')[0].reset();
            $('#cityId').val('');
        });
    </script>
</body>
</html>