<?php
include 'config.php';

/* ───────────────── helper: fetch a user's top-3 skills ───────────────── */
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
<title>Find teammates - Hack.id</title>
<!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
<link rel="Website Icon" type="png" href="img/Logo1.png" />
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

/* ===== ENHANCED DESIGN CSS ===== */

/* Global Variables & Reset */
:root {
  --primary-color: #452499;
  --primary-hover: #351c76;
  --primary-light: rgba(137, 56, 237, 0.1);
  --background-dark:rgb(0, 0, 0);
  --card-bg: #2a2a2a;
  --text-light: #ffffff;
  --text-muted: #b0b0b0;
  --border-color: rgba(137, 56, 237, 0.2);
  --shadow-color: rgba(0, 0, 0, 0.3);
  --gradient-bg: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
}

/* Body & Background */
body {
  background: #000;
  color: var(--text-light);
  font-family: 'Poppins', sans-serif;
  line-height: 1.6;
  min-height: 100vh;
}

/* Page Header Styling */
.page-header {
  background: linear-gradient(135deg, #452499 0%, #351c76 100%);
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  33% { transform: translateY(-10px) rotate(1deg); }
  66% { transform: translateY(5px) rotate(-1deg); }
}

.page-header .container {
  position: relative;
  z-index: 2;
}

.page-header h1 {
  font-weight: 700;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
  margin-bottom: 1rem;
}

.page-header .lead {
  font-size: 1.1rem;
  opacity: 0.9;
}

/* Container Styling */
.container {
  position: relative;
}

/* Search Form Styling */
.container > form {
  background: var(--card-bg);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 30px var(--shadow-color);
  border: 1px solid var(--border-color);
  backdrop-filter: blur(10px);
  margin-bottom: 2rem;
}

/* Input Group Styling */
.input-group {
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}


.form-control {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  color: var(--text-light);
  padding: 0.75rem 1rem;
}

.form-control:focus {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(137, 56, 237, 0.2);
  color: var(--text-light);
}

.form-control::placeholder {
  color: var(--text-muted);
}

/* Enhanced Checkbox Styling */
.form-check {
  position: relative;
  margin-bottom: 0.5rem;
  margin-right: 0.8rem;
}

.form-check-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.form-check-label {
  position: relative;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.85rem;
  color: var(--text-light);
  padding: 0.5rem 1rem 0.5rem 2.8rem;
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid var(--border-color);
  border-radius: 25px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(10px);
  user-select: none;
  min-width: 110px;
  justify-content: flex-start;
  text-align: left;
}

.form-check-label::before {
  content: '';
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: transparent;
  border: 2px solid var(--border-color);
  transition: all 0.3s ease;
}

.form-check-label::after {
  content: '✓';
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%) scale(0);
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--primary-color);
  color: white;
  font-size: 11px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  opacity: 0;
}

.form-check-input:checked + .form-check-label {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-color: var(--primary-color);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(137, 56, 237, 0.4);
}

.form-check-input:checked + .form-check-label::before {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-50%) scale(1.1);
}

.form-check-input:checked + .form-check-label::after {
  transform: translateY(-50%) scale(1);
  opacity: 1;
}

.form-check-label:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(137, 56, 237, 0.2);
  border-color: var(--primary-color);
  background: rgba(137, 56, 237, 0.1);
}

.form-check-input:checked + .form-check-label:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(137, 56, 237, 0.5);
}

/* Checkbox Focus State */
.form-check-input:focus + .form-check-label {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Checkbox Animation on Load */
@keyframes checkboxSlide {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

.form-check {
  animation: checkboxSlide 0.5s ease forwards;
}

.form-check:nth-child(1) { animation-delay: 0.1s; }
.form-check:nth-child(2) { animation-delay: 0.2s; }
.form-check:nth-child(3) { animation-delay: 0.3s; }
.form-check:nth-child(4) { animation-delay: 0.4s; }
.form-check:nth-child(5) { animation-delay: 0.5s; }
.form-check:nth-child(6) { animation-delay: 0.6s; }

/* Results Count */
.fw-semibold {
  color: var(--primary-color);
  font-size: 1.1rem;
  margin-bottom: 2rem;
}

/* Card Styling */
.card {
  background: var(--card-bg);
  border-radius: 20px;
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px #351c76;
  border-color: var(--primary-color);
}

.card:hover::before {
  opacity: 1;
}

.card-body {
  padding: 1.5rem;
  position: relative;
}

/* User Avatar */
.card-body img {
  border: 3px solid var(--primary-color);
  box-shadow: 0 4px 15px rgba(137, 56, 237, 0.3);
  transition: all 0.3s ease;
}

.card:hover .card-body img {
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(137, 56, 237, 0.4);
}

/* User Info */
.card-body h5 {
  color: var(--text-light);
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.card-body .text-muted {
  color: var(--text-muted) !important;
  font-size: 0.9rem;
}

/* Skill Pills Enhanced */
.skill-pill {
  display: inline-flex;
  align-items: center;
  background: #582ec4;
  border: 1px solid var(--primary-color);
  border-radius: 25px;
  padding: 0.4rem 0.8rem 0.4rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  margin: 0.2rem 0.3rem 0.2rem 0;
  transition: all 0.3s ease;
  backdrop-filter: blur(5px);
  color: var(--text-light);
}

.skill-pill:hover {
  background: rgba(0, 0, 0, 0.2);
  transform: scale(1.05);
  box-shadow: 0 4px 12px #452499;
}

.skill-pill .circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  margin-right: 0.5rem;
  position: relative;
  flex-shrink: 0;
  overflow: hidden;
}

.skill-pill .circle::after {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 50%;
  background: conic-gradient(
    var(--primary-color) calc(var(--pct) * 1%), 
    rgba(255, 255, 255, 0.2) 0
  );
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Card Footer */
.card-footer {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  position: relative;
}

.stretched-link::after {
  border-radius: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .container > form {
    padding: 1.5rem;
    margin: 1rem;
  }
  
  .page-header h1 {
    font-size: 2rem;
  }
  
  .form-check-inline {
    display: block;
    margin-bottom: 0.5rem;
  }
  
  .form-check-label {
    min-width: 100px;
    font-size: 0.8rem;
    padding: 0.5rem 1rem;
  }
  
  .card {
    margin-bottom: 1rem;
  }
}

/* Loading Animation for Skill Pills */
@keyframes skillLoad {
  0% { opacity: 0; transform: translateY(10px); }
  100% { opacity: 1; transform: translateY(0); }
}

.skill-pill {
  animation: skillLoad 0.5s ease forwards;
}

.skill-pill:nth-child(1) { animation-delay: 0.1s; }
.skill-pill:nth-child(2) { animation-delay: 0.2s; }
.skill-pill:nth-child(3) { animation-delay: 0.3s; }

/* Background Pattern */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(137, 56, 237, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(137, 56, 237, 0.1) 0%, transparent 50%);
  pointer-events: none;
  z-index: -1;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: var(--background-dark);
}

::-webkit-scrollbar-thumb {
  background: var(--primary-color);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--primary-hover);
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- Page header -->
<div class="container-fluid page-header wow fadeIn mt-5" data-wow-delay="0.1s">
  <div class="container text-center py-5">
    <h1 class="display-4 text-white mb-3">Find Your Team</h1>
    <p class="lead text-white-50">Browse hackers &amp; filter by skill domain</p>
  </div>
</div>

<!-- Search / filters -->
<div class="container py-5">
  <form class="mb-4" method="get">
    <!-- Centered Search Box -->
    <div class="row justify-content-center mb-4">
      <div class="col-md-6 col-lg-5">
        <div class="input-group">
          <input class="form-control" placeholder="Search by name"
                 name="q" value="<?= htmlspecialchars($q) ?>">
        </div>
      </div>
    </div>

    <!-- Centered Checkboxes -->
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <?php
            $roles = ['Frontend','Design','Backend',
                      'Mobile Dev','AI & ML','Cloud & DevOps'];
            foreach ($roles as $r):
          ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="cat[]"
                     value="<?= $r ?>" id="cat<?= md5($r) ?>"
                     <?= in_array($r,$cat) ? 'checked' : '' ?>>
              <label class="form-check-label small"
                     for="cat<?= md5($r) ?>"><?= $r ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </form>

  <p class="mb-4 fw-semibold text-center"><?= count($users) ?> hackers found</p>

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