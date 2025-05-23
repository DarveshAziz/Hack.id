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
      <p class="text-muted">You aren’t registered for any hackathons yet.</p>
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
  <h2 class="mb-3">Teams You’re In</h2>
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
