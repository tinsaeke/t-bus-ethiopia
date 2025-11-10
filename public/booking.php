<?php
require_once '../includes/functions.php';

$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

if (!$schedule_id) {
    // Redirect to search page if no schedule ID
    header("Location: search.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get schedule details
$query = "
    SELECT s.*, b.bus_number, b.type, b.total_seats, b.amenities, bc.company_name,
           c1.name as from_city, c2.name as to_city
    FROM schedules s
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_companies bc ON b.bus_company_id = bc.id
    JOIN cities c1 ON s.from_city_id = c1.id
    JOIN cities c2 ON s.to_city_id = c2.id
    WHERE s.id = ? AND s.is_active = TRUE AND b.is_active = TRUE
";

$stmt = $db->prepare($query);
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    $_SESSION['error'] = "Sorry, this schedule is no longer available.";
    header("Location: search.php");
    exit;
}

if ($schedule['available_seats'] < $passengers) {
    $_SESSION['error'] = "Sorry, this schedule doesn't have enough seats available.";
    header("Location: search.php");
    exit;
}

// Get booked seats
$bookedSeatsQuery = "SELECT seat_number FROM bookings WHERE schedule_id = ? AND booking_status != 'cancelled'";
$bookedSeatsStmt = $db->prepare($bookedSeatsQuery);
$bookedSeatsStmt->execute([$schedule_id]);
$bookedSeats = $bookedSeatsStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Book Your Seats - T BUS";

// Calculate times
$departure = new DateTime($schedule['departure_time']);
$arrival = new DateTime($schedule['arrival_time']);
$duration = $departure->diff($arrival);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .bus-layout {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        .seat {
            width: 50px;
            height: 50px;
            margin: 5px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .seat.available {
            background: #28a745;
            color: white;
            border: 2px solid #1e7e34;
        }
        .seat.available:hover {
            background: #218838;
            transform: scale(1.1);
        }
        .seat.selected {
            background: #ffc107;
            color: #000;
            border: 2px solid #e0a800;
            transform: scale(1.1);
        }
        .seat.booked {
            background: #dc3545;
            color: white;
            border: 2px solid #c82333;
            cursor: not-allowed;
        }
        .driver-cabin {
            background: #007bff;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 2rem;
        }
        .seat-row {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        .aisle {
            width: 60px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            margin: 0 10px;
        }
        .legend-seat {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 5px;
        }
        .selected-seats-list {
            min-height: 100px;
            border: 1px dashed #dee2e6;
            border-radius: 5px;
            padding: 1rem;
        }
        .selected-seat-item {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-bus me-2"></i>T BUS
            </a>
            <div class="navbar-text">
                <span class="text-muted">Step 2: Select Seats</span>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <!-- Trip Summary -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Trip Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="trip-info mb-4">
                            <h6 class="fw-bold"><?php echo $schedule['from_city']; ?> → <?php echo $schedule['to_city']; ?></h6>
                            <div class="text-muted small">
                                <div class="mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('l, M d, Y', strtotime($schedule['travel_date'])); ?>
                                </div>
                                <div class="mb-1">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo $departure->format('h:i A'); ?> - <?php echo $arrival->format('h:i A'); ?>
                                    <span class="badge bg-light text-dark ms-2"><?php echo $duration->h . 'h ' . $duration->i . 'm'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bus-info mb-4">
                            <h6 class="fw-bold">Bus Details</h6>
                            <div class="text-muted small">
                                <div class="mb-1">
                                    <i class="fas fa-building me-2"></i>
                                    <?php echo $schedule['company_name']; ?>
                                </div>
                                <div class="mb-1">
                                    <i class="fas fa-bus me-2"></i>
                                    <?php echo $schedule['bus_number']; ?> • <?php echo strtoupper($schedule['type']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo $schedule['available_seats']; ?> seats available
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="booking-summary">
                            <h6 class="fw-bold">Booking Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Passengers:</span>
                                <span id="passengerCount">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Price per seat:</span>
                                <span>ETB <?php echo number_format($schedule['price'], 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total Amount:</span>
                                <span id="totalAmount" class="text-success">ETB 0.00</span>
                            </div>
                        </div>
                        
                        <div class="selected-seats mt-4">
                            <h6 class="fw-bold">Selected Seats</h6>
                            <div id="selectedSeatsList" class="selected-seats-list">
                                <div class="text-muted text-center py-3">No seats selected yet</div>
                            </div>
                        </div>
                        
                        <button id="proceedToPayment" class="btn btn-primary w-100 mt-3 py-3" disabled>
                            <i class="fas fa-lock me-2"></i>Proceed to Payment
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Seat Selection -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chair me-2"></i>Select Your Seats</h5>
                        <small class="text-muted">Click on available seats to select (Max: <?php echo $passengers; ?> seats)</small>
                    </div>
                    <div class="card-body">
                        <!-- Bus Layout -->
                        <div class="bus-layout text-center">
                            <div class="driver-cabin">
                                <i class="fas fa-user-tie fa-2x mb-2"></i>
                                <div class="fw-bold">Driver</div>
                            </div>
                            
                            <div class="bus-seats mb-4">
                                <?php
                                $totalSeats = $schedule['total_seats'];
                                $seatsPerRow = 4;
                                $rows = ceil($totalSeats / $seatsPerRow);
                                
                                for ($row = 1; $row <= $rows; $row++): 
                                ?>
                                    <div class="seat-row">
                                        <?php for ($col = 1; $col <= $seatsPerRow; $col++): 
                                            $seatNumber = ($row - 1) * $seatsPerRow + $col;
                                            if ($seatNumber > $totalSeats) break;
                                            
                                            $isBooked = in_array($seatNumber, $bookedSeats);
                                            $isAisle = $col == 3;
                                        ?>
                                            <div class="<?php echo $isAisle ? 'aisle' : ''; ?>">
                                                <?php if (!$isAisle): ?>
                                                    <div class="seat <?php echo $isBooked ? 'booked' : 'available'; ?>" 
                                                         data-seat="<?php echo $seatNumber; ?>"
                                                         data-price="<?php echo $schedule['price']; ?>">
                                                        <?php if ($isBooked): ?>
                                                            <i class="fas fa-times"></i>
                                                        <?php else: ?>
                                                            <span class="seat-number"><?php echo $seatNumber; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="aisle"></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="bus-legends">
                                <div class="legend-item">
                                    <div class="seat available legend-seat"></div>
                                    <span class="small">Available</span>
                                </div>
                                <div class="legend-item">
                                    <div class="seat selected legend-seat"></div>
                                    <span class="small">Selected</span>
                                </div>
                                <div class="legend-item">
                                    <div class="seat booked legend-seat"></div>
                                    <span class="small">Booked</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Passenger Details Form -->
                <div id="passengerForm" class="card shadow-sm border-0 mt-4" style="display: none;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Passenger Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="bookingForm" action="process-booking.php" method="POST">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                            <input type="hidden" name="selected_seats" id="selectedSeatsInput">
                            <input type="hidden" name="total_amount" id="totalAmountInput">
                            
                            <div id="passengerFields">
                                <!-- Passenger fields will be generated here by JavaScript -->
                            </div>
                            
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method</h6>
                                <div class="payment-methods">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" value="telebirr" id="telebirr" checked>
                                        <label class="form-check-label d-flex align-items-center" for="telebirr">
                                            <i class="fas fa-mobile-alt text-primary fs-4 me-3"></i>
                                            <div>
                                                <div class="fw-bold">TeleBirr</div>
                                                <small class="text-muted">Pay securely with TeleBirr</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" value="cbe_birr" id="cbe_birr">
                                        <label class="form-check-label d-flex align-items-center" for="cbe_birr">
                                            <i class="fas fa-university text-success fs-4 me-3"></i>
                                            <div>
                                                <div class="fw-bold">CBE Birr</div>
                                                <small class="text-muted">Pay with CBE Birr</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" value="cash" id="cash">
                                        <label class="form-check-label d-flex align-items-center" for="cash">
                                            <i class="fas fa-money-bill-wave text-warning fs-4 me-3"></i>
                                            <div>
                                                <div class="fw-bold">Cash Payment</div>
                                                <small class="text-muted">Pay at bus station</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-success btn-lg w-100 py-3">
                                    <i class="fas fa-lock me-2"></i>Confirm & Pay Now
                                </button>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Your payment is secure and encrypted
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Seat selection functionality
        const maxPassengers = <?php echo $passengers; ?>;
        let selectedSeats = [];
        const seatPrice = <?php echo $schedule['price']; ?>;
        
        // Seat selection
        $('.seat.available').click(function() {
            const seatNumber = $(this).data('seat');
            
            if (selectedSeats.includes(seatNumber)) {
                // Deselect seat
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                $(this).removeClass('selected').addClass('available');
            } else {
                // Check if reached maximum
                if (selectedSeats.length >= maxPassengers) {
                    alert(`You can only select up to ${maxPassengers} seat(s)`);
                    return;
                }
                
                // Select seat
                selectedSeats.push(seatNumber);
                $(this).removeClass('available').addClass('selected');
            }
            
            updateBookingSummary();
            updatePassengerForm();
        });
        
        function updateBookingSummary() {
            const passengerCount = selectedSeats.length;
            const totalAmount = passengerCount * seatPrice;
            
            $('#passengerCount').text(passengerCount);
            $('#totalAmount').text('ETB ' + totalAmount.toFixed(2));
            
            // Update selected seats list
            const selectedSeatsList = $('#selectedSeatsList');
            selectedSeatsList.empty();
            
            if (selectedSeats.length === 0) {
                selectedSeatsList.html('<div class="text-muted text-center py-3">No seats selected yet</div>');
            } else {
                selectedSeats.forEach(seat => {
                    selectedSeatsList.append(`
                        <div class="selected-seat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Seat ${seat}</span>
                                <span class="text-primary">ETB ${seatPrice.toFixed(2)}</span>
                            </div>
                        </div>
                    `);
                });
            }
            
            // Enable/disable proceed button
            const proceedBtn = $('#proceedToPayment');
            if (passengerCount > 0) {
                proceedBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
            } else {
                proceedBtn.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
            }
        }
        
        function updatePassengerForm() {
            const passengerForm = $('#passengerForm');
            const passengerFields = $('#passengerFields');
            
            if (selectedSeats.length > 0) {
                passengerForm.slideDown(300);
                passengerFields.empty();
                
                selectedSeats.forEach((seat, index) => {
                    passengerFields.append(`
                        <div class="passenger-field-group border rounded p-3 mb-3">
                            <h6>Passenger ${index + 1} (Seat ${seat})</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="passenger_name[]" required 
                                           placeholder="Enter full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="passenger_phone[]" required 
                                           placeholder="+251 ...">
                                </div>
                            </div>
                        </div>
                    `);
                });
                
                // Update hidden inputs
                $('#selectedSeatsInput').val(selectedSeats.join(','));
                $('#totalAmountInput').val(selectedSeats.length * seatPrice);
            } else {
                passengerForm.slideUp(300);
            }
        }
        
        // Proceed to payment button
        $('#proceedToPayment').click(function() {
            $('html, body').animate({
                scrollTop: $('#passengerForm').offset().top - 100
            }, 500);
        });
        
        // Form submission
        $('#bookingForm').submit(function(e) {
            if (selectedSeats.length === 0) {
                e.preventDefault();
                alert('Please select at least one seat');
                return;
            }
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Show loading state
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            // Form will submit normally
        });
    </script>
</body>
</html>