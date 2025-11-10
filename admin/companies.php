<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Bus Companies - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $company_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            // Check if company has active buses or schedules
            $checkQuery = "SELECT COUNT(*) FROM buses WHERE bus_company_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$company_id]);
            $busCount = $checkStmt->fetchColumn();
            
            if ($busCount > 0) {
                $_SESSION['error'] = "Cannot delete company with active buses";
            } else {
                $deleteQuery = "DELETE FROM bus_companies WHERE id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$company_id]);
                $_SESSION['success'] = "Company deleted successfully";
            }
        } elseif ($action == 'toggle') {
            $updateQuery = "UPDATE bus_companies SET is_active = NOT is_active WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$company_id]);
            $_SESSION['success'] = "Company status updated";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('companies.php');
}

// Handle form submission
if ($_POST) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $company_name = sanitizeInput($_POST['company_name']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $contact_phone = sanitizeInput($_POST['contact_phone']);
        $description = sanitizeInput($_POST['description']);
        
        if ($id > 0) {
            // Update existing company
            $query = "UPDATE bus_companies SET company_name = ?, contact_person_name = ?, 
                     contact_phone = ?, description = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$company_name, $contact_person, $contact_phone, $description, $id]);
            $_SESSION['success'] = "Company updated successfully";
        } else {
            // Create new company
            $query = "INSERT INTO bus_companies (company_name, contact_person_name, contact_phone, description) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$company_name, $contact_person, $contact_phone, $description]);
            $company_id = $db->lastInsertId();
            
            // Create default partner admin user
            $email = strtolower(str_replace(' ', '', $company_name)) . '@tbus.et';
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            
            $userQuery = "INSERT INTO users (email, password_hash, user_type, bus_company_id, full_name) 
                         VALUES (?, ?, 'partner_admin', ?, ?)";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute([$email, $password_hash, $company_id, $contact_person]);
            
            $_SESSION['success'] = "Company created successfully with default login: " . $email;
        }
        
        redirect('companies.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get all companies with stats
$companies = $db->query("
    SELECT bc.*, 
           (SELECT COUNT(*) FROM buses b WHERE b.bus_company_id = bc.id AND b.is_active = TRUE) as active_buses,
           (SELECT COUNT(*) FROM buses b WHERE b.bus_company_id = bc.id) as total_buses,
           (SELECT COUNT(*) FROM schedules s 
            JOIN buses b ON s.bus_id = b.id 
            WHERE b.bus_company_id = bc.id AND s.travel_date >= CURDATE()) as upcoming_schedules,
           (SELECT COUNT(*) FROM bookings bk 
            JOIN schedules s ON bk.schedule_id = s.id 
            JOIN buses b ON s.bus_id = b.id 
            WHERE b.bus_company_id = bc.id AND bk.payment_status = 'paid') as total_bookings
    FROM bus_companies bc
    ORDER BY bc.company_name
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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Bus Companies</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal">
                            <i class="fas fa-plus"></i> Add New Company
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

                <!-- Companies Grid -->
                <div class="row">
                    <?php foreach ($companies as $company): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo $company['company_name']; ?></h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input company-toggle" type="checkbox" 
                                           data-company-id="<?php echo $company['id']; ?>"
                                           <?php echo $company['is_active'] ? 'checked' : ''; ?>>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Contact Person</small>
                                    <div class="fw-bold"><?php echo $company['contact_person_name']; ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Contact Phone</small>
                                    <div><?php echo $company['contact_phone']; ?></div>
                                </div>
                                
                                <?php if ($company['description']): ?>
                                <div class="mb-3">
                                    <small class="text-muted">Description</small>
                                    <div class="small"><?php echo $company['description']; ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="fw-bold text-primary"><?php echo $company['active_buses']; ?></div>
                                        <small class="text-muted">Active Buses</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-success"><?php echo $company['upcoming_schedules']; ?></div>
                                        <small class="text-muted">Upcoming Trips</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-info"><?php echo $company['total_bookings']; ?></div>
                                        <small class="text-muted">Total Bookings</small>
                                    </div>
                                </div>
                                
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-outline-primary btn-sm edit-company"
                                            data-company='<?php echo json_encode($company); ?>'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="companies.php?action=delete&id=<?php echo $company['id']; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this company? This will also remove all associated users.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Company Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="companyForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Bus Company</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="companyId">
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" required>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" name="contact_phone" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Edit company
        $('.edit-company').click(function() {
            const company = $(this).data('company');
            $('#modalTitle').text('Edit Bus Company');
            $('#companyId').val(company.id);
            $('input[name="company_name"]').val(company.company_name);
            $('input[name="contact_person"]').val(company.contact_person_name);
            $('input[name="contact_phone"]').val(company.contact_phone);
            $('textarea[name="description"]').val(company.description);
            
            $('#companyModal').modal('show');
        });

        // Toggle company status
        $('.company-toggle').change(function() {
            const companyId = $(this).data('company-id');
            const isActive = $(this).is(':checked');
            
            $.post('ajax/toggle-company.php', {
                id: companyId,
                is_active: isActive
            }, function(response) {
                if (!response.success) {
                    alert('Error updating company status');
                    location.reload();
                }
            }).fail(function() {
                alert('Error updating company status');
                location.reload();
            });
        });

        // Reset form when modal is hidden
        $('#companyModal').on('hidden.bs.modal', function() {
            $('#modalTitle').text('Add New Bus Company');
            $('#companyForm')[0].reset();
            $('#companyId').val('');
        });
    </script>
</body>
</html>