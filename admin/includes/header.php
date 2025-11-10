<?php
// Admin header file
if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}
?>
<nav class="navbar navbar-light bg-white border-bottom">
    <div class="container-fluid">
        <span class="navbar-brand">
            <i class="fas fa-bus me-2"></i>T BUS - Super Admin
        </span>
        <div class="d-flex align-items-center">
            <span class="me-3">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../includes/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>