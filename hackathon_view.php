<?php
/* --------------------------------------------------------------------------
   hackathon_view.php   –   drop-in replacement (with “Schedule” tab)
----------------------------------------------------------------------------*/
include 'config.php';

/* ────────── current hackathon + tab ────────── */
$slug = preg_replace('~[^a-z0-9\-]~i', '', $_GET['slug'] ?? '');
$tab  = $_GET['tab']  ?? 'overview';                    // overview | rules | schedule | myproject

$hack = $mysqli->query("SELECT * FROM hackathons WHERE slug = '$slug'")
               ->fetch_assoc();

if (!$hack) {
    http_response_code(404);
    exit('Hackathon not found');
}

/* safe-decode JSON columns that were saved as strings */
$themes = json_decode($hack['themes']      ?? '[]', true) ?: [];
$prizes = json_decode($hack['prizes_json'] ?? '[]', true) ?: [];

/* -----------------------------------------------
 *  HELPERS
 * ---------------------------------------------*/
/* overview.json : description / requirements / prizes / judging */
function overview_row(string $slug): array
{
    static $rows = null;
    if ($rows === null) {
        $json = file_get_contents(__DIR__.'/data/overview.json');
        $rows = json_decode($json, true) ?: [];
    }
    foreach ($rows as $r) {
        if (($r['slug'] ?? '') === $slug) return $r;
    }
    return [];
}

/* rules.json : “main” field */
function rules_row(string $slug): array
{
    static $rows = null;
    $file = __DIR__.'/data/rules.json';
    if ($rows === null && file_exists($file)) {
        $rows = json_decode(file_get_contents($file), true) ?: [];
    }
    foreach ($rows ?? [] as $r) {
        if (($r['slug'] ?? '') === $slug) return $r;
    }
    return [];
}

/* schedules.json : “main” field */
function schedule_row(string $slug): array
{
    static $rows = null;
    $file = __DIR__.'/data/schedules.json';
    if ($rows === null && file_exists($file)) {
        $rows = json_decode(file_get_contents($file), true) ?: [];
    }
    foreach ($rows as $r) {
        if (($r['slug'] ?? '') === $slug) {
            // now returns ['slug'=>'…','schedule'=>[…]]
            return $r;
        }
    }
    return [];
}

$userId = $_SESSION['user_id'] ?? 0;
$hid    = (int)$hack['id'];

// check registration
$stmt = $mysqli->prepare(
  "SELECT 1 FROM registrations WHERE user_id=? AND hackathon_id=?"
);
$stmt->bind_param('ii',$userId,$hid);
$stmt->execute();
$isReg = (bool)$stmt->get_result()->fetch_row();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Hack.id - Find Your Team. Hack the Future </title>
   <link rel="Website Icon" type="png" href="img/Logo1.png" />
  <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
    />
<meta charset="utf-8">
<title><?= htmlspecialchars($hack['title']) ?> • Hack.id</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"        rel="stylesheet">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ─── quick “Devpost-ish” helpers ───────────────────────────────────────── */
.hero-banner {
    background:#222 url(<?= json_encode($hack['header_img']) ?>) center/cover no-repeat;
    min-height:240px;               /* keeps banner visible even without text */
}
.hero-overlay        {background:rgba(0,0,0,.45);}
.cp-tag              {font-size:.85rem;font-weight:600;padding:.25rem .7rem;border-radius:20px}
.status-label.open   {background:#198754;color:#fff;}
.theme-label         {background:#eff6ff;color:#2563eb;padding:.25rem .6rem;border-radius:4px;font-size:.75rem;}
.host-label          {background:#e0e7ff;color:#4338ca;padding:.25rem .6rem;border-radius:12px;font-size:.75rem;}
.challenge-tabs a    {display:inline-block;padding:.75rem 1.25rem;font-weight:600;
                      color:#555;border-bottom:3px solid transparent;}
.challenge-tabs a:hover{color:#000}
.challenge-tabs a.active{color:#000;border-color:#7f39e9}
@media (min-width:992px){
  .sticky-reg{position:sticky;top:0;z-index:1030;}
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- ────────── HERO (banner image) ────────── -->
<header id="challenge-header" class="mb-4" style="margin-top:66px;">
  <div class="hero-banner d-flex align-items-center justify-content-center">
    <div class="hero-overlay w-100 h-100"></div>
  </div>

  <!-- sticky sub-nav -->
  <div class="challenge-tabs bg-light border-top">
    <div class="container text-center">
      <a class="<?= $tab==='overview'  ? 'active':'' ?>"
         href="?slug=<?= urlencode($slug) ?>&tab=overview">Overview</a>
      <a class="<?= $tab==='rules'     ? 'active':'' ?>"
         href="?slug=<?= urlencode($slug) ?>&tab=rules">Rules</a>
      <a class="<?= $tab==='schedule'  ? 'active':'' ?>"
         href="?slug=<?= urlencode($slug) ?>&tab=schedule">Schedule</a>
      <a class="<?= $tab==='myproject' ? 'active':'' ?>"
         href="?slug=<?= urlencode($slug) ?>&tab=myproject">My&nbsp;project</a>
    </div>
  </div>
</header>

<!-- ────────── PAGE BODY ────────── -->
<div class="container pb-6">
  <div class="row">
    <!-- left column (8/12) -->
    <div class="col-lg-8">
<?php
/* -----------------------------------------------------------
 *  TAB CONTENT
 * -----------------------------------------------------------*/
switch ($tab) {

  /* ---------- overview ---------- */
  case 'overview':
      $ov = overview_row($slug);
      $found = false;
      foreach (['description','requirements','prizes','judging'] as $k) {
          if (!empty($ov[$k])) { echo $ov[$k]; $found = true; }
      }
      if (!$found) echo '<p class="text-muted">No information available for this section.</p>';
      break;

  /* ---------- rules ---------- */
  case 'rules':
      $ru = rules_row($slug);
      echo $ru['main'] ?? '<p class="text-muted">No information available for this section.</p>';
      break;

  /* ---------- schedule ---------- */
  case 'schedule':
    $sc = schedule_row($slug);
    $items = $sc['schedule'] ?? [];
    if (count($items) === 0) {
        echo '<p class="text-muted">No schedule available.</p>';
        break;
    }
    ?>
    <section class="row text-content content-section" id="main">
      <div class="col-12">
        <div class="d-flex align-items-center mb-4">
          <h2 class="mb-0">Schedule</h2>
          <div class="ms-auto small">
            Schedule timezone
            <a data-settings-url="true"
               href=""
               target="_blank">
              <span id="tz-abbr">GMT+7</span>
              <i class="fas fa-pen fa-xs" aria-hidden="true"></i>
            </a>
          </div>
        </div>
  
        <div class="table-responsive ">
          <table class="table table-borderless mb-0 bg-white p-3"">
            <thead class="bg-light">
              <tr>
                <th>Period</th>
                <th>Begins</th>
                <th>Ends</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $row): 
                // mark the first row “active” — change as you like
                $active = $i === 0 ? 'active' : '';
            ?>
              <tr class="<?= $active ?>">
                <td><?= htmlspecialchars($row['period']) ?></td>
                <td data-iso="<?= htmlspecialchars($row['begins']) ?>"
                    class="<?= $active ?>"></td>
                <td data-iso="<?= htmlspecialchars($row['ends']) ?>"></td>
              </tr>
            <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php
    break;


  /* ---------- my project ---------- */
  case 'myproject':
  default:
      echo '<p class="lead">Project submission coming soon…</p>';
      break;
}
?>
    </div>

    <!-- right sidebar (4/12) -->
    <aside class="col-lg-4">
      <div class="sticky-reg">
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <!-- deadline tag -->
            <?php if ($hack['status']): ?>
              <p class="cp-tag status-label rounded open mb-3"><?= htmlspecialchars($hack['status']) ?></p>
            <?php endif; ?>

            <!-- schedule line -->
            <p class="small text-muted mb-2">
              <?= htmlspecialchars($hack['submission_period']) ?>
              <br><a href="<?= htmlspecialchars($hack['link']) ?>details/dates" target="_blank">View schedule</a>
            </p>

            <!-- cash + participants -->
            <table class="w-100 mb-3">
              <tr>
                <td><i class="fas fa-money-bill-wave me-2 text-primary"></i>
                    <strong><?= htmlspecialchars($hack['prize']) ?></strong></td>
                <td><i class="fas fa-users me-2 text-primary"></i>
                    <strong><?= htmlspecialchars($hack['participants']) ?></strong></td>
              </tr>
            </table>

            <!-- host -->
            <?php if ($hack['host']): ?>
            <p class="mb-2">
              <i class="fas fa-flag me-2 text-primary"></i>
              <span class="label host-label"><?= htmlspecialchars($hack['host']) ?></span>
            </p>
            <?php endif; ?>

            <!-- themes -->
            <?php if ($themes): ?>
              <p><i class="fas fa-tag me-2 text-primary"></i>
              <?php foreach ($themes as $t): ?>
                <span class="theme-label me-1 mb-1 d-inline-block"><?= htmlspecialchars($t) ?></span>
              <?php endforeach; ?>
              </p>
            <?php endif; ?>
          </div>
          <div class="card-footer bg-transparent">
			  <?php if (!$isReg): ?>
				<!-- register action -->
				<form method="post" action="register_to_hack.php" class="d-inline w-100">
				  <input type="hidden" name="hackathon_id" value="<?= $hid ?>">
				  <button type="submit" class="btn btn-primary w-100">
					Register
				  </button>
				</form>
			  <?php else: ?>
				<!-- already registered -->
				<a href="manage_teams.php" class="btn btn-outline-primary w-100 mb-2">
				  Find Teams
				</a>
				<a href="manage_teams.php?create=<?= $hid ?>" class="btn btn-primary w-100">
				  Create Team
				</a>
			  <?php endif; ?>
			</div>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment-timezone@0.5.33/builds/moment-timezone-with-data.min.js"></script>
<script>
// pick your zone
const TZ = 'Asia/Jakarta';

// helper to get “+7” (or “-4”, etc) from a moment:
function getGMTOffsetH(m) {
  // utcOffset() is in minutes, so divide by 60
  const hours = m.utcOffset() / 60;
  // keep the sign (“+” or “–”) in there
  return `${hours >= 0 ? '+' : ''}${hours}`;
}

// inject “GMT+7” into the “pen” link
const now = moment.tz(TZ);
document.getElementById('tz-abbr').textContent =
  `GMT${getGMTOffsetH(now)}`;

// convert each ISO cell
document.querySelectorAll('[data-iso]').forEach(el => {
  const iso = el.getAttribute('data-iso');
  if (!iso) return;

  const m = moment(iso).tz(TZ);
  const pretty = m.format('MMMM D [at] h:mma');
  const gmtH   = getGMTOffsetH(m);

  el.textContent = `${pretty} GMT${gmtH}`;
});
</script>
</body>
</html>
