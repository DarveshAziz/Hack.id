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
    <?php include 'includes/header.php'; ?>
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
                        <input type="text" id="fullName" class="form-control-mentor" placeholder="Your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" class="form-control-mentor" placeholder="Your email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" class="form-control-mentor form-textarea" placeholder="What would you like to discuss with the mentor?"></textarea>
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

    <?php include 'includes/footer.php'; ?>
    <!-- Back to Top -->
    <a href="#" class="btn btn-secondary btn-lg-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   
    <script src="./js/calendar.js"></script>
</html>