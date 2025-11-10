<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $from_city = isset($_GET['from_city']) ? intval($_GET['from_city']) : 0;
    $to_city = isset($_GET['to_city']) ? intval($_GET['to_city']) : 0;
    $travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : '';
    $passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

    if (!$from_city || !$to_city || !$travel_date) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
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

        // Format response
        $formatted_buses = [];
        foreach ($buses as $bus) {
            $departure = new DateTime($bus['departure_time']);
            $arrival = new DateTime($bus['arrival_time']);
            $duration = $departure->diff($arrival);
            
            $formatted_buses[] = [
                'id' => $bus['id'],
                'company_name' => $bus['company_name'],
                'company_logo' => $bus['logo_url'],
                'bus_number' => $bus['bus_number'],
                'bus_type' => $bus['type'],
                'from_city' => $bus['from_city'],
                'to_city' => $bus['to_city'],
                'departure_time' => $departure->format('h:i A'),
                'arrival_time' => $arrival->format('h:i A'),
                'duration' => $duration->h . 'h ' . $duration->i . 'm',
                'price' => floatval($bus['price']),
                'available_seats' => $bus['available_seats'],
                'amenities' => json_decode($bus['amenities'], true) ?: [],
                'travel_date' => $bus['travel_date']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $formatted_buses,
            'count' => count($formatted_buses)
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>