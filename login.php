<?php
include 'config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = trim($_POST['identifier'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare(
        "SELECT id, username, password FROM users
         WHERE username=? OR email=? LIMIT 1"
    );
    $stmt->bind_param('ss', $id, $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $errors[] = 'Wrong username / e-mail / password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login – Acuas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Fonts + Icons + CSS libs (unchanged) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Playfair+Display:wght@400..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="lib/animate/animate.min.css" rel="stylesheet" />
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
</head>

<body class="pt-5">
    <?php include 'includes/header.php'; ?>

    <!-- ===== Login form ===== -->
    <div class="container py-5">
        <div class="row justify-content-center wow fadeInUp" data-wow-delay="0.2s">
            <div class="col-md-6 col-lg-5">
                <div class="bg-light rounded p-5">

                    <?php if ($errors): ?>
                        <div class="alert alert-danger mb-4">
                            <?= implode('<br>', $errors) ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="mb-4 text-center">Welcome Back</h3>
                    <form action="" method="post">
                        <div class="form-floating mb-3">
                            <input name="identifier" class="form-control"
                                   placeholder="Username or Email" required>
                            <label>Username or Email</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" name="password" class="form-control"
                                   placeholder="Password" required>
                            <label>Password</label>
                        </div>
                        <button class="btn btn-primary w-100 py-3">Log In</button>
                        <p class="text-center mt-3 mb-0">Need an account?
                           <a href="register.php">Register</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== /Login form ===== -->

    <!-- ===== Footer & scripts (copied verbatim) ===== -->
    <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
        <!-- … all the same footer markup … -->
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
