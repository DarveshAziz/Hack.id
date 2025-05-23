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
<?php include 'includes/header.php'; ?>
<head>
  <meta charset="utf-8">
  <title>Register – Hack.id</title>
  <link rel="Website Icon" type="png" href="img/Logo1.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Google Fonts + Icons + CSS -->
  <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="lib/animate/animate.min.css" rel="stylesheet" />
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
  <link href="css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
  <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
</head>
<body>

<!-- Register Form -->
<div class="container py-5 mt-5">
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
          <div class="form-floating mb-3 position-relative">
            <input type="password" name="password" id="pass1" class="form-control"
                   placeholder="Password" required minlength="6">
            <label for="pass1">Password</label>
            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor:pointer;z-index:2;" onclick="togglePassword('pass1','togglePass1')">
              <i class="fa fa-eye" id="togglePass1"></i>
            </span>
          </div>
          <div class="form-floating mb-4 position-relative">
            <input type="password" name="pass_confirm" id="pass2" class="form-control"
                   placeholder="Confirm Password" required>
            <label for="pass2">Confirm Password</label>
            <span class="position-absolute top-50 end-0 translate-middle-y me-3" style="cursor:pointer;z-index:2;" onclick="togglePassword('pass2','togglePass2')">
              <i class="fa fa-eye" id="togglePass2"></i>
            </span>
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



<!-- JS Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/counterup/counterup.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
<script>
  function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
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
