<?php
/* --------------------------------------------------------------------------
   hackathon_view.php   –   drop-in replacement (with "Schedule" tab)
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

/* rules.json : "main" field */
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

/* schedules.json : "main" field */
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
   <title>Hack.id</title>
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
/* ===============================================
   ENHANCED HACKATHON STYLES - INTEGRATED
   =============================================== */

/* === HERO BANNER IMPROVEMENTS === */
.hero-banner {
    background: #222 url(<?= json_encode($hack['header_img']) ?>) center/cover no-repeat;
    min-height: 320px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-overlay {
    background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(123,67,151,0.4) 100%);
    backdrop-filter: blur(2px);
}

/* === MODERN NAVIGATION TABS === */
.challenge-tabs {
    background: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(10px);
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 66px;
    z-index: 1020;
    transition: all 0.3s ease;
}

.challenge-tabs::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
}

.challenge-tabs .container {
    padding: 0;
}

.challenge-tabs a {
    display: inline-block;
    padding: 1rem 2rem;
    font-weight: 600;
    font-size: 0.95rem;
    color: #64748b;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    margin: 0 0.25rem;
    border-radius: 8px 8px 0 0;
}

.challenge-tabs a::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 8px 8px 0 0;
    z-index: -1;
}

.challenge-tabs a:hover {
    color: #fff;
    transform: translateY(-2px);
    background: #452499;
}

.challenge-tabs a:hover::before {
    opacity: 0.1;
}

.challenge-tabs a.active {
    color: #7c3aed;
    background: rgba(124, 58, 237, 0.08);
    border-color: #7c3aed;
    transform: translateY(-1px);
}

.challenge-tabs a.active::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: linear-gradient(90deg, #7c3aed, #a855f7);
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
}

/* === SIDEBAR REGISTRATION CARD === */
.registration-sidebar {
    position: relative;
}

.registration-card {
    background: #202020;
    border-radius: 16px;
    box-shadow: 
        0 4px 6px -1px #582ec4,
        0 2px 4px -1px #582ec4,
        0 0 0 1px #582ec4;
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    animation: slideInUp 0.6s ease-out;
}

.registration-card:hover {
    transform: translateY(-4px);
}

.registration-card .card-body {
    padding: 2rem;
    background: transparent;
}

.registration-card .card-footer {
    background: rgba(248, 250, 252, 0.8);
    padding: 1.5rem 2rem;
}

/* === STATUS AND LABELS === */
.cp-tag.status-label.open {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: inline-block;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.theme-label {
    background:#6132d7;
    color: #fff;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid rgba(59, 130, 246, 0.2);
    transition: all 0.2s ease;
}

.theme-label:hover {
    background: linear-gradient(135deg, #351c76 0%, #452499 100%);
    background: #582ec4;
    transform: translateY(-1px);
}

.host-label {;
    color: #fff;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(67, 56, 202, 0.2);
}

/* === STATS TABLE === */
.stats-table {
    width: 100%;
    margin-bottom: 1.5rem;
    background: rgba(46, 46, 46, 0.5);
    border-radius: 12px;
    padding: 1rem;
}

.stats-table td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

.stats-table i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.stats-table strong {
    font-weight: 700;
    color: #fff;
}

/* === BUTTONS === */
.btn-register {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    border: none;
    color: white;
    font-weight: 700;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-size: 1rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    position: relative;
    overflow: hidden;
    width: 100%;
}

.btn-register::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-register:hover::before {
    left: 100%;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
    background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
    color: white;
}

.btn-team {
    background: #582ec4;
    border: 2px solid #6132d7;
    color: #fff;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    width: 100%;
    margin-bottom: 0.5rem;
}

.btn-team:hover {
    background: #452499;
    border-color: #452499;
    color: #fff;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-team-create {
    background: #452499;
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    box-shadow: 0 2px 8px #29155a;
    width: 100%;
}

.btn-team-create:hover {
    background: #351c76;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px #351c76;
    text-decoration: none;
}

/* === SCHEDULE TABLE === */
.table-responsive {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.table {
    margin-bottom: 0;
    background: white;
}

.table thead th {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border: none;
    color: #475569;
    font-weight: 700;
    padding: 1rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 1rem;
    border-color: #f1f5f9;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: rgba(124, 58, 237, 0.04);
}

.table tbody tr.active {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, rgba(168, 85, 247, 0.04) 100%);
    border-left: 4px solid #7c3aed;
}

/* === ICONS === */
.text-primary {
    color: #7c3aed !important;
}

/* === ANIMATIONS === */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* === RESPONSIVE IMPROVEMENTS === */
@media (max-width: 991.98px) {
    .challenge-tabs {
        position: relative;
        top: 0;
    }
    
    .challenge-tabs a {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        margin: 0 0.1rem;
    }
    
    .registration-card {
        margin-bottom: 1rem;
    }
    
    .registration-card .card-body,
    .registration-card .card-footer {
        padding: 1.5rem;
    }
}

@media (max-width: 767.98px) {
    .challenge-tabs .container {
        padding: 0 1rem;
    }
    
    .challenge-tabs a {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        margin: 0;
    }
    
    .hero-banner {
        min-height: 200px;
    }
    
    .registration-card .card-body,
    .registration-card .card-footer {
        padding: 1rem;
    }
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
    <div class="container text-center Hackathon">
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
  
        <div class="table-responsive">
          <table class="table table-borderless mb-0 bg-white p-3">
            <thead class="bg-light">
              <tr>
                <th>Period</th>
                <th>Begins</th>
                <th>Ends</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $row): 
                // mark the first row "active" — change as you like
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
    <aside class="col-lg-4 registration-sidebar">
      <div class="registration-card">
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
          <table class="stats-table">
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
			  <button type="submit" class="btn btn-register">
				Register
			  </button>
			</form>
		  <?php else: ?>
			<!-- already registered -->
			<a href="manage_teams.php" class="btn btn-team">
			  Find Teams
			</a>
			<a href="manage_teams.php?create=<?= $hid ?>" class="btn btn-team-create">
			  Create Team
			</a>
		  <?php endif; ?>
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

// helper to get "+7" (or "-4", etc) from a moment:
function getGMTOffsetH(m) {
  // utcOffset() is in minutes, so divide by 60
  const hours = m.utcOffset() / 60;
  // keep the sign ("+" or "–") in there
  return `${hours >= 0 ? '+' : ''}${hours}`;
}

// inject "GMT+7" into the "pen" link
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