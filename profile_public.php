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

$myOwnedTeams = [];
if (isset($_SESSION['user_id'])) {
    $me = (int) $_SESSION['user_id'];
    $stmt = $mysqli->prepare("
        SELECT id, name, logo_url
          FROM teams
         WHERE creator_id = ?
    ");
    $stmt->bind_param('i', $me);
    $stmt->execute();
    $myOwnedTeams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ② Fetch teams *they* own (to offer "request to join")
$theirOwnedTeams = [];
{
    $them = $viewId; 
    $stmt = $mysqli->prepare("
        SELECT id, name, logo_url
          FROM teams
         WHERE creator_id = ?
    ");
    $stmt->bind_param('i', $them);
    $stmt->execute();
    $theirOwnedTeams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function skillLabel(int $pct): string
{
	if ($pct == 0)  return 'N/A';
    if ($pct < 50)  return 'Beginner';
    if ($pct < 75)  return 'Intermediate';
    return 'Advanced';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Hack.id - Find Your Team. Hack the Future </title>
<!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="Website Icon" type="png" href="img/Logo1.png" />
<title><?= htmlspecialchars($user['display_name']) ?> • Hack.id</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css"      rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ */
/* MODERN GLASSMORPHISM DESIGN SYSTEM */
/* ═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ */

:root {
    --primary-color: #582ec4;
    --primary-light: #7c5ce8;
    --primary-dark: #4a23a3;
    --bg-dark: #222222;
    --bg-darker: #1a1a1a;
    --text-light: #ffffff;
    --text-gray: #b8b8b8;
    --text-muted: #888888;
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.3);
    --shadow-hover: 0 12px 48px rgba(88, 46, 196, 0.3);
    --gradient-primary: linear-gradient(135deg, #582ec4 0%, #7c5ce8 100%);
    --gradient-card: linear-gradient(145deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-dark);
    color: var(--text-light);
    line-height: 1.6;
    overflow-x: hidden;
}

/* Animated Background */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #222;
    animation: backgroundMove 20s ease-in-out infinite alternate;
    z-index: -1;
}

@keyframes backgroundMove {
    0% { transform: translateX(0) translateY(0) scale(1); }
    100% { transform: translateX(-20px) translateY(-20px) scale(1.05); }
}

/* Container */
.container {
    max-width: 1200px;
    margin: 3rem auto;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
}

/* Glass Card System */
.glass-card {
    background: var(--gradient-card);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    box-shadow: var(--shadow-soft);
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
}

.glass-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.glass-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(88, 46, 196, 0.4);
}

/* Profile Header */
.profile-header {
    padding: 3rem;
    margin-bottom: 2rem;
}

.profile-content {
    display: flex;
    align-items: flex-start;
    gap: 2rem;
    flex-wrap: wrap;
}

.profile-avatar {
    position: relative;
}

.avatar-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary-color);
    box-shadow: 0 0 30px rgba(88, 46, 196, 0.4);
    transition: all 0.3s ease;
}

.avatar-image:hover {
    transform: scale(1.05);
    box-shadow: 0 0 40px rgba(88, 46, 196, 0.6);
}

.profile-info {
    flex: 1;
    min-width: 300px;
}

.profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: #6132d7;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-headline {
    font-size: 1.2rem;
    color: var(--text-gray);
    margin-bottom: 1rem;
    font-weight: 400;
}

.profile-location {
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-location i {
    color: var(--primary-color);
}

/* Social Links */
.social-links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.social-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(88, 46, 196, 0.1);
    border: 1px solid rgba(88, 46, 196, 0.3);
    border-radius: 50px;
    color: var(--text-light);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.social-link:hover {
    background: var(--gradient-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(88, 46, 196, 0.4);
}

.social-link i {
    font-size: 1.1rem;
}

/* Top Skills Section */
.top-skills {
    padding: 2rem 3rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: 1rem;
}

.skills-grid {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Skill Pills */
.skill-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: var(--gradient-card);
    backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.skill-pill::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: var(--pct, 0);
    height: 100%;
    background: var(--gradient-primary);
    opacity: 0.1;
    transition: all 0.3s ease;
}

.skill-pill:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(88, 46, 196, 0.3);
    border-color: var(--primary-color);
}

.skill-pill:hover::before {
    opacity: 0.2;
}

.skill-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 0.75rem;
    background: conic-gradient(var(--primary-color) calc(var(--pct) * 1%), rgba(255, 255, 255, 0.2) 0);
    position: relative;
    flex-shrink: 0;
}

.skill-circle::after {
    content: '';
    position: absolute;
    inset: 3px;
    border-radius: 50%;
    background: var(--bg-dark);
}

.skill-badge {
    margin-left: 0.75rem;
    padding: 0.25rem 0.75rem;
    background: var(--gradient-primary);
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
}

/* Action Buttons */
.action-section {
    margin: 2rem 0;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-primary-custom {
    background: var(--gradient-primary);
    border: none;
    padding: 1rem 2rem;
    border-radius: 50px;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(88, 46, 196, 0.3);
}

.btn-primary-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(88, 46, 196, 0.5);
    color: white;
}

.btn-outline-custom {
    background: transparent;
    border: 2px solid var(--primary-color);
    padding: 1rem 2rem;
    border-radius: 50px;
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.btn-outline-custom:hover {
    background: var(--gradient-primary);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(88, 46, 196, 0.4);
}

/* Dropdown */
.dropdown-menu {
    background: var(--gradient-card);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    padding: 0.5rem;
}

.dropdown-item {
    color: var(--text-light);
    padding: 0.75rem 1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background: var(--gradient-primary);
    color: white;
}

/* About Inline in Profile */
.about-inline {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.about-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-light);
    margin-bottom: 1rem;
    position: relative;
    display: inline-block;
}

.about-title::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--gradient-primary);
    border-radius: 1px;
}

/* About Section */
.about-section {
    margin: 2rem 0;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--text-light);
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--gradient-primary);
    border-radius: 2px;
}

.about-text {
    font-size: 1.1rem;
    line-height: 1.8;
    color: var(--text-gray);
}

/* Skills Matrix */
.skills-matrix {
    margin-top: 3rem;
}

.skill-category {
    margin-bottom: 3rem;
}

.category-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-light);
    margin-bottom: 1rem;
    position: relative;
    display: inline-block;
}

.category-title::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--primary-color);
    border-radius: 1px;
}

.subcategory-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--primary-light);
    margin: 1.5rem 0 1rem 0;
}

.skills-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
    max-width: 1200px;
    margin: 0rem auto;
    padding: 2rem 1rem;
    position: relative;
    z-index: 1;
    }

    .profile-header {
        padding: 2rem 1.5rem;
    }
    
    .profile-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 1.5rem;
    }
    
    .profile-avatar {
        align-self: center;
    }
    
    .avatar-image {
        width: 100px;
        height: 100px;
    }
    
    .profile-info {
        width: 100%;
        text-align: center;
    }
    
    .profile-name {
        font-size: 2rem;
        text-align: center;
    }
    
    .profile-headline {
        font-size: 1.1rem;
        text-align: center;
    }
    
    .profile-location {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .social-link {
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
    }
    
    .about-inline {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        text-align: center;
    }
    
    .about-title {
        font-size: 1.3rem;
        text-align: center;
    }
    
    .about-text {
        text-align: center;
    }
    
    .action-section {
        justify-content: center;
        flex-direction: column;
        align-items: center;
    }
    
    .btn-primary-custom,
    .btn-outline-custom {
        width: 100%;
        max-width: 280px;
        justify-content: center;
    }
    
    .skills-grid {
        justify-content: center;
    }
    
    .top-skills {
        padding: 1.5rem;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .container {
        margin: 0rem auto;
        padding: 1rem 0.5rem;
    }
    
    .profile-header {
        padding: 1.5rem 1rem;
    }
    
    .avatar-image {
        width: 100px;
        height: 100px;
    }
    
    .profile-name {
        font-size: 1.8rem;
    }
    
    .social-links {
        justify-content: center;
    }
}

/* Loading Animation */
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

.glass-card {
    animation: fadeInUp 0.6s ease-out;
}

.glass-card:nth-child(2) {
    animation-delay: 0.2s;
}

.glass-card:nth-child(3) {
    animation-delay: 0.4s;
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container">

  <!-- Profile Header with About -->
  <div class="glass-card profile-header">
    <div class="profile-content">
      <div class="profile-avatar">
        <img src="<?= htmlspecialchars($user['avatar']) ?>"
             class="avatar-image"
             alt="<?= htmlspecialchars($user['display_name']) ?>">
      </div>
      <div class="profile-info">
        <h1 class="profile-name"><?= htmlspecialchars($user['display_name']) ?></h1>
        <?php if($user['headline']): ?>
          <p class="profile-headline"><?= htmlspecialchars($user['headline']) ?></p>
        <?php endif; ?>
        <?php if($user['location']): ?>
          <p class="profile-location">
            <i class="fas fa-map-marker-alt"></i>
            <?= htmlspecialchars($user['location']) ?>
          </p>
        <?php endif; ?>

        <!-- Social Links -->
        <div class="social-links">
          <?php if($user['website']): ?>
            <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" class="social-link">
              <i class="fas fa-globe"></i>Website
            </a>
          <?php endif;?>
          <?php if($user['github']): ?>
            <a href="https://github.com/<?= ltrim($user['github'],'https://github.com/') ?>" target="_blank" class="social-link">
              <i class="fab fa-github"></i>GitHub
            </a>
          <?php endif;?>
          <?php if($user['linkedin']): ?>
            <a href="<?= htmlspecialchars($user['linkedin']) ?>" target="_blank" class="social-link">
              <i class="fab fa-linkedin"></i>LinkedIn
            </a>
          <?php endif;?>
        </div>

        <!-- About Section -->
        <?php if($user['about']): ?>
          <div class="about-inline">
            <h3 class="about-title">About</h3>
            <p class="about-text"><?= nl2br(htmlspecialchars($user['about'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Top Skills -->
    <?php if($top3): ?>
    <div class="top-skills">
      <div class="skills-grid">
        <?php foreach ($top3 as $t): ?>
          <?php $label = skillLabel((int)$t['level']); ?>
          <div class="skill-pill" style="--pct:<?= $t['level'] ?>;">
            <div class="skill-circle"></div>
            <?= htmlspecialchars($t['name']) ?>
            <span class="skill-badge"><?= $label ?></span>
          </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Action Buttons -->
  <div class="action-section">
    <?php if (isset($_SESSION['user_id']) && $myOwnedTeams): ?>
      <div class="dropdown">
        <button class="btn-outline-custom dropdown-toggle" type="button" id="inviteDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user-plus"></i>Invite to Team
        </button>
        <ul class="dropdown-menu" aria-labelledby="inviteDropdown">
          <?php foreach($myOwnedTeams as $team): ?>
            <li>
              <form action="invite_team.php" method="post" class="m-0 p-0">
                <input type="hidden" name="team_id" value="<?= (int)$team['id'] ?>">
                <input type="hidden" name="to_user_id" value="<?= $viewId ?>">
                <button type="submit" class="dropdown-item">
                  <?= htmlspecialchars($team['name']) ?>
                </button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $theirOwnedTeams): ?>
      <div class="dropdown">
        <button class="btn-outline-custom dropdown-toggle" type="button" id="requestDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-handshake"></i>Request to Join
        </button>
        <ul class="dropdown-menu" aria-labelledby="requestDropdown">
          <?php foreach ($theirOwnedTeams as $team): ?>
            <li>
              <a class="dropdown-item" href="request_team.php?action=request&team_id=<?= $team['id'] ?>">
                <?= htmlspecialchars($team['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $viewId): ?>
      <a href="start_chat.php?uid=<?= $viewId ?>" class="btn-primary-custom">
        <i class="fas fa-paper-plane"></i>Message
      </a>
    <?php endif; ?>
  </div>



  <!-- Full Skills Matrix -->
  <?php if($skills): ?>
    <div class="glass-card skills-matrix">
      <div style="padding: 2rem 3rem;">
        <h2 class="section-title">Skills & Expertise</h2>
        <?php
          $currentCat=''; $currentSub='';
          foreach ($skills as $s):
            if ($s['category'] !== $currentCat){
               $currentCat = $s['category']; $currentSub='';
               echo "<div class='skill-category'>";
               echo "<h3 class='category-title'>".htmlspecialchars($currentCat)."</h3>";
            }
            if ($s['subcategory'] !== $currentSub){
               $currentSub = $s['subcategory'];
               echo "<h4 class='subcategory-title'>".htmlspecialchars($currentSub)."</h4>";
               echo "<div class='skills-row'>";
            }
            $label = skillLabel((int)$s['level']);
        ?>
            <div class="skill-pill" style="--pct:<?= $s['level'] ?>;">
              <div class="skill-circle"></div>
              <?= htmlspecialchars($s['name']) ?>
              <span class="skill-badge"><?= $label ?></span>
            </div>
        <?php 
          // Check if next skill has different subcategory or category to close div
          $nextIndex = array_search($s, $skills) + 1;
          if ($nextIndex >= count($skills) || 
              (isset($skills[$nextIndex]) && $skills[$nextIndex]['subcategory'] !== $currentSub)) {
              echo "</div>"; // Close skills-row
          }
          if ($nextIndex >= count($skills) || 
              (isset($skills[$nextIndex]) && $skills[$nextIndex]['category'] !== $currentCat)) {
              echo "</div>"; // Close skill-category
          }
          endforeach; 
        ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>