<?php
/*****************************************************************
 *  profile.php  –  editable profile with avatar & skill matrix  *
 *****************************************************************/
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

/* ────────────────────── one-time schema guards ───────────────────── */
$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS
                avatar VARCHAR(150) DEFAULT NULL");

$mysqli->query("
CREATE TABLE IF NOT EXISTS user_profile (
  user_id      INT UNSIGNED PRIMARY KEY,
  display_name VARCHAR(60),
  headline     VARCHAR(120),
  about        TEXT,
  location     VARCHAR(100),
  website      VARCHAR(120),
  github       VARCHAR(80),
  linkedin     VARCHAR(80),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$mysqli->query("
CREATE TABLE IF NOT EXISTS skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category     VARCHAR(40),
  subcategory  VARCHAR(40),
  name         VARCHAR(80) UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$mysqli->query("
CREATE TABLE IF NOT EXISTS user_skill (
  user_id  INT UNSIGNED,
  skill_id INT UNSIGNED,
  level    TINYINT UNSIGNED DEFAULT 0,
  PRIMARY KEY (user_id, skill_id),
  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/* ───────────── seed skills master list (runs once) ──────────────── */
if (!$mysqli->query("SELECT 1 FROM skills LIMIT 1")->fetch_row()) {
    $skillSeed = [
      /* Frontend */
      ['Frontend','Languages','HTML'],['Frontend','Languages','CSS'],
      ['Frontend','Languages','JavaScript'],
      ['Frontend','Skills','Responsive layouts'],
      ['Frontend','Skills','Accessibility'],
      ['Frontend','Skills','Performance optimizations'],
      ['Frontend','Others','React'],['Frontend','Others','Webpack'],
      ['Frontend','Others','Tailwind'],

      /* Design */
      ['Design','Languages','Figma'],['Design','Languages','Sketch'],
      ['Design','Languages','Adobe XD'],
      ['Design','Skills','Wireframing & prototyping'],
      ['Design','Skills','User flows & IA'],
      ['Design','Skills','Visual hierarchy & typography'],
      ['Design','Others','Asset handoff'],
      ['Design','Others','Icon/vector work'],
      ['Design','Others','Usability testing'],

      /* Backend */
      ['Backend','Languages','Node.js / TypeScript'],
      ['Backend','Languages','Python'],
      ['Backend','Languages','Java'],
      ['Backend','Skills','REST/GraphQL API design'],
      ['Backend','Skills','Data modeling & migrations'],
      ['Backend','Skills','Auth & security'],
      ['Backend','Others','Express'],['Backend','Others','Docker'],
      ['Backend','Others','Unit testing'],

      /* Mobile Dev */
      ['Mobile Dev','Languages','Swift'],['Mobile Dev','Languages','Kotlin'],
      ['Mobile Dev','Languages','Dart / Flutter'],
      ['Mobile Dev','Skills','Native UI/navigation'],
      ['Mobile Dev','Skills','Device APIs'],
      ['Mobile Dev','Skills','Offline data'],
      ['Mobile Dev','Others','React Native'],
      ['Mobile Dev','Others','APK/TestFlight deployment'],
      ['Mobile Dev','Others','AsyncStorage/SQLite'],

      /* AI & ML */
      ['AI & ML','Languages','Python'],
      ['AI & ML','Languages','NumPy / pandas'],
      ['AI & ML','Languages','TensorFlow / PyTorch'],
      ['AI & ML','Skills','Data cleaning & feature engineering'],
      ['AI & ML','Skills','Model training'],
      ['AI & ML','Skills','Model evaluation'],
      ['AI & ML','Others','Inference API development'],
      ['AI & ML','Others','Cloud ML services'],
      ['AI & ML','Others','Model versioning'],

      /* Cloud & DevOps */
      ['Cloud & DevOps','Languages','Terraform'],
      ['Cloud & DevOps','Languages','YAML'],
      ['Cloud & DevOps','Languages','Bash'],
      ['Cloud & DevOps','Skills','Docker & Kubernetes'],
      ['Cloud & DevOps','Skills','CI/CD pipelines'],
      ['Cloud & DevOps','Skills','Infra provisioning'],
      ['Cloud & DevOps','Others','Monitoring (Prometheus/Grafana)'],
      ['Cloud & DevOps','Others','DNS/CDN basics'],
      ['Cloud & DevOps','Others','Secrets management'],
    ];
    $ins=$mysqli->prepare("INSERT IGNORE INTO skills(category,subcategory,name) VALUES (?,?,?)");
    foreach($skillSeed as $row){ $ins->bind_param('sss',...$row); $ins->execute(); }
}

/* ──────────────────────── fetch current data ───────────────────── */
$userRow = $mysqli->query("
      SELECT username, COALESCE(avatar,'img/default-avatar.png') AS avatar
      FROM users WHERE id=$uid")->fetch_assoc();

$prof = $mysqli->query("
      SELECT * FROM user_profile WHERE user_id=$uid")->fetch_assoc() ?:
      ['display_name'=>'','headline'=>'','about'=>'','location'=>'',
       'website'=>'','github'=>'','linkedin'=>''];

$skillRows = $mysqli->query("
      SELECT * FROM skills ORDER BY category,subcategory,name")
      ->fetch_all(MYSQLI_ASSOC);

$userLvls=[]; $rs=$mysqli->query("SELECT skill_id,level FROM user_skill WHERE user_id=$uid");
while($r=$rs->fetch_assoc()) $userLvls[$r['skill_id']]=$r['level'];

/* ───────────────────────── form save logic ─────────────────────── */
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {

    /* ---------- avatar upload (optional) ---------- */
    if (!empty($_FILES['avatar']['name'])) {
        $valid = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
        $f = $_FILES['avatar'];
        if ($f['error']===UPLOAD_ERR_OK &&
            isset($valid[$f['type']]) &&
            $f['size']<=2*1024*1024) {

            $ext=$valid[$f['type']];
            if (!is_dir('img/avatars')) mkdir('img/avatars',0777,true);
            foreach (glob("img/avatars/$uid.*") as $old) unlink($old);
            $dest="img/avatars/$uid.$ext";
            if (move_uploaded_file($f['tmp_name'],$dest)) {
                $stmt=$mysqli->prepare("UPDATE users SET avatar=? WHERE id=?");
                $stmt->bind_param('si',$dest,$uid); $stmt->execute();
                $_SESSION['avatar']=$userRow['avatar']=$dest;
            } else $errors[]='Failed to save uploaded image.';
        } else $errors[]='Avatar must be JPG/PNG/GIF and ≤ 2 MB.';
    }

    /* ---------- profile text fields ---------- */
    $fields=['display_name','headline','about','location','website','github','linkedin'];
    $p=[];
    foreach($fields as $f){ $p[$f]=trim($_POST[$f] ?? ''); }

    $stmt=$mysqli->prepare("REPLACE INTO user_profile
        (user_id,display_name,headline,about,location,website,github,linkedin)
        VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param('isssssss',$uid,
        $p['display_name'],$p['headline'],$p['about'],$p['location'],
        $p['website'],$p['github'],$p['linkedin']);
    $stmt->execute();

    if ($p['display_name']) $_SESSION['username']=$p['display_name'];

    /* ---------- skills ---------- */
    $mysqli->query("DELETE FROM user_skill WHERE user_id=$uid");
    $ins=$mysqli->prepare("INSERT INTO user_skill(user_id,skill_id,level) VALUES (?,?,?)");
    foreach($_POST['skill']??[] as $sid=>$lvl){
        $lvl=max(0,min(100,(int)$lvl));
        $ins->bind_param('iii',$uid,$sid,$lvl); $ins->execute();
    }

    if(!$errors){ header('Location: profile.php?saved=1'); exit; }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<title>My profile • Hack.id</title>
<link rel="Website Icon" type="png" href="img/Logo1.png" />
<!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"      rel="stylesheet">
<style>
.avatar-big{width:140px;height:140px;border-radius:50%;
            object-fit:cover;border:4px solid var(--bs-primary);cursor:pointer;}
.skill-pill{display:inline-flex;align-items:center;border:1px solid var(--bs-primary);
            border-radius:20px;padding:.25rem .75rem;margin:.25rem;font-size:.9rem;}
.skill-pill .circle{width:26px;height:26px;border-radius:50%;margin-right:.5rem;
                    background:#e8e8e8;position:relative;flex-shrink:0;}
.skill-pill .circle::after{content:'';position:absolute;inset:0;border-radius:50%;
                    background:conic-gradient(var(--bs-primary) calc(var(--pct)*1%),#e8e8e8 0);}

/* Dark Mode Base */
body {
    background: #0a0a0a;
    color: #e2e8f0;
}

.container {
    min-height: 100vh;
    border-radius: 20px;
    padding: 2rem !important;
}

/* Enhanced Profile Styles - Dark Mode */
.profile-card {
    background: rgb(17, 17, 17);
    border-radius: 25px;
    padding: 0;
    overflow: hidden;
    margin-bottom: 2rem;
    position: relative;
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
}

.profile-header {
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 2rem;
    margin: 2rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 2;
}

.profile-form-section {
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 2.5rem;
    margin: 2rem;
    position: relative;
    z-index: 2;
}

.floating-label {
    position: relative;
    margin-bottom: 1.5rem;
}

.floating-label input,
.floating-label textarea {
    width: 100%;
    padding: 1rem 1rem 0.5rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    background: rgba(26, 26, 26, 0.6);
    backdrop-filter: blur(10px);
    font-size: 1rem;
    transition: all 0.3s ease;
    outline: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    color: #e2e8f0;
}

.floating-label input::placeholder,
.floating-label textarea::placeholder {
    color: transparent;
}

.floating-label input:focus,
.floating-label textarea:focus {
    border-color: #6132d7;
    background:rgb(22, 22, 22);
    box-shadow: 0 0 20px rgba(97, 50, 215, 0.3);
    transform: translateY(-2px);
}

.floating-label label {
    position: absolute;
    top: 1rem;
    left: 1rem;
    font-size: 1rem;
    color: #94a3b8;
    transition: all 0.3s ease;
    pointer-events: none;
    font-weight: 500;
}

.floating-label input:focus ~ label,
.floating-label input:not(:placeholder-shown) ~ label,
.floating-label textarea:focus ~ label,
.floating-label textarea:not(:placeholder-shown) ~ label {
    top: 0.25rem;
    font-size: 0.75rem;
    color: #6132d7;
    font-weight: 600;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.form-row-single {
    display: grid;
    grid-template-columns: 1fr;
    margin-bottom: 1rem;
}

.form-row-triple {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.icon-input {
    position: relative;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 2rem;
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #6132d7, #452499);
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(97, 50, 215, 0.3);
}

/* Dark Mode Alert Styles */
.alert-danger {
    background: rgba(220, 38, 38, 0.2);
    border: 1px solid rgba(220, 38, 38, 0.3);
    color: #fca5a5;
}

.alert-success {
    background: rgba(34, 197, 94, 0.2);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

/* Dark Mode Typography */
h2, h3, h5{
  color: #452499;
}

.text-muted {
    color: #94a3b8 !important;
}

/* Dark Mode Button */
.btn-primary {
    background: linear-gradient(135deg, #6132d7 0%, #452499 100%);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(97, 50, 215, 0.3);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5729c9 0%, #3a1f7f 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(97, 50, 215, 0.4);
}

/* Dark Mode Skills Section */
.skill-pill {
    background: rgba(30, 41, 59, 0.6);
    border: 1px solid rgba(97, 50, 215, 0.3);
    color: #e2e8f0;
    backdrop-filter: blur(10px);
}

.skill-pill .circle {
    background: #374151;
}

.skill-pill .circle::after {
    background: conic-gradient(#6132d7 calc(var(--pct)*1%), #374151 0);
}

@media (max-width: 768px) {
    .form-row,
    .form-row-triple {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .profile-header,
    .profile-form-section {
        margin: 1rem;
        padding: 1.5rem;
    }
}
</style>
</head><body>

<?php include 'includes/header.php'; ?>

<div class="container py-5 mt-5">
  <h2 class="mb-4">Profile settings</h2>

  <?php foreach($errors as $e): ?>
     <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach;?>
  <?php if(isset($_GET['saved'])):?>
     <div class="alert alert-success">Profile updated!</div>
  <?php endif;?>

<form method="post" enctype="multipart/form-data">

  <!-- avatar + name -->
  <div class="profile-card">
    <div class="profile-header text-center">
      <img id="avatarPreview" src="<?= htmlspecialchars($userRow['avatar']) ?>"
           class="avatar-big mb-3">
      <input type="file" id="avatarInput" name="avatar" accept="image/*" hidden>
      <h3 class="mb-2"><?= htmlspecialchars($prof['display_name'] ?: $_SESSION['username']) ?></h3>
      <p class="text-muted mb-0">Click the photo to upload a new one (max 2&nbsp;MB)</p>
    </div>
  </div>

  <!-- Enhanced public profile section -->
  <div class="profile-card">
    <div class="profile-form-section">
      <h3 class="section-title">Public Profile</h3>
      
      <div class="form-row">
        <div class="floating-label">
          <input type="text" name="display_name" id="display_name" placeholder=" "
                 value="<?= htmlspecialchars($prof['display_name']) ?>">
          <label for="display_name">Display Name</label>
        </div>
        <div class="floating-label icon-input location">
          <input type="text" name="location" id="location" placeholder=" "
                 value="<?= htmlspecialchars($prof['location']) ?>">
          <label for="location">Location</label>
        </div>
      </div>

      <div class="form-row-single">
        <div class="floating-label">
          <input type="text" name="headline" id="headline" placeholder=" "
                 value="<?= htmlspecialchars($prof['headline']) ?>">
          <label for="headline">Professional Headline</label>
        </div>
      </div>

      <div class="form-row-single">
        <div class="floating-label">
          <textarea name="about" id="about" rows="4" placeholder=" "><?= htmlspecialchars($prof['about']) ?></textarea>
          <label for="about">About Me</label>
        </div>
      </div>

      <div class="form-row-triple">
        <div class="floating-label icon-input website">
          <input type="url" name="website" id="website" placeholder=" "
                 value="<?= htmlspecialchars($prof['website']) ?>">
          <label for="website">Website</label>
        </div>
        <div class="floating-label icon-input github">
          <input type="text" name="github" id="github" placeholder=" "
                 value="<?= htmlspecialchars($prof['github']) ?>">
          <label for="github">GitHub</label>
        </div>
        <div class="floating-label icon-input linkedin">
          <input type="text" name="linkedin" id="linkedin" placeholder=" "
                 value="<?= htmlspecialchars($prof['linkedin']) ?>">
          <label for="linkedin">LinkedIn</label>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <!-- skills -->
  <h2 class="mb-3">Skill levels</h2>
<?php
$cat='';$sub='';
foreach($skillRows as $s){
  if($s['category']!==$cat){
      $cat=$s['category'];$sub='';
      echo "<h3 class='mt-4'>".$cat."</h3>";
  }
  if($s['subcategory']!==$sub){
      $sub=$s['subcategory'];
      echo "<h5 class='mt-3'>".$sub."</h5>";
  }
  $sid=$s['id']; $lvl=$userLvls[$sid]??0;
  echo '<label class="skill-pill" style="--pct:'.$lvl.'">';
  echo '<span class="circle"></span>'.htmlspecialchars($s['name']);
  echo '<input type="range" min="0" max="100" value="'.$lvl.
       '" name="skill['.$sid.']" class="w-100 ms-2"></label>';
}
?>
  <div class="mt-4">
     <button class="btn btn-primary">Save profile</button>
  </div>
</form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* live skill circles */
document.querySelectorAll('input[type=range]').forEach(r=>{
  r.addEventListener('input',e=>{
    e.target.parentElement.style.setProperty('--pct',e.target.value);
  });
});

/* avatar picker */
const img=document.getElementById('avatarPreview');
const file=document.getElementById('avatarInput');
img.onclick=()=>file.click();
file.onchange=e=>{
   if(e.target.files && e.target.files[0])
     img.src = URL.createObjectURL(e.target.files[0]);
};
</script>
</body></html>