<?php
require __DIR__ . '/vendor/autoload.php';
include 'config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// 2️⃣ If they’re logged in, grab their info; otherwise skip the DB hit
if (isset($_SESSION['user_id'])) {
    // cast to int for safety
    $uid = (int) $_SESSION['user_id'];

    // prepare + execute a safe, injection-proof query
    $stmt = $mysqli->prepare("
        SELECT
          username,
          COALESCE(avatar, 'img/default-avatar.png') AS avatar
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Hack.id - Find Your Team. Hack the Future </title>
        <link rel="Website Icon" type="png" href="img/Logo1.png" />
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
    />

       
        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="lib/animate/animate.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body>
        <!-- Navbar & Hero Start -->
        <div class="container-fluid position-relative p-0">
            <nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0 sticky-top shadow-sm">
                <a href="index.php" class="navbar-brand d-flex align-items-center p-0">
					<!-- image logo -->
					<img src="img/logos.png" alt="Acuas logo"
						 class="me-2" style="height:48px;">

					<!-- text logo -->
					<span class="fs-3 fw-bold" style="color:#7f39e9;">
						Hack.id
					</span>
				</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0">
                        <a href="index.php" class="nav-item nav-link active">Home</a>
                        <a href="about.php" class="nav-item nav-link">About</a>
                        <a href="service.php" class="nav-item nav-link">Service</a>
                        <a href="blog.php" class="nav-item nav-link">Blog</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu m-0">
                                <a href="feature.php" class="dropdown-item">Our Feature</a>
                                <a href="product.php" class="dropdown-item">Our Product</a>
                                <a href="team.php" class="dropdown-item">Our Team</a>
                                <a href="testimonial.php" class="dropdown-item">Testimonial</a>
                                <a href="404.php" class="dropdown-item">404 Page</a>
                            </div>
                        </div>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
						<!-- avatar + username -->
						<a href="profile.php"
						   class="d-inline-flex align-items-center justify-content-center rounded-circle overflow-hidden ms-3"
						   style="width:40px;height:40px;background:#f0f3ff;">
							<img src="<?= htmlspecialchars($userRow['avatar'] ?? 'img/default-avatar.png') ?>"
								 class="img-fluid w-100 h-100 object-fit-cover" alt="Profile">
						</a>

						<span class="d-none d-lg-inline-block ms-2 me-3 fw-medium">
							<?= htmlspecialchars($_SESSION['username']) ?>
						</span>

						<!-- logout pill -->
						<a href="logout.php"
						   class="btn-mentor btn-kedua rounded-pill d-inline-flex flex-shrink-0 py-2 px-4">
						   Logout
						</a>
					<?php else: ?>
						<!-- guest sees the login pill -->
						<a href="login.php"
						   class="btn-mentor btn-pertama rounded-pill d-inline-flex flex-shrink-0 py-2 px-4 ms-3">
						   Login
						</a>
					<?php endif; ?>
                </div>
            </nav>
    <main>
        <section class="hero">
            <div class="container">
                <h1>Ask a Mentor</h1>
                <p>Connect with industry experts for personalized guidance and advice. Book a one-on-one session with a mentor who can help you achieve your goals.</p>
            </div>
        </section>

        <section class="search-section">
            <div class="container-search">
                <div class="search-form">
                    <input type="text" class="search-input" id="searchInput" placeholder="Search by name, skill, or role...">
                </div>
            </div>
        </section>

        <section class="mentors-section">
            <div class="container">
                <div class="mentors-grid" id="mentorsGrid">
                    <!-- Mentor cards will be dynamically generated here -->
                </div>
            </div>
        </section>
    </main>

    <!-- Booking Modal -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <span class="close-btn" id="closeModal">&times;</span>
            <div class="modal-header">
                <img src="" alt="" class="modal-avatar" id="modalAvatar">
                <div class="modal-title">
                    <h2 id="modalName"></h2>
                    <p id="modalTitle"></p>
                </div>
            </div>
            
            <div class="modal-body">
                <p class="modal-bio" id="modalBio"></p>
                
                <div class="mentor-skills" id="modalSkills">
                    <!-- Skills will be dynamically generated here -->
                </div>
                
                <h3>Select a Date & Time</h3>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h4 class="calendar-title" id="calendarTitle">May 2025</h4>
                        <div class="calendar-nav">
                            <button class="calendar-btn" id="prevWeek"><i class="fas fa-chevron-left"></i></button>
                            <button class="calendar-btn" id="nextWeek"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    
                    <div class="weekdays">
                        <div class="weekday">Sun</div>
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                        <div class="weekday">Sat</div>
                    </div>
                    
                    <div class="days" id="calendarDays">
                        <!-- Calendar days will be dynamically generated here -->
                    </div>
                    
                    <h4 class="mt-4 mb-3" style="margin-top: 1.5rem; color: var(--text-color);">Available Time Slots</h4>
                    <div class="time-slots" id="timeSlots">
                        <p>Please select a date to view available time slots.</p>
                    </div>
                </div>
                
                <div id="bookingForm">
                    <h3>Your Information</h3>
                    <div class="form-group">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" id="fullName" class="form-control" placeholder="Your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" class="form-control" placeholder="Your email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" class="form-control form-textarea" placeholder="What would you like to discuss with the mentor?"></textarea>
                    </div>
                    
                    <button type="button" id="submitBooking" class="btn-mentor btn-pertama">Confirm Booking</button>
                </div>
                
                <div id="confirmation" class="confirmation">
                    <i class="fas fa-check-circle confirmation-icon"></i>
                    <h2>Booking Confirmed!</h2>
                    <p>Your session has been successfully booked.</p>
                    <p id="confirmationDetails"></p>
                    <button type="button" id="closeConfirmation" class="btn-mentor btn-pertama" style="margin-top: 1rem;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
        <div class="container py-5">
            <div class="row g-5 mb-5 align-items-center">
                <div class="col-lg-7">
                    <div class="position-relative mx-auto">
                        <input class="form-control rounded-pill w-100 py-3 ps-4 pe-5" type="text" placeholder="Email address to Subscribe">
                        <button type="button" class="btn-mentor btn-kedua rounded-pill position-absolute top-0 end-0 py-2 px-4 mt-2 me-2">Subscribe</button>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-end">
                        <a class="btn-mentor btn-kedua btn-md-square rounded-circle me-3" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn-mentor btn-kedua btn-md-square rounded-circle me-3" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn-mentor btn-kedua btn-md-square rounded-circle me-3" href=""><i class="fab fa-instagram"></i></a>
                        <a class="btn-mentor btn-kedua btn-md-square rounded-circle me-0" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="footer-item d-flex flex-column">
                        <div class="footer-item">
                            <h3 class="text-white mb-4"></i>Hack.id</h3>
                            <p class="mb-3">Dolor amet sit justo amet elitr clita ipsum elitr est.Lorem ipsum dolor sit amet, consectetur adipiscing elit consectetur adipiscing elit.</p>
                        </div>
                        <div class="position-relative">
                            <input class="form-control rounded-pill w-100 py-3 ps-4 pe-5" type="text" placeholder="Enter your email">
                            <button type="button" class="btn-mentor btn-kedua rounded-pill position-absolute top-0 end-0 py-2 mt-2 me-2">SignUp</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="footer-item d-flex flex-column">
                        <h4 class="text-white mb-4">About Us</h4>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Why Choose Us</a>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Free Water Bottles</a>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Water Dispensers</a>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Bottled Water Coolers</a>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Contact us</a>
                        <a href="#"><i class="fas fa-angle-right me-2"></i> Terms & Conditions</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="footer-item d-flex flex-column">
                        <h4 class="text-white mb-4">Business Hours</h4>
                        <div class="mb-3">
                            <h6 class="text-muted mb-0">Mon - Friday:</h6>
                            <p class="text-white mb-0">09.00 am to 07.00 pm</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-0">Saturday:</h6>
                            <p class="text-white mb-0">10.00 am to 05.00 pm</p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-0">Vacation:</h6>
                            <p class="text-white mb-0">All Sunday is our vacation</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="footer-item d-flex flex-column">
                        <h4 class="text-white mb-4">Contact Info</h4>
                        <a href="#"><i class="fa fa-map-marker-alt me-2"></i> 123 Street, New York, USA</a>
                        <a href="mailto:info@example.com"><i class="fas fa-envelope me-2"></i> info@example.com</a>
                        <a href="mailto:info@example.com"><i class="fas fa-envelope me-2"></i> info@example.com</a>
                        <a href="tel:+012 345 67890"><i class="fas fa-phone me-2"></i> +012 345 67890</a>
                        <a href="tel:+012 345 67890" class="mb-3"><i class="fas fa-print me-2"></i> +012 345 67890</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->
    
    <!-- Copyright Start -->
    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-md-0">
                    <span class="text-body"><a href="#" class="border-bottom text-white"><i class="fas fa-copyright text-light me-2"></i>Hack.id</a> All right reserved.</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Copyright End -->
    <!-- Back to Top -->
    <a href="#" class="btn btn-secondary btn-lg-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   
    <script src="./js/calendar.js"></script>
</html>