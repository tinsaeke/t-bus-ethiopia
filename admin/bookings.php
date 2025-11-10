<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Check if viewing a single booking or all bookings
if (isset($_GET['id'])) {
    // SINGLE BOOKING VIEW
    $booking_id = intval($_GET['id']);
    
    $query = "
        SELECT b.*, s.from_city_id, s.to_city_id, s.travel_date, s.departure_time, s.arrival_time,
               bus.bus_number, bus.type, bus.amenities, bc.company_name, bc.contact_phone as company_phone,
               c1.name as from_city, c2.name as to_city
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bus ON s.bus_id = bus.id
        JOIN bus_companies bc ON bus.bus_company_id = bc.id
        JOIN cities c1 ON s.from_city_id = c1.id
        JOIN cities c2 ON s.to_city_id = c2.id
        WHERE b.id = ?
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo '<div class="alert alert-danger">Booking not found</div>';
        exit;
    }

    $departure = new DateTime($booking['departure_time']);
    $arrival = new DateTime($booking['arrival_time']);
    $duration = $departure->diff($arrival);
    $amenities = json_decode($booking['amenities'], true) ?: [];
    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Details - T BUS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Booking Details</h4>
                <a href="bookings.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to All Bookings
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Passenger Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td><?php echo htmlspecialchars($booking['passenger_full_name']); ?></td></tr>
                                <tr><td><strong>Phone:</strong></td><td><?php echo htmlspecialchars($booking['passenger_phone']); ?></td></tr>
                                <tr><td><strong>Seat Number:</strong></td><td>#<?php echo htmlspecialchars($booking['seat_number']); ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Booking Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Reference:</strong></td><td><?php echo htmlspecialchars($booking['booking_reference']); ?></td></tr>
                                <tr><td><strong>Status:</strong></td><td>
                                    <span class="badge bg-<?php echo $booking['booking_status'] == 'confirmed' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td></tr>
                                <tr><td><strong>Payment:</strong></td><td>
                                    <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td></tr>
                                <tr><td><strong>Amount:</strong></td><td class="text-success">ETB <?php echo number_format($booking['total_price'], 2); ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Trip Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Route:</strong></td><td><?php echo htmlspecialchars($booking['from_city']); ?> → <?php echo htmlspecialchars($booking['to_city']); ?></td></tr>
                                <tr><td><strong>Travel Date:</strong></td><td><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td></tr>
                                <tr><td><strong>Departure:</strong></td><td><?php echo $departure->format('h:i A'); ?></td></tr>
                                <tr><td><strong>Arrival:</strong></td><td><?php echo $arrival->format('h:i A'); ?></td></tr>
                                <tr><td><strong>Duration:</strong></td><td><?php echo $duration->h . 'h ' . $duration->i . 'm'; ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Bus Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Company:</strong></td><td><?php echo htmlspecialchars($booking['company_name']); ?></td></tr>
                                <tr><td><strong>Bus Number:</strong></td><td><?php echo htmlspecialchars($booking['bus_number']); ?></td></tr>
                                <tr><td><strong>Bus Type:</strong></td><td class="text-capitalize"><?php echo htmlspecialchars($booking['type']); ?></td></tr>
                                <tr><td><strong>Contact:</strong></td><td><?php echo htmlspecialchars($booking['company_phone']); ?></td></tr>
                            </table>
                            
                            <?php if (!empty($amenities)): ?>
                            <h6>Amenities</h6>
                            <div class="amenities-list">
                                <?php foreach ($amenities as $amenity): ?>
                                    <span class="badge bg-info me-1"><?php echo ucfirst(htmlspecialchars($amenity)); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h6>Timeline</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Booked on: <?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></li>
                                <?php if ($booking['payment_status'] == 'paid'): ?>
                                    <li><i class="fas fa-check text-success"></i> Paid on: <?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    // ALL BOOKINGS VIEW
    // Get all bookings with pagination
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $total_query = "SELECT COUNT(*) FROM bookings";
    $total_bookings = $db->query($total_query)->fetchColumn();
    $total_pages = ceil($total_bookings / $limit);
    
    // Get bookings
    $query = "
        SELECT b.*, s.from_city_id, s.to_city_id, s.travel_date, s.departure_time,
               bc.company_name, c1.name as from_city, c2.name as to_city
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bus ON s.bus_id = bus.id
        JOIN bus_companies bc ON bus.bus_company_id = bc.id
        JOIN cities c1 ON s.from_city_id = c1.id
        JOIN cities c2 ON s.to_city_id = c2.id
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>All Bookings - T BUS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>All Bookings</h4>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" placeholder="Search bookings..." id="searchInput">
                    <button class="btn btn-outline-primary" onclick="searchBookings()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <h5>No bookings found</h5>
                            <p class="text-muted">There are no bookings in the system yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Passenger</th>
                                        <th>Route</th>
                                        <th>Travel Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['passenger_full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['passenger_phone']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['from_city']); ?> → 
                                                <?php echo htmlspecialchars($booking['to_city']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></small>
                                            </td>
                                            <td class="text-success">
                                                ETB <?php echo number_format($booking['total_price'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['booking_status'] == 'confirmed' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="bookings.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Bookings pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="bookings.php?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="bookings.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="bookings.php?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function searchBookings() {
                const searchTerm = document.getElementById('searchInput').value;
                if (searchTerm.trim()) {
                    // Implement search functionality here
                    alert('Search functionality to be implemented for: ' + searchTerm);
                }
            }
            
            // Enter key search
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchBookings();
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>