<?php
// Partner sidebar file
?>
<div class="col-md-3 col-lg-2 sidebar bg-dark text-white p-0">
    <div class="p-3">
        <h5 class="text-center mb-4 text-white">
            <i class="fas fa-building"></i><br>
            <?php echo $_SESSION['company_name']; ?>
        </h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage-schedules.php">
                    <i class="fas fa-calendar-alt"></i> Schedules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="bookings.php">
                    <i class="fas fa-ticket-alt"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buses.php">
                    <i class="fas fa-bus"></i> My Buses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-warning" href="../includes/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>