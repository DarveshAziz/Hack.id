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
