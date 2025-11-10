<?php
require_once '../includes/functions.php';

// Get search parameters
$from_city = isset($_GET['from_city']) ? intval($_GET['from_city']) : 0;
$to_city = isset($_GET['to_city']) ? intval($_GET['to_city']) : 0;
$travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

$pageTitle = "Search Results - T BUS";

// Search for buses
$buses = [];
if ($from_city && $to_city && $travel_date) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "
        SELECT s.*, b.bus_number, b.type, b.amenities, bc.company_name, bc.logo_url,
               c1.name as from_city, c2.name as to_city
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN bus_companies bc ON b.bus_company_id = bc.id
        JOIN cities c1 ON s.from_city_id = c1.id
        JOIN cities c2 ON s.to_city_id = c2.id
        WHERE s.from_city_id = ? AND s.to_city_id = ? 
        AND s.travel_date = ? AND s.available_seats >= ?
        AND s.is_active = TRUE AND b.is_active = TRUE AND bc.is_active = TRUE
        ORDER BY s.departure_time
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$from_city, $to_city, $travel_date, $passengers]);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get city names for display
$from_city_name = getCityName($from_city);
$to_city_name = getCityName($to_city);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-bus me-2"></i>T BUS
            </a>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> New Search
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Search Summary -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">
                            <i class="fas fa-route text-primary me-2"></i>
                            <?php echo $from_city_name . ' → ' . $to_city_name; ?>
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar me-1"></i> 
                            <?php echo date('F d, Y', strtotime($travel_date)); ?> 
                            • 
                            <i class="fas fa-users me-1"></i> 
                            <?php echo $passengers; ?> Passenger(s)
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary fs-6">
                            <?php echo count($buses); ?> Buses Found
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bus Results -->
        <?php if (empty($buses)): ?>
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-bus-slash fa-3x text-muted mb-3"></i>
                    <h3>No Buses Found</h3>
                    <p class="text-muted mb-4">
                        Sorry, we couldn't find any buses for your search criteria.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h5>Suggestions:</h5>
                                    <ul class="text-start">
                                        <li>Try different travel dates</li>
                                        <li>Check different city combinations</li>
                                        <li>Contact customer support</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="index.php" class="btn btn-primary mt-4">
                        <i class="fas fa-search me-1"></i> Search Again
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($buses as $bus): 
                    $amenities = json_decode($bus['amenities'], true) ?: [];
                    $departure = new DateTime($bus['departure_time']);
                    $arrival = new DateTime($bus['arrival_time']);
                    $duration = $departure->diff($arrival);
                ?>
                <div class="col-12 mb-4">
                    <div class="card bus-card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <div class="company-logo mb-2">
                                        <i class="fas fa-bus fa-2x text-primary"></i>
                                    </div>
                                    <h6 class="mb-0"><?php echo $bus['company_name']; ?></h6>
                                    <small class="text-muted"><?php echo strtoupper($bus['bus_number']); ?></small>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="time-section text-center">
                                        <h5 class="text-primary mb-1"><?php echo $departure->format('h:i A'); ?></h5>
                                        <small class="text-muted"><?php echo $bus['from_city']; ?></small>
                                        <br>
                                        <small class="text-muted duration">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo $duration->h . 'h ' . $duration->i . 'm'; ?>
                                        </small>
                                        <br>
                                        <h6 class="mt-1"><?php echo $arrival->format('h:i A'); ?></h6>
                                        <small class="text-muted"><?php echo $bus['to_city']; ?></small>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="amenities">
                                        <div class="mb-2">
                                            <?php if ($bus['type'] == 'vip'): ?>
                                                <span class="badge bg-warning text-dark">VIP</span>
                                            <?php elseif ($bus['type'] == 'business'): ?>
                                                <span class="badge bg-success">Business</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Standard</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="amenity-icons">
                                            <?php foreach ($amenities as $amenity): ?>
                                                <span class="amenity-icon me-1" title="<?php echo ucfirst($amenity); ?>">
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
                                                    <i class="fas fa-<?php echo $icon; ?> text-success"></i>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 text-center">
                                    <div class="seats-available">
                                        <h6 class="text-<?php echo $bus['available_seats'] > 10 ? 'success' : ($bus['available_seats'] > 5 ? 'warning' : 'danger'); ?>">
                                            <?php echo $bus['available_seats']; ?> Seats
                                        </h6>
                                        <small class="text-muted">Available</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 text-center">
                                    <div class="price-section">
                                        <h4 class="text-primary mb-1">ETB <?php echo number_format($bus['price'], 2); ?></h4>
                                        <small class="text-muted">per person</small>
                                        <br>
                                        <a href="booking.php?schedule_id=<?php echo $bus['id']; ?>&passengers=<?php echo $passengers; ?>" 
                                           class="btn btn-primary mt-2 w-100">
                                            Select Seats
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>