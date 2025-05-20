<nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0 sticky-top shadow-sm">
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