<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upcoming Hackathons • Hack.id</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Fonts + Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>


<!-- ===== Header: identical to index.php ===== -->
<div class="container-fluid position-relative p-0">
    <nav class="navbar sticky-top shadow-sm navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0">
        <a href="index.php" class="navbar-brand d-flex align-items-center p-0">
            <img src="img/logos.png" alt="Acuas logo" class="me-2" style="height:48px;">
            <span class="fs-3 fw-bold" style="color:#7f39e9;">Hack.id</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="fa fa-bars"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto py-0">
                <a href="index.php"       class="nav-item nav-link">Home</a>
                <a href="about.html"      class="nav-item nav-link">About</a>
                <a href="service.html"    class="nav-item nav-link">Service</a>
                <a href="blog.html"       class="nav-item nav-link">Blog</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                    <div class="dropdown-menu m-0">
                        <a href="feature.html"     class="dropdown-item">Our Feature</a>
                        <a href="product.html"     class="dropdown-item">Our Product</a>
                        <a href="team.html"        class="dropdown-item">Our Team</a>
                        <a href="testimonial.html" class="dropdown-item">Testimonial</a>
                        <a href="404.html"         class="dropdown-item">404 Page</a>
                    </div>
                </div>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>

            <div class="d-none d-xl-flex me-3">
                <div class="d-flex flex-column pe-3 border-end border-primary">
                    <span class="text-body">Get Free Delivery</span>
                    <a href="tel:+4733378901"><span class="text-primary">Free: + 0123 456 7890</span></a>
                </div>
            </div>

            <button class="btn btn-primary btn-md-square d-flex flex-shrink-0 mb-3 mb-lg-0 rounded-circle me-3"
                    data-bs-toggle="modal" data-bs-target="#searchModal">
                <i class="fas fa-search"></i>
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="d-inline-flex align-items-center justify-content-center
                         rounded-circle overflow-hidden ms-3"
                   style="width:40px;height:40px;background:#f0f3ff;">
                    <img src="<?= htmlspecialchars($_SESSION['avatar'] ?? 'img/default-avatar.png') ?>"
                         class="img-fluid w-100 h-100 object-fit-cover" alt="Profile">
                </a>
                <span class="d-none d-lg-inline-block ms-2 me-3 fw-medium">
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php"
                   class="btn btn-secondary rounded-pill d-inline-flex flex-shrink-0 py-2 px-4">Logout</a>
            <?php else: ?>
                <a href="login.php"
                   class="btn btn-primary rounded-pill d-inline-flex flex-shrink-0 py-2 px-4 ms-3">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</div>
<!-- ===== /Header ===== -->

<!-- ===== Page header ===== -->
<div class="container-fluid page-header wow fadeIn" data-wow-delay="0.1s">
    <div class="container text-center py-5">
        <h1 class="display-4 text-white mb-3 animated slideInDown">Upcoming Hackathons</h1>
        <p class="lead text-white-50 animated slideInDown" style="animation-delay:.3s">
            Discover open challenges, then head to “Find Your Team” to assemble your squad.
        </p>
    </div>
</div>

<!-- ===== Hackathon cards ===== -->
<div class="container py-5">
    <div class="row g-4">

<?php
// fetch data
$res  = $mysqli->query("SELECT * FROM hackathons ORDER BY id DESC");
while ($h = $res->fetch_assoc()):
    $themes = json_decode($h['themes'], true) ?: [];
?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($h['link']) ?>" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($h['image_src']) ?>" class="card-img-top"
                         alt="<?= htmlspecialchars($h['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-2"><?= htmlspecialchars($h['title']) ?></h5>
                        <p class="small text-muted mb-1">
                            <?= htmlspecialchars($h['status']) ?> •
                            <?= htmlspecialchars($h['participants']) ?>
                        </p>
                        <p class="small text-muted mb-1">Hosted by <?= htmlspecialchars($h['host']) ?></p>
                        <p class="small mb-2"><i class="bi bi-calendar-event me-1"></i>
                            <?= htmlspecialchars($h['submission_period']) ?></p>

                        <?php foreach ($themes as $t): ?>
                            <span class="badge bg-secondary bg-opacity-25 text-white fw-normal me-1 mb-1">
                                <?= htmlspecialchars($t) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <span class="fw-bold text-primary"><?= htmlspecialchars($h['prize']) ?></span>
                    </div>
                </div>
            </a>
        </div>
<?php endwhile; ?>

    </div>
</div>

<!-- ===== Footer (reuse the same footer block if you like) ===== -->
<?php include 'includes/footer.php'; ?>

<!-- ===== Scripts ===== -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></
