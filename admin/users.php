<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$pageTitle = "Manage Users - T BUS";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        if ($action == 'delete') {
            // Prevent deleting own account
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = "You cannot delete your own account";
            } else {
                $deleteQuery = "DELETE FROM users WHERE id = ?";
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User deleted successfully";
            }
        } elseif ($action == 'toggle') {
            // Prevent deactivating own account
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = "You cannot deactivate your own account";
            } else {
                $updateQuery = "UPDATE users SET is_active = NOT is_active WHERE id = ?";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User status updated";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    redirect('users.php');
}

// Handle form submission
if ($_POST) {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $user_type = sanitizeInput($_POST['user_type']);
        $bus_company_id = $user_type == 'partner_admin' ? intval($_POST['bus_company_id']) : null;
        $password = $_POST['password'];
        
        if ($id > 0) {
            // Update existing user
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET email = ?, full_name = ?, user_type = ?, bus_company_id = ?, password_hash = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$email, $full_name, $user_type, $bus_company_id, $password_hash, $id]);
            } else {
                $query = "UPDATE users SET email = ?, full_name = ?, user_type = ?, bus_company_id = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$email, $full_name, $user_type, $bus_company_id, $id]);
            }
            $_SESSION['success'] = "User updated successfully";
        } else {
            // Create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (email, password_hash, full_name, user_type, bus_company_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$email, $password_hash, $full_name, $user_type, $bus_company_id]);
            $_SESSION['success'] = "User created successfully";
        }
        
        redirect('users.php');
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get all users with company info
$users = $db->query("
    SELECT u.*, bc.company_name
    FROM users u
    LEFT JOIN bus_companies bc ON u.bus_company_id = bc.id
    ORDER BY u.user_type, u.email
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
                    <h1 class="h2">Manage Users</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="fas fa-plus"></i> Add New User
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

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Company</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                No users found. <a href="#" data-bs-toggle="modal" data-bs-target="#userModal">Add your first user</a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo $user['full_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $user['email']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['user_type'] == 'super_admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($user['user_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['company_name'] ?: 'N/A'; ?></td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-user"
                                                            data-user='<?php echo json_encode($user); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="users.php?action=toggle&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-outline-warning">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure? This will permanently delete the user.')">
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="userForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">User Type</label>
                                <select class="form-select" name="user_type" id="userType" required>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="partner_admin">Partner Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bus Company</label>
                                <select class="form-select" name="bus_company_id" id="busCompanySelect">
                                    <option value="">Select Company</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>"><?php echo $company['company_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" id="passwordField" required>
                                <small class="text-muted" id="passwordHelp">Leave blank to keep current password when editing</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Show/hide company selection based on user type
        $('#userType').change(function() {
            if ($(this).val() === 'partner_admin') {
                $('#busCompanySelect').prop('required', true).closest('.col-md-6').show();
            } else {
                $('#busCompanySelect').prop('required', false).closest('.col-md-6').hide();
            }
        });

        // Edit user
        $('.edit-user').click(function() {
            const user = $(this).data('user');
            $('#modalTitle').text('Edit User');
            $('#userId').val(user.id);
            $('input[name="full_name"]').val(user.full_name);
            $('input[name="email"]').val(user.email);
            $('select[name="user_type"]').val(user.user_type).trigger('change');
            
            if (user.bus_company_id) {
                $('select[name="bus_company_id"]').val(user.bus_company_id);
            }
            
            // Make password optional for edits
            $('#passwordField').prop('required', false);
            $('#passwordHelp').text('Leave blank to keep current password');
            
            $('#userModal').modal('show');
        });

        // Reset form when modal is hidden
        $('#userModal').on('hidden.bs.modal', function() {
            $('#modalTitle').text('Add New User');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#passwordField').prop('required', true);
            $('#passwordHelp').text('Enter a password for the new user');
            $('#userType').trigger('change');
        });

        // Initialize on page load
        $('#userType').trigger('change');
    </script>
</body>
</html>