<?php
// register.php

// 1️⃣ Bootstrap & config
require __DIR__ . '/vendor/autoload.php';
include 'config.php';

// 2️⃣ Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 3️⃣ Prepare Google OAuth client
$client = new Google\Client;
$client->setClientId("844878097440-7fd98ruf2jkfhhalfrb4aut9nda5jhd7.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/Hack.id/oauth2callback.php");
$client->addScope("email");
$client->addScope("profile");
$googleUrl = $client->createAuthUrl();

// 4️⃣ Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']     ?? '');
    $email    = trim($_POST['email']        ?? '');
    $pass1    = $_POST['password']          ?? '';
    $pass2    = $_POST['pass_confirm']      ?? '';

    // basic validations
    if (!$username || !$email || !$pass1 || !$pass2) {
        $errors[] = 'All fields are required.';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid e-mail address.';
    }
    if ($pass1 !== $pass2) {
        $errors[] = 'Passwords don’t match.';
    }

    // try to insert
    if (empty($errors)) {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("
            INSERT INTO users (username, email, password)
            VALUES (?,?,?)
        ");
        $stmt->bind_param('sss', $username, $email, $hash);

        if ($stmt->execute()) {
            // auto-login
            $_SESSION['user_id']  = $stmt->insert_id;
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'That username or e-mail is already taken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register – Hack.id</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Google Fonts + Icons + CSS -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="lib/animate/animate.min.css" rel="stylesheet" />
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- Hero -->
<div class="container-fluid page-header wow fadeIn mt-5" data-wow-delay="0.1s">
  <div class="container text-center py-5">
    <h1 class="display-4 text-white mb-3 animated slideInDown">Register</h1>
    <nav aria-label="breadcrumb animated slideInDown">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item text-primary" aria-current="page">Register</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Register Form -->
<div class="container py-5">
  <div class="row justify-content-center wow fadeInUp" data-wow-delay="0.2s">
    <div class="col-md-6 col-lg-5">
      <div class="bg-light rounded p-5">

        <?php if ($errors): ?>
          <div class="alert alert-danger mb-4">
            <?= implode('<br>', $errors) ?>
          </div>
        <?php endif; ?>

        <h3 class="mb-4 text-center">Create Account</h3>
        <form action="" method="post">
          <div class="form-floating mb-3">
            <input name="username" id="username" class="form-control"
                   placeholder="Username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required>
            <label for="username">Username</label>
          </div>
          <div class="form-floating mb-3">
            <input type="email" name="email" id="email" class="form-control"
                   placeholder="name@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
            <label for="email">Email address</label>
          </div>
          <div class="form-floating mb-3">
            <input type="password" name="password" id="pass1" class="form-control"
                   placeholder="Password" required minlength="6">
            <label for="pass1">Password</label>
          </div>
          <div class="form-floating mb-4">
            <input type="password" name="pass_confirm" id="pass2" class="form-control"
                   placeholder="Confirm Password" required>
            <label for="pass2">Confirm Password</label>
          </div>
          <button class="btn btn-primary w-100 py-3 mb-3">Register</button>

          <!-- Social/Register with Google -->
          <div class="text-center">
            <p class="mb-2">Or register with</p>
            <div class="d-flex justify-content-center gap-3">
              <a href="<?= htmlspecialchars($googleUrl) ?>"
                 class="btn btn-outline-danger btn-lg rounded-circle">
                <i class="fab fa-google"></i>
              </a>
              <a href="#" class="btn btn-outline-dark btn-lg rounded-circle">
                <i class="fab fa-github"></i>
              </a>
              <a href="#" class="btn btn-outline-primary btn-lg rounded-circle">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>
          </div>

          <p class="text-center mt-3 mb-0">
            Already registered? <a href="login.php">Log in</a>
          </p>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- JS Scripts -->
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
