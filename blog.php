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
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
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
    <?php include './includes/header.php'; ?>

    <!-- Header Start -->
    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Blog</h4>
            <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="about.php">About Us</a></li>
                <li class="breadcrumb-item active text-primary">Blog</li>
            </ol>
        </div>
    </div>
    <!-- Header End -->
    <!-- Navbar & Hero End -->

    <!-- Modal Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h4 class="modal-title mb-0" id="exampleModalLabel">Search by keyword</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="input-group-text btn border p-3"><i class="fa fa-search text-white"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Search End -->
    <!-- Blog Start -->
    <div class="container-fluid blog pb-5">
        <div class="container pb-5">
            <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                <h4 class="text-uppercase text-primary">Our Blog</h4>
                <h1 class="display-3 text-capitalize mb-3">Latest Blog & News</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="blog-item">
                        <div class="blog-img">
                            <img src="img/3.png" class="img-fluid rounded-top w-100" alt="">
                            <div class="blog-date px-4 py-2"><i class="fa fa-calendar-alt me-1"></i> May 21 2025</div>
                        </div>
                        <div class="blog-content rounded-bottom p-4">
                            <a href="#" class="h4 d-inline-block mb-3">Dive into innovation: The AI in Action Google Cloud</a>
                            <p>Learn how to register, access key resources, and build innovative projects with Google Cloud, MongoDB, and GitLab.</p>
                            <a href="blog1.html" class="fw-bold text-secondary">Read More <i class="fa fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="blog-item">
                        <div class="blog-img">
                            <img src="img/2.png" class="img-fluid rounded-top w-100" alt="">
                            <div class="blog-date px-4 py-2"><i class="fa fa-calendar-alt me-1"></i> Jan 12 2025</div>
                        </div>
                        <div class="blog-content rounded-bottom p-4">
                            <a href="#" class="h4 d-inline-block mb-3">How to get executive buy-in for your internal hackathons</a>
                            <p>Learn how to secure leadership approval for your internal hackathon and get access to a business case template.</p>
                            <a href="blog2.html" class="fw-bold text-secondary">Read More <i class="fa fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="blog-item">
                        <div class="blog-img">
                            <img src="img/1.png" class="img-fluid rounded-top w-100" alt="">
                            <div class="blog-date px-4 py-2"><i class="fa fa-calendar-alt me-1"></i> Jan 12 2025</div>
                        </div>
                        <div class="blog-content rounded-bottom p-4">
                            <a href="#" class="h4 d-inline-block mb-3">Increase’s hackathon win took him from Nigeria</a>
                            <p>Get to know Increase and learn how winning a hackathon took him to the US for the first time!</p>
                            <a href="blog3.html" class="fw-bold text-secondary">Read More <i class="fa fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Blog End -->

    <!-- Testimonial Start -->
    <div class="container-fluid testimonial pb-5">
        <div class="container pb-5">
            <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                <h4 class="text-uppercase text-primary">Testimonials</h4>
                <h1 class="display-3 text-capitalize mb-3">Our clients reviews.</h1>
            </div>
            <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.3s">
                <div class="testimonial-item text-center p-4">
                    <p>Hack.id was an incredible experience. The organizers did a fantastic job, and the mentorship was top-notch. I gained valuable insights and connected with amazing developers.
                    </p>
                    <div class="d-flex justify-content-center mb-4">
                        <img src="img/testimonial-1.jpg" class="img-fluid border border-4 border-primary" style="width: 100px; height: 100px; border-radius: 50px;" alt="">
                    </div>
                    <div class="d-block">
                        <h4 class="text-dark">Dinda Sulistiani</h4>
                        <p class="m-0 pb-3">Product Manager</p>
                        <div class="d-flex justify-content-center text-secondary">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="testimonial-item text-center p-4">
                    <p>An intense and inspiring 48 hours. I had the chance to turn my ideas into a working prototype and pitch it in front of experienced judges. A truly empowering experience.
                    </p>
                    <div class="d-flex justify-content-center mb-4">
                        <img src="img/testimonial-2.jpg" class="img-fluid border border-4 border-primary" style="width: 100px; height: 100px; border-radius: 50px;" alt="">
                    </div>
                    <div class="d-block">
                        <h4 class="text-dark">Kevin Pratama</h4>
                        <p class="m-0 pb-3">Front End</p>
                        <div class="d-flex justify-content-center text-secondary">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="testimonial-item text-center p-4">
                    <p>The mentors were incredibly helpful, and the community was so supportive. I learned more in two days than I expected. Highly recommended for anyone in tech!
                    <div class="d-flex justify-content-center mb-4">
                        <img src="img/testimonial-3.jpg" class="img-fluid border border-4 border-primary" style="width: 100px; height: 100px; border-radius: 50px;" alt="">
                    </div>
                    <div class="d-block">
                        <h4 class="text-dark">Ayu Kartika</h4>
                        <p class="m-0 pb-3">UI/UX Designer</p>
                        <div class="d-flex justify-content-center text-secondary">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
                <div class="testimonial-item text-center p-4">
                    <p>Joining the Hackathon was one of the best decisions I've made. It pushed me out of my comfort zone and helped me grow both technically and creatively.
                    </p>
                    <div class="d-flex justify-content-center mb-4">
                        <img src="img/testimonial-4.jpg" class="img-fluid border border-4 border-primary" style="width: 100px; height: 100px; border-radius: 50px;" alt="">
                    </div>
                    <div class="d-block">
                        <h4 class="text-dark">Rizky Chandra</h4>
                        <p class="m-0 pb-3">Back End</p>
                        <div class="d-flex justify-content-center text-secondary">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Testimonial End -->

    <?php include 'includes/footer.php'; ?>



    <!-- Back to Top -->
    <a href="#" class="btn btn-secondary btn-lg-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>


    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>