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
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">About Us</h4>
            <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active text-primary">About Us</li>
            </ol>
        </div>
    </div>

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

    <!-- About Start -->
    <div class="container-fluid about overflow-hidden py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s">
                    <div class="about-img rounded h-100">
                        <img src="img/logo2.png" class="img-fluid rounded h-100 w-100" style="object-fit: cover;" alt="">
                        <div class="about-exp"><span>20 Years Experiance</span></div>
                    </div>
                </div>
                <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.2s">
                    <div class="about-item">
                        <h4 class="text-primary text-uppercase">About Us</h4>
                        <h1 class="display-3 mb-3">Empowering the Next Tech Leaders.</h1>
                        <p class="mb-4">
                            Hack.id is your ultimate platform for finding the perfect team to conquer hackathons.
                            Whether you're a developer, designer, or innovator, we connect you with like-minded individuals to build, learn, and win together.
                        </p>
                        <div class="bg-light rounded p-4 mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex">
                                        <div class="pe-4">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;"><i class="fas fa-user-friends text-white fa-2x"></i></div>
                                        </div>
                                        <div class="">
                                            <a href="#" class="h4 d-inline-block mb-3">Presticious Team</a>
                                            <p class="mb-0">Hack.id connected me with an amazing team! We tackled a tough challange and ended up winning</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-light rounded p-4 mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex">
                                        <div class="pe-4">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;"><i class="fas fa-chart-line text-white fa-2x"></i></div>
                                        </div>
                                        <div class="">
                                            <a href="#" class="h4 d-inline-block mb-3">Our Impact</a>
                                            <p class="mb-0">We empower Indonesia's brightiest minds by connecting them to opportunities and collaborators</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="btn btn-secondary rounded-pill py-3 px-5">Read More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!-- Team Start -->
    <div class="container-fluid team pb-5">
        <div class="container pb-5">
            <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                <h4 class="text-primary">Recommended People</h4>
                <h1 class="display-3 text-capitalize mb-3">The most popular Mentor for Hackathon</h1>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="team-item p-4">
                        <div class="team-inner rounded">
                            <div class="team-img">
                                <img src="img/Richard.png" class="img-fluid rounded-top w-100" alt="Image">
                                <div class="team-share">
                                    <a class="btn btn-secondary btn-md-square rounded-pill text-white mx-1" href=""><i class="fas fa-share-alt"></i></a>
                                </div>
                                <div class="team-icon rounded-pill py-2 px-2">
                                    <a class="btn btn-secondary btn-sm-square rounded-pill mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-twitter"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-linkedin-in"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                            <div class="bg-light rounded-bottom text-center py-4">
                                <h4 class="mb-3">Richard</h4>
                                <p class="mb-0">Product Manager</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="team-item p-4">
                        <div class="team-inner rounded">
                            <div class="team-img">
                                <img src="img/Brandon.png" class="img-fluid rounded-top w-100" alt="Image">
                                <div class="team-share">
                                    <a class="btn btn-secondary btn-md-square rounded-pill text-white mx-1" href=""><i class="fas fa-share-alt"></i></a>
                                </div>
                                <div class="team-icon rounded-pill py-2 px-2">
                                    <a class="btn btn-secondary btn-sm-square rounded-pill mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-twitter"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-linkedin-in"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                            <div class="bg-light rounded-bottom text-center py-4">
                                <h4 class="mb-3">Brandon Geraldo</h4>
                                <p class="mb-0">UI/UX & Front End</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="team-item p-4">
                        <div class="team-inner rounded">
                            <div class="team-img">
                                <img src="img/Haris.png" class="img-fluid rounded-top w-100" alt="Image">
                                <div class="team-share">
                                    <a class="btn btn-secondary btn-md-square rounded-pill text-white mx-1" href=""><i class="fas fa-share-alt"></i></a>
                                </div>
                                <div class="team-icon rounded-pill py-2 px-2">
                                    <a class="btn btn-secondary btn-sm-square rounded-pill mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-twitter"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-linkedin-in"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                            <div class="bg-light rounded-bottom text-center py-4">
                                <h4 class="mb-3">Malvinshah Haris</h4>
                                <p class="mb-0">Back End & Security</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.8s">
                    <div class="team-item p-4">
                        <div class="team-inner rounded">
                            <div class="team-img">
                                <img src="img/Darvesh.png" class="img-fluid rounded-top w-100" alt="Image">
                                <div class="team-share">
                                    <a class="btn btn-secondary btn-md-square rounded-pill text-white mx-1" href=""><i class="fas fa-share-alt"></i></a>
                                </div>
                                <div class="team-icon rounded-pill py-2 px-2">
                                    <a class="btn btn-secondary btn-sm-square rounded-pill mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-twitter"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-linkedin-in"></i></a>
                                    <a class="btn btn-secondary btn-sm-square rounded-pill me-1" href=""><i class="fab fa-instagram"></i></a>
                                </div>
                            </div>
                            <div class="bg-light rounded-bottom text-center py-4">
                                <h4 class="mb-3">Darvesh Aziz</h4>
                                <p class="mb-0">Back End & Security</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Team End -->

    <!-- Fact Counter -->
    <div class="container-fluid counter py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="counter-item">
                        <div class="counter-item-icon mx-auto">
                            <i class="fas fa-trophy fa-3x text-white"></i>
                        </div>
                        <h4 class="text-white my-4">Competition</h4>
                        <div class="counter-counting">
                            <span class="text-white fs-2 fw-bold" data-toggle="counter-up">456</span>
                            <span class="h1 fw-bold text-white">+</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="counter-item">
                        <div class="counter-item-icon mx-auto">
                            <i class="fas fa-chalkboard-teacher fa-3x text-white"></i>
                        </div>
                        <h4 class="text-white my-4">Mentor</h4>
                        <div class="counter-counting">
                            <span class="text-white fs-2 fw-bold" data-toggle="counter-up">513</span>
                            <span class="h1 fw-bold text-white">+</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="counter-item">
                        <div class="counter-item-icon mx-auto">
                            <i class="fas fa-users fa-3x text-white"></i>
                        </div>
                        <h4 class="text-white my-4">Teams</h4>
                        <div class="counter-counting">
                            <span class="text-white fs-2 fw-bold" data-toggle="counter-up">53</span>
                            <span class="h1 fw-bold text-white">+</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.8s">
                    <div class="counter-item">
                        <div class="counter-item-icon mx-auto">
                            <i class="fas fa-heart fa-3x text-white"></i>
                        </div>
                        <h4 class="text-white my-4">Years Experiance</h4>
                        <div class="counter-counting">
                            <span class="text-white fs-2 fw-bold" data-toggle="counter-up">17</span>
                            <span class="h1 fw-bold text-white">+</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fact Counter -->

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