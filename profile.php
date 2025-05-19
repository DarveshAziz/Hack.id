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
</style>
</head><body>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
  <h2 class="mb-4">Profile settings</h2>

  <?php foreach($errors as $e): ?>
     <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach;?>
  <?php if(isset($_GET['saved'])):?>
     <div class="alert alert-success">Profile updated!</div>
  <?php endif;?>

<form method="post" enctype="multipart/form-data">

  <!-- avatar + name -->
  <div class="text-center mb-4">
    <img id="avatarPreview" src="<?= htmlspecialchars($userRow['avatar']) ?>"
         class="avatar-big">
    <input type="file" id="avatarInput" name="avatar" accept="image/*" hidden>
    <h3 class="mt-3"><?= htmlspecialchars($prof['display_name'] ?: $_SESSION['username']) ?></h3>
    <p class="text-muted">Click the photo to upload a new one (max 2&nbsp;MB)</p>
  </div>

  <!-- public profile -->
  <h3 class="mb-3">Public profile</h3>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Display name</label>
      <input name="display_name" class="form-control"
             value="<?= htmlspecialchars($prof['display_name']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Location</label>
      <input name="location" class="form-control"
             value="<?= htmlspecialchars($prof['location']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Headline</label>
      <input name="headline" class="form-control"
             value="<?= htmlspecialchars($prof['headline']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">About me</label>
      <textarea name="about" rows="4"
                class="form-control"><?= htmlspecialchars($prof['about']) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Website</label>
      <input name="website" type="url" class="form-control"
             value="<?= htmlspecialchars($prof['website']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">GitHub</label>
      <input name="github" class="form-control"
             value="<?= htmlspecialchars($prof['github']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">LinkedIn</label>
      <input name="linkedin" class="form-control"
             value="<?= htmlspecialchars($prof['linkedin']) ?>">
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
