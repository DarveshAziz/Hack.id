<?php
/* ───────────────────────────  hackathons.php  ──────────────────────────── */

include 'config.php';
/* ---------- one-time schema + data patch ---------- */
$mysqli->query("
    ALTER TABLE hackathons
        ADD COLUMN IF NOT EXISTS link        VARCHAR(255),
        ADD COLUMN IF NOT EXISTS slug        VARCHAR(120),
        ADD COLUMN IF NOT EXISTS header_img  VARCHAR(255)
") or die($mysqli->error);

$needsPatch = $mysqli->query("SELECT COUNT(*) c FROM hackathons WHERE slug IS NULL OR slug=''")
                     ->fetch_assoc()['c'];

if ($needsPatch) {
    // map id => [url , header_image]
    $patch = [
        1 => ['https://googlecloudmultiagents.devpost.com/',     'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/388/218/datas/full_width.png'],
        2 => ['https://ai-in-action.devpost.com/',               'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/355/305/datas/full_width.png'],
        3 => ['https://perplexityhackathon.devpost.com/',        'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/352/578/datas/full_width.png'],
        4 => ['https://hpaistudio.devpost.com/',                 'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/319/066/datas/full_width.png'],
        5 => ['https://aws-breaking-barriers.devpost.com/',      NULL],
        6 => ['https://b25.devpost.com/',                        'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/355/595/datas/full_width.png'],
        7 => ['https://hackonomics25.devpost.com/',              'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/217/960/datas/full_width.png'],
        8 => ['https://amplicode.devpost.com/',                  'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/352/790/datas/full_width.png'],
        9 => ['https://codepi-ic-2025-1.devpost.com/',           'https://d112y698adiu2z.cloudfront.net/photos/production/challenge_photos/003/426/721/datas/full_width.png'],
    ];

    $stmt = $mysqli->prepare("
        UPDATE hackathons
           SET link = ?, slug = ?, header_img = ?
         WHERE id   = ?
    ");

    foreach ($patch as $id => [$url,$img]) {
        // slug = part right before `.devpost.com`
        preg_match('~https?://([^.]+)\.devpost\.com~i', $url, $m);
        $slug = $m[1] ?? '';

        $stmt->bind_param('sssi', $url, $slug, $img, $id);
        $stmt->execute();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hack.id - Find Your Team</title>
    <link rel="Website Icon" type="png" href="img/Logo1.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
    
    <link rel="stylesheet"
          href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"
          rel="stylesheet">

    <!-- Animate.css & Vendor CSS -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css"          rel="stylesheet">
</head>
<style>
    :root {
    --primary-purple: #6132d7;
    --secondary-purple: #A78BFA;
    --dark-purple: #6D28D9;
    --light-purple: #C4B5FD;
    --dark-bg: #0F0F23;
    --card-bg: #222222;
    --card-hover: #1a1a1a;
    --text-primary: #E5E7EB;
    --text-secondary: #9CA3AF;
    --text-muted: #6B7280;
}

* {
    box-sizing: border-box;
}

body {
    background: #000;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(167, 139, 250, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 40% 60%, rgba(109, 40, 217, 0.05) 0%, transparent 50%);
    pointer-events: none;
    z-index: -1;
}

/* Hero Section */
.page-header {
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(139,92,246,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.page-header h1 {
    background: #6132d7;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    text-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
}

.page-header p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
}

/* Cards */
.card {
    background: #222222;
    border: 1px solid rgba(49, 49, 49, 0.2);
    border-radius: 16px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    overflow: hidden;
    position: relative;
    backdrop-filter: blur(10px);
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-purple), var(--secondary-purple), var(--primary-purple));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.card:hover::before {
    transform: scaleX(1);
}

.card:hover {
    transform: translateY(-8px) scale(1.02);
    background: var(--card-hover);
    border-color: var(--primary-purple);
    box-shadow: 
        0 20px 40px rgba(139, 92, 246, 0.2),
        0 0 0 1px rgba(139, 92, 246, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.card-img-top {
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
    border-bottom: 1px solid rgba(139, 92, 246, 0.1);
}

.card:hover .card-img-top {
    transform: scale(1.05);
}

.card-body {
    padding: 1.5rem;
    position: relative;
}

.card-title {
    color: var(--text-primary);
    font-weight: 600;
    font-size: 1.1rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

.text-muted {
    color: var(--text-muted) !important;
}

.small {
    font-size: 0.875rem;
}

/* Badges */
.badge {
    background: linear-gradient(135deg, #582ec4 0%, #452499 100%) !important;
    color: white !important;
    font-weight: 500 !important;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    margin: 0.125rem;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px #;
}

/* Footer */
.card-footer {
    background: rgba(139, 92, 246, 0.05) !important;
    border-top: 1px solid rgba(139, 92, 246, 0.1) !important;
    padding: 1rem 1.5rem;
}

.text-primary {
    color: var(--primary-purple) !important;
    font-weight: 600;
    font-size: 1.1rem;
}

/* Icons */
.bi {
    color: var(--primary-purple);
}

/* Link styling */
a {
    text-decoration: none;
    color: inherit;
}

a:hover {
    color: inherit;
}

/* Container improvements */
.container {
    position: relative;
    z-index: 1;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .card:hover {
        transform: translateY(-4px) scale(1.01);
    }
}

/* Loading animation for cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.wow.fadeInUp {
    animation: fadeInUp 0.6s ease-out forwards;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Selection color */
::selection {
    background: var(--primary-purple);
    color: white;
}

::-moz-selection {
    background: var(--primary-purple);  
    color: white;
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--dark-bg);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(var(--primary-purple), var(--secondary-purple));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(var(--secondary-purple), var(--primary-purple));
}
</style>
<body class="pt-5">

<!-- ===== Sticky header ===== -->
<?php include 'includes/header.php'; ?>

<!-- ===== Page hero ===== -->
<div class="container-fluid page-header wow fadeIn mt-5" data-wow-delay="0.1s">
    <div class="container text-center py-5">
        <h1 class="display-4 text-white mb-3 animated slideInDown">Upcoming Hackathons</h1>
        <p class="lead text-white-50 animated slideInDown" style="animation-delay:.3s">
            Discover open challenges, then head to “Find Your Team” to assemble your squad.
        </p>
    </div>
</div>

<!-- ===== Hackathon grid ===== -->
<div class="container py-5">
    <div class="row g-4">
    <?php
      $res = $mysqli->query("SELECT * FROM hackathons ORDER BY id DESC");
      $idx = 0;
      while ($h = $res->fetch_assoc()):
        $themes = json_decode($h['themes'], true) ?: [];
        // derive slug from link
        if (preg_match('~https?://([^.]+)\.devpost\.com~i', $h['link'], $m)) {
          $slug = $m[1];
        } else {
          $slug = '';
        }
        // compute a 0.1s staggered delay
        $delay = sprintf('%.1f', $idx * 0.1) . 's';
    ?>
        <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="<?= $delay ?>">
            <a href="hackathon_view.php?slug=<?= urlencode($slug) ?>"
               class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($h['image_src'] ?? $h['header_img']) ?>"
                         class="card-img-top"
                         alt="<?= htmlspecialchars($h['title']) ?>">

                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-2">
                            <?= htmlspecialchars($h['title']) ?>
                        </h5>

                        <p class="small text-muted mb-1">
                            <?= htmlspecialchars($h['status']) ?> •
                            <?= htmlspecialchars($h['participants']) ?>
                        </p>

                        <p class="small text-muted mb-1">
                            Hosted&nbsp;by <?= htmlspecialchars($h['host']) ?>
                        </p>

                        <p class="small mb-2">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= htmlspecialchars($h['submission_period']) ?>
                        </p>

                        <?php foreach ($themes as $t): ?>
                            <span class="badge bg-secondary bg-opacity-25 text-white fw-normal me-1 mb-1">
                                <?= htmlspecialchars($t) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-footer bg-transparent border-0">
                        <span class="fw-bold text-primary">
                            <?= htmlspecialchars($h['prize']) ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    <?php
        $idx++;
      endwhile;
    ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- ===== JS ===== -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- WOW.js + init -->
<script src="lib/wow/wow.min.js"></script>
<script>
  new WOW().init();
</script>

<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
