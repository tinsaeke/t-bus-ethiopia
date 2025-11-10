$(document).ready(function() {
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
            $(this).addClass('animate-seat');
            setTimeout(() => $(this).removeClass('animate-seat'), 500);
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
        
        selectedSeats.forEach(seat => {
            selectedSeatsList.append(`
                <div class="selected-seat-item">
                    <span>Seat ${seat}</span>
                    <span class="text-primary">ETB ${seatPrice.toFixed(2)}</span>
                </div>
            `);
        });
        
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
                    <div class="passenger-field-group">
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
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Simulate form submission (you'll replace this with actual AJAX)
        setTimeout(() => {
            alert('Booking would be processed here with TeleBirr/CBE integration');
            submitBtn.prop('disabled', false).html(originalText);
        }, 2000);
    });
    
    // Add some interactive effects
    $('.seat.available').hover(
        function() {
            if (!$(this).hasClass('selected')) {
                $(this).css('transform', 'scale(1.05)');
            }
        },
        function() {
            if (!$(this).hasClass('selected')) {
                $(this).css('transform', 'scale(1)');
            }
        }
    );
});