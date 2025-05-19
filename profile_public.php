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

<nav class="navbar sticky-top shadow-sm navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0">
	<a href="index.php" class="navbar-brand d-flex align-items-center p-0">
		<!-- image logo -->
		<img src="img/logos.png" alt="Acuas logo"
			 class="me-2" style="height:48px;">

		<!-- text logo -->
		<span class="fs-3 fw-bold" style="color:#7f39e9;">
			Hack.id
		</span>
	</a>
	<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
		<span class="fa fa-bars"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarCollapse">
		<div class="navbar-nav ms-auto py-0">
			<a href="index.html" class="nav-item nav-link active">Home</a>
			<a href="about.html" class="nav-item nav-link">About</a>
			<a href="service.html" class="nav-item nav-link">Service</a>
			<a href="blog.html" class="nav-item nav-link">Blog</a>
			<div class="nav-item dropdown">
				<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
				<div class="dropdown-menu m-0">
					<a href="feature.html" class="dropdown-item">Our Feature</a>
					<a href="product.html" class="dropdown-item">Our Product</a>
					<a href="team.html" class="dropdown-item">Our Team</a>
					<a href="testimonial.html" class="dropdown-item">Testimonial</a>
					<a href="404.html" class="dropdown-item">404 Page</a>
				</div>
			</div>
			<a href="contact.html" class="nav-item nav-link">Contact</a>
		</div>
		<button class="btn btn-primary btn-md-square d-flex flex-shrink-0 mb-3 mb-lg-0 rounded-circle me-3" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i></button>
		<?php if (isset($_SESSION['user_id'])): ?>
			<!-- avatar + username -->
			<a href="profile.php"
			   class="d-inline-flex align-items-center justify-content-center rounded-circle overflow-hidden ms-3"
			   style="width:40px;height:40px;background:#f0f3ff;">
				<img src="<?= htmlspecialchars($_SESSION['avatar'] ?? 'img/default-avatar.png') ?>"
					 class="img-fluid w-100 h-100 object-fit-cover" alt="Profile">
			</a>

			<span class="d-none d-lg-inline-block ms-2 me-3 fw-medium">
				<?= htmlspecialchars($_SESSION['username']) ?>
			</span>

			<!-- logout pill -->
			<a href="logout.php"
			   class="btn btn-secondary rounded-pill d-inline-flex flex-shrink-0 py-2 px-4">
			   Logout
			</a>
		<?php else: ?>
			<!-- guest sees the login pill -->
			<a href="login.php"
			   class="btn btn-primary rounded-pill d-inline-flex flex-shrink-0 py-2 px-4 ms-3">
			   Login
			</a>
		<?php endif; ?>
	</div>
</nav>

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

