<?php
// Use absolute path to includes
require_once __DIR__ . '/../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get popular routes
$routes = $db->query("
    SELECT pr.*, c1.name as from_city, c2.name as to_city,
           (SELECT COUNT(*) FROM schedules s 
            WHERE s.from_city_id = pr.from_city_id AND s.to_city_id = pr.to_city_id 
            AND s.travel_date >= CURDATE()) as upcoming_trips,
           (SELECT MIN(price) FROM schedules s 
            WHERE s.from_city_id = pr.from_city_id AND s.to_city_id = pr.to_city_id 
            AND s.travel_date >= CURDATE() AND s.available_seats > 0) as min_price
    FROM popular_routes pr
    JOIN cities c1 ON pr.from_city_id = c1.id
    JOIN cities c2 ON pr.to_city_id = c2.id
    WHERE pr.is_active = TRUE
    ORDER BY pr.display_order
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($routes)) {
    echo '<div class="col-12 text-center"><p class="text-muted">No popular routes found.</p></div>';
} else {
    foreach ($routes as $route) {
        $from_city = $route['from_city'];
        $to_city = $route['to_city'];
        $upcoming_trips = $route['upcoming_trips'];
        $min_price = $route['min_price'] ? number_format($route['min_price'], 2) : 'N/A';
        
        echo "
        <div class='col-md-6 col-lg-4'>
            <div class='card route-card border-0 shadow-sm h-100'>
                <div class='card-body p-4'>
                    <div class='d-flex align-items-start mb-3'>
                        <div class='flex-grow-1'>
                            <h5 class='fw-bold mb-1'>{$from_city} → {$to_city}</h5>
                            <div class='d-flex align-items-center text-muted small mb-2'>
                                <i class='fas fa-calendar me-1'></i>
                                <span>{$upcoming_trips} upcoming trips</span>
                            </div>
                        </div>
                        <i class='fas fa-arrow-right text-primary fs-4'></i>
                    </div>
                    
                    <div class='d-flex justify-content-between align-items-center mb-3'>
                        <span class='text-muted small'>Starting from</span>
                        <span class='fw-bold text-success'>ETB {$min_price}</span>
                    </div>
                    
                    <a href='search.php?from_city={$route['from_city_id']}&to_city={$route['to_city_id']}&travel_date=" . date('Y-m-d', strtotime('+1 day')) . "' 
                       class='btn btn-outline-primary w-100'>
                        <i class='fas fa-search me-1'></i>View Buses
                    </a>
                </div>
            </div>
        </div>";
    }
}
?>