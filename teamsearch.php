<?php
include 'config.php';

/* ───────────────── helper: fetch a user’s top-3 skills ───────────────── */
function top_three(mysqli $mysqli, int $uid) : array {
    return $mysqli->query("
        SELECT s.name, us.level
        FROM   user_skill us
        JOIN   skills s ON s.id = us.skill_id
        WHERE  us.user_id = $uid
        ORDER  BY us.level DESC
        LIMIT  3
    ")->fetch_all(MYSQLI_ASSOC);
}

/* ──────────────────────── read filters from <form> ───────────────────── */
$q   = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? [];                     // array of role filters

$where = [];
if ($q) {
    $safe = $mysqli->real_escape_string($q);
    $where[] = "(u.username LIKE '%$safe%' OR up.display_name LIKE '%$safe%')";
}
if ($cat) {
    $safeCats = array_map([$mysqli,'real_escape_string'], $cat);
    $catList  = "'" . implode("','", $safeCats) . "'";
    $where[]  = "EXISTS (
                   SELECT 1
                   FROM   user_skill us
                   JOIN   skills s ON s.id = us.skill_id
                   WHERE  us.user_id = u.id
                     AND  s.category IN ($catList)
                 )";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ──────────────────────── pull users + profile data ──────────────────── */
$users = $mysqli->query("
    SELECT u.id,
           COALESCE(u.avatar,'img/default-avatar.png')               AS avatar,
           u.username,
           IFNULL(up.display_name, u.username)                       AS display_name,
           up.headline,
           up.location
    FROM   users u
    LEFT   JOIN user_profile up ON up.user_id = u.id
    $whereSQL
    ORDER  BY display_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Find teammates • Hack.id</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"        rel="stylesheet">
<style>
.skill-pill{
  display:inline-flex;align-items:center;
  border:1px solid var(--bs-primary);border-radius:20px;
  padding:.25rem .6rem .25rem .4rem;font-size:.75rem;margin:.15rem;
}
.skill-pill .circle{
  width:22px;height:22px;border-radius:50%;background:#eee;
  margin-right:.4rem;position:relative;flex-shrink:0;
}
.skill-pill .circle::after{
  content:'';position:absolute;inset:0;border-radius:50%;
  background:conic-gradient(var(--bs-primary) calc(var(--pct)*1%), #eee 0);
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- Page header -->
<div class="container-fluid page-header wow fadeIn" data-wow-delay="0.1s">
  <div class="container text-center py-5">
    <h1 class="display-4 text-white mb-3">Find Your Team</h1>
    <p class="lead text-white-50">Browse hackers &amp; filter by skill domain</p>
  </div>
</div>

<!-- Search / filters -->
<div class="container py-5">
  <form class="row g-3 mb-4" method="get">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
        <input class="form-control" placeholder="Search by name"
               name="q" value="<?= htmlspecialchars($q) ?>">
      </div>
    </div>

    <div class="col-md-6">
      <?php
        $roles = ['Frontend','Design','Backend',
                  'Mobile Dev','AI & ML','Cloud & DevOps'];
        foreach ($roles as $r):
      ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="cat[]"
                 value="<?= $r ?>" id="cat<?= md5($r) ?>"
                 <?= in_array($r,$cat) ? 'checked' : '' ?>>
          <label class="form-check-label small"
                 for="cat<?= md5($r) ?>"><?= $r ?></label>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="col-md-2 d-grid">
      <button class="btn btn-primary">Search</button>
    </div>
  </form>

  <p class="mb-4 fw-semibold"><?= count($users) ?> hackers found</p>

  <div class="row g-4">
<?php foreach ($users as $u):
      $tops = top_three($mysqli, $u['id']); ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <!-- ─── user header ─────────────────────────── -->
          <div class="d-flex align-items-center mb-3">
            <img src="<?= htmlspecialchars($u['avatar']) ?>"
                 class="rounded-circle me-3"
                 style="width:56px;height:56px;object-fit:cover">
            <div>
              <h5 class="mb-0"><?= htmlspecialchars($u['display_name']) ?></h5>
              <?php if($u['headline']): ?>
                  <small class="text-muted"><?= htmlspecialchars($u['headline']) ?></small>
              <?php endif; ?>
              <?php if($u['location']): ?>
                  <div class="small text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?= htmlspecialchars($u['location']) ?>
                  </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- ─── top-3 skills ────────────────────────── -->
          <?php foreach ($tops as $t): ?>
            <span class="skill-pill" style="--pct:<?= $t['level'] ?>;">
              <span class="circle"></span> <?= htmlspecialchars($t['name']) ?>
            </span>
          <?php endforeach; ?>
        </div>

        <div class="card-footer bg-transparent border-0">
          <a href="profile_public.php?id=<?= $u['id'] ?>"
             class="stretched-link"></a>
        </div>
      </div>
    </div>
<?php endforeach; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
