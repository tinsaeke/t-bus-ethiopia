<?php
require_once '../includes/auth.php';

if (isLoggedIn()) {
    if (isSuperAdmin()) {
        redirect('dashboard.php');
    } else {
        redirect('../partners/dashboard.php');
    }
}

$error = '';
if ($_POST) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Enable error logging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    if (loginUser($email, $password)) {
        if (isSuperAdmin()) {
            redirect('dashboard.php');
        } else {
            redirect('../partners/dashboard.php');
        }
    } else {
        $error = "Invalid email or password";
        
        // Debug info (remove in production)
        $debug_info = "";
        if (file_exists('../includes/auth.php')) {
            $debug_info .= "Auth file exists. ";
        }
        $debug_info .= "Tried: " . $email;
        error_log("Login failed: " . $debug_info);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - T BUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-bus"></i> T BUS</h2>
                <p class="mb-0">Admin Portal</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" required 
                                   placeholder="admin@tbus.et" value="superadmin@tbus.et">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" required 
                                   placeholder="Enter password" value="admin123">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <strong>Demo Credentials Pre-filled</strong><br>
                            Super Admin: superadmin@tbus.et / admin123<br>
                            <a href="../partners/login.php" class="text-decoration-none">Partner Login</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>