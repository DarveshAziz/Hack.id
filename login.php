<?php
include 'config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;

$client->setClientId("844878097440-7fd98ruf2jkfhhalfrb4aut9nda5jhd7.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/Hack.id/oauth2callback.php");

$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

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
<?php include 'includes/header.php'; ?>
<head>
    <meta charset="utf-8">
    <title>Login – Hack.id</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Hack.id - Find Your Team. Hack the Future </title>
    <link rel="Website Icon" type="png" href="img/Logo1.png" />
    <!-- Google Fonts + Icons + CSS libs (unchanged) -->
       <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"
      rel="stylesheet" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"
      rel="stylesheet" />
    <link href="lib/animate/animate.min.css" rel="stylesheet" />
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
</head>

<body class="pt-5 mt-5">
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
                        <div class="form-floating mb-4 position-relative">
                            <input type="password" name="password" class="form-control" id="passwordInput"
                                   placeholder="Password" required>
                            <label>Password</label>
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor:pointer;z-index:2;" onclick="togglePassword()">
                                <i class="fa fa-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>

                        <button class="btn btn-primary w-100 py-3">Log In</button>

                        <!-- ===== Social login icons ===== -->
                        <div class="text-center mt-4">
                            <p class="mb-2">Or log in with</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="<?= htmlspecialchars($url) ?>" class="btn btn-outline-danger btn-lg rounded-circle">
                                    <i class="fab fa-google"></i>
                                </a>
                                <a href="" class="btn btn-outline-dark btn-lg rounded-circle">
                                    <i class="fab fa-github"></i>
                                </a>
                                <a href="" class="btn btn-outline-primary btn-lg rounded-circle">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            </div>
                        </div>

                        <p class="text-center mt-3 mb-0">
                           Need an account? <a href="register.php">Register</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== /Login form ===== -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePasswordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
