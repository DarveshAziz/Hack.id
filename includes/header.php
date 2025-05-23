<?php
// 1️⃣ Start the session (if you haven’t already done so earlier)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2️⃣ If they’re logged in, grab their info; otherwise skip the DB hit
if (isset($_SESSION['user_id'])) {
    // cast to int for safety
    $uid = (int) $_SESSION['user_id'];

    // prepare + execute a safe, injection-proof query
    $stmt = $mysqli->prepare("
        SELECT
          username,
          COALESCE(avatar, 'img/default-avatar.png') AS avatar
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
echo '<link 
          rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        >';

?>

<nav class="navbar navbar-expand-lg navbar-light justify-content-between px-4 px-lg-5 py-3 py-lg-0 sticky-top shadow-sm ms-auto">
	
	
	<a href="index.php" class="navbar-brand d-flex align-items-center p-0">
		<!-- image logo -->
		<img src="img/logos.png" alt="Acuas logo"
			 class="me-2" style="height:48px;">

		<!-- text logo -->
		<span class="fs-3 fw-bold" style="color:#7f39e9;">
			Hack.id
		</span>
	</a>
	
	<button class="navbar-toggler order-2 order-lg-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
		<span class="fa fa-bars"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarCollapse">
		<div class="navbar-nav ms-auto py-0">
			<a href="index.php" class="nav-item nav-link active">Home</a>
			<a href="about.php" class="nav-item nav-link">About</a>
			<a href="hackathons.php" class="nav-item nav-link">Hackathons</a>
			<a href="manage_teams.php" class="nav-item nav-link">Teams</a>
			<div class="nav-item dropdown">
				<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
				<div class="dropdown-menu m-0">
					<a href="feature.php" class="dropdown-item">Our Feature</a>
					<a href="product.php" class="dropdown-item">Our Product</a>
					<a href="team.php" class="dropdown-item">Our Team</a>
					<a href="testimonial.php" class="dropdown-item">Testimonial</a>
					<a href="404.php" class="dropdown-item">404 Page</a>
				</div>
			</div>
			<a href="contact.php" class="nav-item nav-link">Contact</a>
		</div>
	</div>
	<?php if (isset($_SESSION['user_id'])): ?>
		<?php
		  $uid     = (int)$_SESSION['user_id'];
		  $invites = $mysqli->query("
			SELECT COUNT(*) 
			  FROM team_invitations 
			 WHERE to_user_id=$uid 
			   AND status='pending'
		  ")->fetch_row()[0];
		  $reqs    = $mysqli->query("
			SELECT COUNT(*) 
			  FROM team_join_requests r
			  JOIN teams t ON r.team_id = t.id
			 WHERE t.creator_id=$uid 
			   AND r.status='pending'
		  ")->fetch_row()[0];
		  $badge   = $invites + $reqs;
		?>
		<div class="nav-item dropdown">
		  <a class="nav-link position-relative" 
			 href="#" 
			 id="inboxDropdown" 
			 data-bs-toggle="dropdown" 
			 aria-expanded="false">
			<i class="fas fa-envelope me-1"></i>Inbox
			<?php if($badge): ?>
			  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
				<?= $badge ?>
				<span class="visually-hidden">unread notifications</span>
			  </span>
			<?php endif; ?>
		  </a>
		  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="inboxDropdown">
			<?php if($invites): ?>
			  <li><a class="dropdown-item" href="/Hack.id/inbox.php">
				<?= $invites ?> team <?= $invites>1?'invites':'invite' ?>
			  </a></li>
			<?php endif; ?>
			<?php if($reqs): ?>
			  <li><a class="dropdown-item" href="/Hack.id/inbox.php">
				<?= $reqs ?> join <?= $reqs>1?'requests':'request' ?>
			  </a></li>
			<?php endif; ?>
			<?php if(!$badge): ?>
			  <li><span class="dropdown-item text-muted">No new notifications</span></li>
			<?php endif; ?>
		  </ul>
		</div>
	<?php endif; ?>
	<?php if (isset($_SESSION['user_id'])): ?>
		<!-- avatar + username -->
		<a href="profile.php"
		   class="d-inline-flex align-items-center justify-content-center rounded-circle overflow-hidden ms-3"
		   style="width:40px;height:40px;background:#f0f3ff;">
			<img src="<?= htmlspecialchars($userRow['avatar'] ?? 'img/default-avatar.png') ?>"
				 class="img-fluid w-100 h-100 object-fit-cover" alt="Profile">
		</a>

		<span class="d-lg-inline-block me-3 fw-medium">
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

	<!--
	<button class="navbar-toggler order-2 order-lg-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
		<span class="fa fa-bars"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarCollapse">
		<div class="navbar-nav ms-auto py-0">
			<a href="index.php" class="nav-item nav-link active">Home</a>
			<a href="about.php" class="nav-item nav-link">About</a>
			<a href="service.php" class="nav-item nav-link">Service</a>
			<a href="hackathons.php" class="nav-item nav-link">Hackathons</a>
			<a href="manage_teams.php" class="nav-item nav-link">Teams</a>
		</div>
	</div>
	-->
	
</nav>