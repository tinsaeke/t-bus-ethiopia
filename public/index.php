<?php
require_once '../includes/functions.php';
$pageTitle = "T BUS - Ethiopia's Leading Online Bus Booking Platform | Book Bus Tickets";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Cloud-Based CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #7c3aed;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .how-it-works-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.2s ease;
        }
        .how-it-works-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
            background-color: #f8faff !important;
        }

        /* FIXED STYLES FOR FORM ELEMENTS */
        .hero-section {
            position: relative;
            overflow: hidden;
        }

        .hero-section .container {
            position: relative;
            z-index: 1000;
        }

        .search-card {
            position: relative;
            z-index: 1000 !important;
        }

        .form-select, 
        .form-control,
        .form-control:focus,
        .form-select:focus {
            background-color: white !important;
            position: relative !important;
            z-index: 1001 !important;
            pointer-events: auto !important;
        }

        .input-group {
            position: relative;
            z-index: 1001 !important;
        }

        /* Ensure background elements don't interfere */
        .floating-element {
            pointer-events: none !important;
            z-index: 1 !important;
        }

        /* Fix for clickable elements */
        .hero-section select,
        .hero-section input,
        .hero-section button,
        .hero-section .input-group {
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1001 !important;
        }

        /* Remove any overlay interference */
        .hero-section::before {
            display: none;
        }

        /* Ensure form is fully interactive */
        #searchForm * {
            pointer-events: auto !important;
        }

        /* Debug border - remove in production */
        .debug-border {
            /* border: 1px solid red !important; */
        }
    </style>
</head>
<body>
    <!-- Enhanced Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-white shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="ri-bus-line me-2"></i>T BUS
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#routes">Popular Routes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="manage-booking.php">Manage Booking</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <a href="../admin/login.php" class="btn btn-outline-primary me-2">
                        <i class="ri-user-line me-1"></i>Partner Login
                    </a>
                    <a href="tel:+251911223344" class="btn btn-primary">
                        <i class="ri-phone-line me-1"></i>Support
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Search -->
    <section class="hero-section position-relative overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding-top: 120px; padding-bottom: 80px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Travel Across Ethiopia with <span class="text-warning">Comfort</span> & <span class="text-warning">Ease</span>
                    </h1>
                    <p class="lead text-light mb-5">
                        Book bus tickets online in seconds. Choose from 50+ bus companies across Ethiopia. 
                        Safe, reliable, and affordable travel experiences.
                    </p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center text-white">
                            <i class="ri-shield-check-line fs-2 text-success me-3"></i>
                            <div>
                                <h5 class="mb-0">100% Secure</h5>
                                <small class="text-light">Safe & encrypted payments</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <i class="ri-customer-service-2-line fs-2 text-warning me-3"></i>
                            <div>
                                <h5 class="mb-0">24/7 Support</h5>
                                <small class="text-light">Always here to help</small>
                            </div>
                        </div>
                    </div>
                </div>
              
                <!-- Search Form -->
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="card search-card border-0 shadow-lg rounded-3 debug-border">
                        <div class="card-body p-4">
                            <h4 class="card-title text-center mb-4">
                                <i class="fas fa-search me-2 text-primary"></i>Search & Book Buses
                            </h4>
                            
                            <form action="search.php" method="GET" id="searchForm">
                                <div class="row g-3">
                                    <!-- From City -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">From City</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-map-pin text-primary"></i>
                                            </span>
                                            <select class="form-select border-start-0 debug-border" name="from_city" required id="fromCity">
                                                <option value="">Select departure city</option>
                                                <?php
                                                // Database connection with fallback
                                                try {
                                                    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
                                                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                                    
                                                    $cities = $conn->query("SELECT * FROM cities ORDER BY name")->fetchAll();
                                                    
                                                    if ($cities && count($cities) > 0) {
                                                        foreach ($cities as $city) {
                                                            echo "<option value='{$city['id']}'>{$city['name']}</option>";
                                                        }
                                                    } else {
                                                        // Fallback options if no cities in database
                                                        echo "<option value='1'>Addis Ababa</option>";
                                                        echo "<option value='2'>Dire Dawa</option>";
                                                        echo "<option value='3'>Hawassa</option>";
                                                        echo "<option value='4'>Bahir Dar</option>";
                                                        echo "<option value='5'>Mekelle</option>";
                                                    }
                                                } catch (PDOException $e) {
                                                    // Fallback options if database connection fails
                                                    echo "<option value='1'>Addis Ababa</option>";
                                                    echo "<option value='2'>Dire Dawa</option>";
                                                    echo "<option value='3'>Hawassa</option>";
                                                    echo "<option value='4'>Bahir Dar</option>";
                                                    echo "<option value='5'>Mekelle</option>";
                                                    echo "<option value='6'>Adama</option>";
                                                    echo "<option value='7'>Gondar</option>";
                                                    echo "<option value='8'>Jimma</option>";
                                                    error_log("Database error: " . $e->getMessage());
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- To City -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">To City</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-map-pin-fill text-danger"></i>
                                            </span>
                                            <select class="form-select border-start-0 debug-border" name="to_city" required id="toCity">
                                                <option value="">Select destination city</option>
                                                <?php
                                                try {
                                                    $conn = new PDO("mysql:host=localhost;dbname=t_bus_ethiopia", "root", "");
                                                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                                    
                                                    $cities = $conn->query("SELECT * FROM cities ORDER BY name")->fetchAll();
                                                    
                                                    if ($cities && count($cities) > 0) {
                                                        foreach ($cities as $city) {
                                                            echo "<option value='{$city['id']}'>{$city['name']}</option>";
                                                        }
                                                    } else {
                                                        // Fallback options
                                                        echo "<option value='2'>Dire Dawa</option>";
                                                        echo "<option value='1'>Addis Ababa</option>";
                                                        echo "<option value='3'>Hawassa</option>";
                                                        echo "<option value='4'>Bahir Dar</option>";
                                                        echo "<option value='5'>Mekelle</option>";
                                                    }
                                                } catch (PDOException $e) {
                                                    // Fallback options
                                                    echo "<option value='2'>Dire Dawa</option>";
                                                    echo "<option value='1'>Addis Ababa</option>";
                                                    echo "<option value='3'>Hawassa</option>";
                                                    echo "<option value='4'>Bahir Dar</option>";
                                                    echo "<option value='5'>Mekelle</option>";
                                                    echo "<option value='6'>Adama</option>";
                                                    echo "<option value='7'>Gondar</option>";
                                                    echo "<option value='8'>Jimma</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Travel Date -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Travel Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-calendar text-primary"></i>
                                            </span>
                                            <input type="date" class="form-control border-start-0 debug-border" name="travel_date" 
                                                   id="travelDate" min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Passengers -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Passengers</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-primary"></i>
                                            </span>
                                            <select class="form-select border-start-0 debug-border" name="passengers" id="passengers">
                                                <option value="1">1 Passenger</option>
                                                <option value="2">2 Passengers</option>
                                                <option value="3">3 Passengers</option>
                                                <option value="4">4 Passengers</option>
                                                <option value="5">5 Passengers</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-semibold debug-border">
                                            <i class="fas fa-search me-2"></i>Search Buses
                                            <span class="spinner-border spinner-border-sm ms-2 d-none" id="searchSpinner"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-check text-success me-1"></i>
                                    Your search is secure and private
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Animated Background Elements -->
        <div class="position-absolute top-0 end-0 w-50 h-100">
            <div class="position-relative h-100">
                <div class="position-absolute floating-element" style="top: 20%; right: 10%; animation-delay: 0s;">
                    <i class="ri-bus-2-line text-white-50" style="font-size: 3rem;"></i>
                </div>
                <div class="position-absolute floating-element" style="top: 60%; right: 30%; animation-delay: 2s;">
                    <i class="ri-road-map-line text-white-50" style="font-size: 2.5rem;"></i>
                </div>
                <div class="position-absolute floating-element" style="top: 40%; right: 60%; animation-delay: 4s;">
                    <i class="ri-compass-3-line text-white-50" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Indicators -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-3" data-aos="fade-up">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="ri-user-line fs-2 text-primary me-3"></i>
                        <div class="text-start">
                            <h4 class="fw-bold mb-0">50,000+</h4>
                            <small class="text-muted">Happy Travelers</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="ri-building-2-line fs-2 text-success me-3"></i>
                        <div class="text-start">
                            <h4 class="fw-bold mb-0">50+</h4>
                            <small class="text-muted">Bus Partners</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="ri-map-pin-line fs-2 text-warning me-3"></i>
                        <div class="text-start">
                            <h4 class="fw-bold mb-0">25+</h4>
                            <small class="text-muted">Cities Covered</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="ri-star-line fs-2 text-danger me-3"></i>
                        <div class="text-start">
                            <h4 class="fw-bold mb-0">4.8/5</h4>
                            <small class="text-muted">Customer Rating</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   <!-- Popular Routes -->
<section id="routes" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Popular Routes in Ethiopia</h2>
            <p class="lead text-muted">Discover the most traveled routes across the country</p>
        </div>
        
        <div class="row g-4" id="popularRoutes">
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading routes...</span>
                </div>
                <p class="mt-2 text-muted">Loading popular routes...</p>
            </div>
        </div>
    </div>
</section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-3">How T BUS Works</h2>
                <p class="lead text-muted">Book your bus ticket in 3 simple steps</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 how-it-works-card">
                        <div class="card-body">
                            <div class="icon-wrapper bg-primary rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-search-line text-white fs-2"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Search</h4>
                            <p class="text-muted">Enter your departure and destination cities, travel date, and number of passengers</p>
                            <span class="badge bg-primary rounded-pill">Step 1</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 how-it-works-card">
                        <div class="card-body">
                            <div class="icon-wrapper bg-success rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-ticket-line text-white fs-2"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Select & Book</h4>
                            <p class="text-muted">Choose your preferred bus, select seats, and proceed to secure payment</p>
                            <span class="badge bg-success rounded-pill">Step 2</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 how-it-works-card">
                        <div class="card-body">
                            <div class="icon-wrapper bg-warning rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-road-map-line text-white fs-2"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Travel</h4>
                            <p class="text-muted">Receive e-ticket, show it at boarding, and enjoy your comfortable journey</p>
                            <span class="badge bg-warning rounded-pill">Step 3</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <h2 class="display-5 fw-bold mb-4">Why Choose T BUS?</h2>
                    <p class="lead text-muted mb-5">We're revolutionizing bus travel in Ethiopia with technology and customer-centric approach</p>
                    
                    <div class="row g-4">
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-shield-check-line text-success fs-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="fw-bold">Secure Booking</h5>
                                    <p class="text-muted mb-0">Your payments and data are protected with bank-level security</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-24-hours-line text-primary fs-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="fw-bold">24/7 Support</h5>
                                    <p class="text-muted mb-0">Round-the-clock customer support for all your travel needs</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-smartphone-line text-info fs-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="fw-bold">Mobile Friendly</h5>
                                    <p class="text-muted mb-0">Book tickets seamlessly on any device, anywhere</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="ri-refund-line text-warning fs-2"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="fw-bold">Easy Cancellation</h5>
                                    <p class="text-muted mb-0">Flexible cancellation policies with quick refunds</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="position-relative">
                        <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Modern Bus Travel" class="img-fluid rounded-3 shadow-lg">
                        <div class="position-absolute bottom-0 start-0 m-4">
                            <div class="card bg-primary text-white border-0 shadow">
                                <div class="card-body p-3">
                                    <h5 class="mb-1">Ready to Travel?</h5>
                                    <p class="mb-0 small">Book your next adventure today</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-3">What Travelers Say</h2>
                <p class="lead text-muted">Join thousands of satisfied customers across Ethiopia</p>
            </div>
            
            <div class="owl-carousel testimonial-carousel">
                <!-- Testimonials will be loaded via JavaScript -->
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0" data-aos="fade-right">
                    <h2 class="display-6 fw-bold mb-3">Ready to Start Your Journey?</h2>
                    <p class="lead mb-0">Download our mobile app or book directly through our website</p>
                </div>
                <div class="col-lg-4" data-aos="fade-left">
                    <div class="d-flex gap-3 justify-content-lg-end">
                        <a href="#" class="btn btn-light btn-lg px-4">
                            <i class="ri-google-play-line me-2"></i>Play Store
                        </a>
                        <a href="#" class="btn btn-outline-light btn-lg px-4">
                            <i class="ri-apple-line me-2"></i>App Store
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-4">
                        <i class="ri-bus-line me-2"></i>T BUS
                    </h5>
                    <p class="text-light mb-4">Ethiopia's leading online bus ticketing platform, connecting travelers with trusted bus companies nationwide.</p>
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/" class="text-light fs-5"><i class="ri-facebook-fill"></i></a>
                        <a href="https://x.com/" class="text-light fs-5"><i class="ri-twitter-fill"></i></a>
                        <a href="https://www.instagram.com/" class="text-light fs-5"><i class="ri-instagram-line"></i></a>
                        <a href="https://www.linkedin.com/" class="text-light fs-5"><i class="ri-linkedin-fill"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-bold mb-4">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#routes" class="text-light text-decoration-none">Popular Routes</a></li>
                        <li class="mb-2"><a href="#how-it-works" class="text-light text-decoration-none">How It Works</a></li>
                        <li class="mb-2"><a href="#contact" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <h6 class="fw-bold mb-4">Support</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="tel:+251911223344" class="text-light text-decoration-none">
                            <i class="ri-phone-line me-2"></i>+251 931 016221
                        </a></li>
                        <li class="mb-2"><a href="mailto:support@tbus.et" class="text-light text-decoration-none">
                            <i class="ri-mail-line me-2"></i>support@tbus.et
                        </a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">
                            <i class="ri-customer-service-2-line me-2"></i>Help Center
                        </a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <h6 class="fw-bold mb-4">Newsletter</h6>
                    <p class="text-light small mb-3">Subscribe for travel deals and updates</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email">
                        <button class="btn btn-primary" type="button">
                            <i class="ri-send-plane-line"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <hr class="my-4 border-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light small">&copy; 2025 T BUS. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex gap-4 justify-content-md-end">
                        <a href="#" class="text-light small text-decoration-none">Privacy Policy</a>
                        <a href="#" class="text-light small text-decoration-none">Terms of Service</a>
                        <a href="#" class="text-light small text-decoration-none">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Cloud-Based JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Form submission with loading state
        $('#searchForm').submit(function(e) {
            $('#searchSpinner').removeClass('d-none');
        });

        // DEBUG: Check form element status
        $(document).ready(function() {
            console.log('DEBUG: Form elements loaded');
            console.log('From City element:', $('#fromCity').length > 0 ? 'Found' : 'Missing');
            console.log('To City element:', $('#toCity').length > 0 ? 'Found' : 'Missing');
            console.log('Travel Date element:', $('#travelDate').length > 0 ? 'Found' : 'Missing');
            console.log('Passengers element:', $('#passengers').length > 0 ? 'Found' : 'Missing');

            // Force enable all form elements
            $('#searchForm select, #searchForm input, #searchForm button').each(function() {
                $(this).css({
                    'pointer-events': 'auto',
                    'position': 'relative',
                    'z-index': '1000'
                });
            });

            // Test click events
            $('#fromCity').on('click focus', function() {
                console.log('From City clicked/focused');
            });

            $('#toCity').on('click focus', function() {
                console.log('To City clicked/focused');
            });

            $('#travelDate').on('click focus', function() {
                console.log('Travel Date clicked/focused');
            });

            $('#passengers').on('click focus', function() {
                console.log('Passengers clicked/focused');
            });

            // Load popular routes via AJAX
            $.ajax({
                url: 'ajax/get-popular-routes.php',
                type: 'GET',
                success: function(response) {
                    $('#popularRoutes').html(response);
                },
                error: function() {
                    $('#popularRoutes').html('<div class="col-12 text-center"><p class="text-muted">Unable to load popular routes at this time.</p></div>');
                }
            });

            // Initialize testimonial carousel
            $('.testimonial-carousel').owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: false,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    992: { items: 3 }
                }
            });

            // Counter animation
            $('.counter').counterUp({
                delay: 10,
                time: 2000
            });
        });

        // Smooth scrolling for navigation links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $($(this).attr('href')).offset().top - 80
            }, 500);
        });

        // Additional safety: Prevent any overlay interference
        $(document).on('click', '.hero-section', function(e) {
            if ($(e.target).closest('.search-card').length === 0) {
                // Click was outside the search card, allow normal behavior
                return;
            }
            // Click was inside search card, prevent any interference
            e.stopPropagation();
        });
    </script>
</body>
</html>