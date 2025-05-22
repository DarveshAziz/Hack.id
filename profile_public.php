<?php
include 'config.php';

/* ── grab the id param ─────────────────────────────── */
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$viewId) { http_response_code(404); exit('User not found'); }

/* ── fetch core profile data ────────────────────────── */
$user = $mysqli->query("
    SELECT u.username,
           COALESCE(up.display_name,u.username)  AS display_name,
           up.headline, up.about, up.location,
           up.website,  up.github, up.linkedin,
           COALESCE(u.avatar,'img/default-avatar.png') AS avatar
    FROM users u
    LEFT JOIN user_profile up ON up.user_id = u.id
    WHERE u.id = $viewId
")->fetch_assoc();

if (!$user) { http_response_code(404); exit('User not found'); }

/* ── fetch ALL rated skills for this user ───────────── */
$skills = $mysqli->query("
    SELECT s.category, s.subcategory, s.name, us.level
    FROM user_skill us
    JOIN skills s ON s.id = us.skill_id
    WHERE us.user_id = $viewId AND us.level > 0
    ORDER BY s.category, s.subcategory, us.level DESC, s.name
")->fetch_all(MYSQLI_ASSOC);

/* top-3 highest levels */
$top3 = array_slice($skills, 0, 3);

$myOwnedTeams = [];
if (isset($_SESSION['user_id'])) {
    $me = (int) $_SESSION['user_id'];
    $stmt = $mysqli->prepare("
        SELECT id, name, logo_url
          FROM teams
         WHERE creator_id = ?
    ");
    $stmt->bind_param('i', $me);
    $stmt->execute();
    $myOwnedTeams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ② Fetch teams *they* own (to offer “request to join”)
$theirOwnedTeams = [];
{
    $them = $viewId; 
    $stmt = $mysqli->prepare("
        SELECT id, name, logo_url
          FROM teams
         WHERE creator_id = ?
    ");
    $stmt->bind_param('i', $them);
    $stmt->execute();
    $theirOwnedTeams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($user['display_name']) ?> • Hack.id</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"      rel="stylesheet">
<style>
.skill-pill{
  display:inline-flex;align-items:center;margin:.2rem .25rem;
  border:1px solid var(--bs-primary);border-radius:20px;
  padding:.15rem .55rem .15rem .35rem;font-size:.8rem;
}
.skill-pill .circle{
  width:22px;height:22px;border-radius:50%;margin-right:.4rem;background:#eee;
  position:relative;flex-shrink:0;
}
.skill-pill .circle::after{
  content:'';position:absolute;inset:0;border-radius:50%;
  background:conic-gradient(var(--bs-primary) calc(var(--pct)*1%), #eee 0);
}
</style>
</head>
<body class="pt-5">

<?php include 'includes/header.php'; ?>`

<div class="container py-5">

  <!-- profile header -->
  <div class="card shadow-sm mb-5">
    <div class="card-body d-md-flex">
      <img src="<?= htmlspecialchars($user['avatar']) ?>"
           class="rounded-circle me-4 mb-3 mb-md-0"
           style="width:96px;height:96px;object-fit:cover">
      <div>
        <h2 class="mb-1"><?= htmlspecialchars($user['display_name']) ?></h2>
        <?php if($user['headline']): ?>
          <p class="text-muted mb-2"><?= htmlspecialchars($user['headline']) ?></p>
        <?php endif; ?>
        <?php if($user['location']): ?>
          <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i>
             <?= htmlspecialchars($user['location']) ?></p>
        <?php endif; ?>

        <!-- socials -->
        <div class="mt-3">
          <?php if($user['website']): ?>
            <a href="<?= htmlspecialchars($user['website']) ?>"
               target="_blank" class="me-3"><i class="fas fa-globe me-1"></i>Website</a>
          <?php endif;?>
          <?php if($user['github']): ?>
            <a href="https://github.com/<?= ltrim($user['github'],'https://github.com/')?>"
               target="_blank" class="me-3"><i class="fab fa-github me-1"></i>GitHub</a>
          <?php endif;?>
          <?php if($user['linkedin']): ?>
            <a href="<?= htmlspecialchars($user['linkedin']) ?>"
               target="_blank"><i class="fab fa-linkedin me-1"></i>LinkedIn</a>
          <?php endif;?>
        </div>
      </div>
    </div>

    <!-- top-3 skills -->
    <?php if($top3): ?>
    <div class="border-top px-4 py-3">
      <?php foreach ($top3 as $t): ?>
        <span class="skill-pill" style="--pct:<?= $t['level'] ?>;">
          <span class="circle"></span><?= htmlspecialchars($t['name']) ?>
        </span>
      <?php endforeach;?>
    </div>
    <?php endif; ?>
  </div>
  <?php if (isset($_SESSION['user_id']) && $myOwnedTeams): ?>
  <div class="nav-item dropdown ms-3">
    <a class="nav-link dropdown-toggle" href="#" id="inviteDropdown"
       data-bs-toggle="dropdown" aria-expanded="false">
      Invite to Team
    </a>
    <ul class="dropdown-menu" aria-labelledby="inviteDropdown">
      <?php foreach($myOwnedTeams as $team): ?>
        <li>
          <form action="invite_team.php" method="post" class="m-0 p-0">
            <input type="hidden" name="team_id"    value="<?= (int)$team['id'] ?>">
            <input type="hidden" name="to_user_id" value="<?= $viewId ?>"> 
            <button type="submit" class="dropdown-item">
              <?= htmlspecialchars($team['name']) ?>
            </button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['user_id']) && $theirOwnedTeams): ?>
  <div class="dropdown mb-3">
    <button class="btn btn-outline-success dropdown-toggle"
            type="button" id="requestDropdown" data-bs-toggle="dropdown">
      Request to Join
    </button>
    <ul class="dropdown-menu" aria-labelledby="requestDropdown">
      <?php foreach ($theirOwnedTeams as $team): ?>
        <li>
          <a class="dropdown-item"
             href="request_team.php?action=request
                   &team_id=<?= $team['id'] ?>">
            <?= htmlspecialchars($team['name']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

  <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $viewId): ?>
  <a href="start_chat.php?uid=<?= $viewId ?>"
       class="btn btn-primary mt-3">
       <i class="fas fa-paper-plane me-1"></i>Message
    </a>
  <?php endif; ?>

  <!-- about -->
  <?php if($user['about']): ?>
    <h4>About</h4>
    <p><?= nl2br(htmlspecialchars($user['about'])) ?></p>
  <?php endif; ?>

  <!-- full skill matrix -->
  <?php
    $currentCat=''; $currentSub='';
    foreach ($skills as $s):
      if ($s['category'] !== $currentCat){
         $currentCat = $s['category']; $currentSub='';
         echo "<h3 class='mt-4'>{$currentCat}</h3>";
      }
      if ($s['subcategory'] !== $currentSub){
         $currentSub = $s['subcategory'];
         echo "<h5 class='mt-2'>{$currentSub}</h5>";
      }
  ?>
      <span class="skill-pill" style="--pct:<?= $s['level'] ?>;">
        <span class="circle"></span><?= htmlspecialchars($s['name']) ?>
        <small class="ms-1 fw-semibold"><?= $s['level'] ?>%</small>
      </span>
  <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

