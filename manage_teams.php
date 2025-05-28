<?php
/* manage_teams.php — View your registrations & teams */
include 'config.php';
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}
$uid = (int)$_SESSION['user_id'];

// fetch all hackathons this user registered for
$stmt = $mysqli->prepare("
  SELECT h.id,h.title,h.slug
    FROM registrations r
    JOIN hackathons h ON h.id=r.hackathon_id
   WHERE r.user_id=?
   ORDER BY h.title
");
$stmt->bind_param('i',$uid);
$stmt->execute();
$reg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// handle team creation
if (isset($_GET['create']) && in_array($_GET['create'], array_column($reg,'id'))) {
  $hid = (int)$_GET['create'];
  if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['team_name'])) {
    $name = trim($_POST['team_name']);
    // insert team + auto-join creator
    $ins = $mysqli->prepare("
      INSERT INTO teams (name,hackathon_id,creator_id)
      VALUES (?,?,?)
    ");
    $ins->bind_param('sii',$name,$hid,$uid);
    $ins->execute();
    $teamId = $ins->insert_id;
    $ins->close();
    // join creator
    $jm = $mysqli->prepare("
      INSERT INTO team_members (team_id,user_id) VALUES (?,?)
    ");
    $jm->bind_param('ii',$teamId,$uid);
    $jm->execute();
    $jm->close();
    header("Location: manage_teams.php");
    exit;
  }
}

// fetch teams user is in
$stmt = $mysqli->prepare("
  SELECT t.id,t.name,h.title
    FROM team_members m
    JOIN teams t ON t.id=m.team_id
    JOIN hackathons h ON h.id=t.hackathon_id
   WHERE m.user_id=?
   ORDER BY h.title,t.name
");
$stmt->bind_param('i',$uid);
$stmt->execute();
$teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Teams • Hack.id</title>
  <link rel="Website Icon" type="png" href="img/Logo1.png" />
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css"        rel="stylesheet">
  <style>
/* Enhanced CSS for manage_teams.php */

/* Global Styles */
body {
  font-family: 'Poppins', sans-serif;
  background: #222222;
  min-height: 100vh;
  color: #ffffff;
}

/* Headings */
h1 {
  color: #ffffff;
  font-weight: 700;
  text-align: center;
  margin-bottom: 2rem;
  position: relative;
  padding-bottom: 1rem;
}

h1::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 4px;
  background: #452499;
  border-radius: 2px;
}

h2 {
  color: #ffffff;
  font-weight: 600;
  margin-bottom: 1.5rem;
  position: relative;
  padding-left: 1rem;
}

h2::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 2rem;
  background: #452499;
  border-radius: 2px;
}

/* Card Enhancements */
.card {
  border: none;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  transition: all 0.3s ease;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(69, 36, 153, 0.3);
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(69, 36, 153, 0.3);
  border-color: rgba(69, 36, 153, 0.5);
}

.card-body {
  padding: 2rem;
  position: relative;
  color: #ffffff;
}

.card-body::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: #452499;
}

.card-header {
  background: #452499;
  color: white;
  border: none;
  padding: 1.5rem 2rem;
  font-weight: 600;
  font-size: 1.1rem;
}

/* Button Styles */
.btn {
  border-radius: 25px;
  padding: 0.6rem 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.875rem;
}

.btn-primary {
  background: #452499;
  border: none;
  box-shadow: 0 4px 15px rgba(69, 36, 153, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(69, 36, 153, 0.5);
  background: #5a2fb8;
}

.btn-outline-primary {
  border: 2px solid #452499;
  color: #fff;
  background: #452499;
}

.btn-outline-primary:hover {
  background: #452499;
  border-color: #452499;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(69, 36, 153, 0.3);
}

.btn-sm {
  padding: 0.4rem 1rem;
  font-size: 0.8rem;
}

.btn-link {
  color: #452499;
  text-decoration: none;
  font-weight: 500;
}

.btn-link:hover {
  color: #5a2fb8;
  text-decoration: underline;
}

/* Form Enhancements */
.form-control {
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
  font-size: 1rem;
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.form-control:focus {
  border-color: #452499;
  box-shadow: 0 0 0 0.2rem rgba(69, 36, 153, 0.25);
  transform: scale(1.02);
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.form-control::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.form-label {
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

/* Card Title Enhancements */
.card-body h5 {
  color: #ffffff;
  font-weight: 600;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

/* Text Muted Enhancement */
.text-muted {
  color: rgba(255, 255, 255, 0.6) !important;
  font-style: italic;
  background: rgba(255, 255, 255, 0.05);
  padding: 1rem;
  border-radius: 10px;
  border-left: 4px solid #452499;
}

/* Row and Column Spacing */
.g-4 {
  --bs-gutter-x: 2rem;
  --bs-gutter-y: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    margin: 1rem;
    padding: 1.5rem;
    border-radius: 15px;
  }
  
  h1 {
    font-size: 2rem;
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  .btn {
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .btn:last-child {
    margin-bottom: 0;
  }
}

/* Animation for cards */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card {
  animation: slideInUp 0.6s ease forwards;
}

.card:nth-child(2) {
  animation-delay: 0.1s;
}

.card:nth-child(3) {
  animation-delay: 0.2s;
}

.card:nth-child(4) {
  animation-delay: 0.3s;
}

/* Loading state for better UX */
.card-body {
  position: relative;
}

/* Empty state styling */
.text-muted:only-child {
  text-align: center;
  font-size: 1.1rem;
  background: rgba(255, 255, 255, 0.05);
  border: 2px dashed rgba(69, 36, 153, 0.5);
}

/* Focus states for accessibility */
.btn:focus,
.form-control:focus {
  outline: none;
}

/* Paragraph text color in cards */
.card-body p {
  color: rgba(255, 255, 255, 0.8);
}

/* Print styles */
@media print {
  body {
    background: white;
    color: black;
  }
  
  .container {
    background: white;
    box-shadow: none;
  }
  
  .btn {
    display: none;
  }
}
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container py-5">
  <h1 class="mb-4">Your Hackathons & Teams</h1>

  <!-- Registered hackathons -->
  <div class="row g-4 mb-5">
    <?php foreach ($reg as $h): ?>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5><?= htmlspecialchars($h['title']) ?></h5>
            <a href="manage_teams.php?create=<?= $h['id'] ?>"
               class="btn btn-sm btn-primary">Create Team</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($reg)): ?>
      <p class="text-muted">You aren't registered for any hackathons yet.</p>
    <?php endif; ?>
  </div>

  <!-- Team creation form -->
  <?php if (isset($_GET['create']) && in_array($_GET['create'], array_column($reg,'id'))): 
    $cur = array_filter($reg, fn($x)=>$x['id']===(int)$_GET['create']);
    $cur = array_shift($cur);
  ?>
    <div class="card mb-5">
      <div class="card-header">
        Create a team for <?= htmlspecialchars($cur['title']) ?>
      </div>
      <div class="card-body">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Team Name</label>
            <input type="text" name="team_name"
                   class="form-control" required>
          </div>
          <button class="btn btn-primary">Create Team</button>
          <a href="manage_teams.php" class="btn btn-link">Cancel</a>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Your teams -->
  <h2 class="mb-3">Teams You're In</h2>
  <div class="row g-4">
    <?php foreach ($teams as $t): ?>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5><?= htmlspecialchars($t['name']) ?></h5>
            <p class="text-muted">
              Hackathon: <?= htmlspecialchars($t['title']) ?>
            </p>
            <a href="view_team.php?id=<?= $t['id'] ?>"
               class="btn btn-sm btn-outline-primary">Manage</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($teams)): ?>
      <p class="text-muted">You have not joined any teams yet.</p>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>