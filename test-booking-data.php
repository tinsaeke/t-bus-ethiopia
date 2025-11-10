<?php
// Add test data for booking demonstration
try {
    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🚌 Adding Test Booking Data</h2>";
    
    // 1. Add sample schedules if they don't exist
    $checkSchedules = $conn->query("SELECT COUNT(*) FROM schedules WHERE travel_date >= CURDATE()")->fetchColumn();
    
    if ($checkSchedules == 0) {
        echo "<p>Adding sample schedules...</p>";
        
        // Get bus and city IDs
        $bus = $conn->query("SELECT id FROM buses LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $cities = $conn->query("SELECT id, name FROM cities LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        
        if ($bus && count($cities) >= 2) {
            // Create sample schedules for next 7 days
            for ($i = 1; $i <= 7; $i++) {
                $travel_date = date('Y-m-d', strtotime("+$i days"));
                
                // Create multiple schedules per day
                $departure_times = ['08:00:00', '10:00:00', '14:00:00', '18:00:00'];
                
                foreach ($departure_times as $dep_time) {
                    $arr_time = date('H:i:s', strtotime($dep_time) + 4*3600); // +4 hours
                    $price = rand(300, 600); // Random price between 300-600
                    
                    $stmt = $conn->prepare("
                        INSERT INTO schedules (bus_id, from_city_id, to_city_id, departure_time, arrival_time, price, available_seats, travel_date) 
                        VALUES (?, ?, ?, ?, ?, ?, 45, ?)
                    ");
                    
                    // Use different city combinations
                    $from_city = $cities[0]['id'];
                    $to_city = $cities[1]['id'];
                    
                    $stmt->execute([$bus['id'], $from_city, $to_city, $dep_time, $arr_time, $price, $travel_date]);
                }
            }
            echo "<p style='color: green;'>✅ Sample schedules added successfully!</p>";
        }
    } else {
        echo "<p>✅ Schedules already exist</p>";
    }
    
    // 2. Show available schedules for booking
    echo "<h3>Available Schedules for Booking:</h3>";
    $schedules = $conn->query("
        SELECT s.*, b.bus_number, bc.company_name, c1.name as from_city, c2.name as to_city
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN bus_companies bc ON b.bus_company_id = bc.id
        JOIN cities c1 ON s.from_city_id = c1.id
        JOIN cities c2 ON s.to_city_id = c2.id
        WHERE s.travel_date >= CURDATE() AND s.available_seats > 0
        ORDER BY s.travel_date, s.departure_time
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($schedules) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>
                <th>Route</th>
                <th>Date & Time</th>
                <th>Company</th>
                <th>Price</th>
                <th>Seats</th>
                <th>Action</th>
              </tr>";
        
        foreach ($schedules as $schedule) {
            $departure = date('h:i A', strtotime($schedule['departure_time']));
            $arrival = date('h:i A', strtotime($schedule['arrival_time']));
            
            echo "<tr>";
            echo "<td><strong>{$schedule['from_city']} → {$schedule['to_city']}</strong></td>";
            echo "<td>{$schedule['travel_date']}<br>{$departure} - {$arrival}</td>";
            echo "<td>{$schedule['company_name']}</td>";
            echo "<td style='color: green; font-weight: bold;'>ETB {$schedule['price']}</td>";
            echo "<td>{$schedule['available_seats']} available</td>";
            echo "<td>
                    <a href='../public/booking.php?schedule_id={$schedule['id']}&passengers=1' 
                       style='background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                       Book Now
                    </a>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px;'>";
        echo "<h4>🎉 Ready to Book!</h4>";
        echo "<p>Click 'Book Now' on any schedule above to start the booking process.</p>";
        echo "<p>Or <a href='../public/index.php'>go to homepage</a> to search for specific routes.</p>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>No available schedules found. Please add buses and schedules first.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>