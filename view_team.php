<?php
// view_team.php — main page for a single team (with Edit mode)
include 'config.php';

// 1️⃣ Validate input & login
$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$teamId || !isset($_SESSION['user_id'])) {
  http_response_code(404);
  exit('Team not found or login required');
}
$uid = (int)$_SESSION['user_id'];

// 2️⃣ Load the team + hackathon
$stmt = $mysqli->prepare("
  SELECT
    t.id,
    t.name,
    t.description,
    t.logo_url,
    t.creator_id,
    h.slug     AS hack_slug,
    h.title    AS hack_title
  FROM teams t
  JOIN hackathons h ON h.id = t.hackathon_id
  WHERE t.id = ?
");
$stmt->bind_param('i', $teamId);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$team) {
  http_response_code(404);
  exit('Team not found');
}

// 3️⃣ Load members
$stmt = $mysqli->prepare("
  SELECT
    u.id,
    COALESCE(up.display_name,u.username) AS display_name,
    COALESCE(u.avatar,'img/default-avatar.png') AS avatar
  FROM team_members m
  JOIN users u ON u.id = m.user_id
  LEFT JOIN user_profile up ON up.user_id = u.id
  WHERE m.team_id = ?
  ORDER BY u.username
");
$stmt->bind_param('i', $teamId);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4️⃣ Load existing roles
$rolesRes = $mysqli->prepare("
  SELECT user_id, role
    FROM team_member_roles
   WHERE team_id = ?
");
$rolesRes->bind_param('i', $teamId);
$rolesRes->execute();
$memberRoles = [];
foreach ($rolesRes->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
  $memberRoles[$r['user_id']][] = $r['role'];
}
$rolesRes->close();

// 5️⃣ Define all possible roles
$allRoles = [
  'AI & ML',
  'Backend',
  'Cloud & DevOps',
  'Design',
  'Frontend',
  'Mobile Dev',
];

// 6️⃣ Are we the owner?
$isOwner = $uid === (int)$team['creator_id'];

// 7️⃣ Handle form submission
if ($isOwner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
  // a) Description
  $desc = trim($_POST['description'] ?? '');
  $u = $mysqli->prepare("UPDATE teams SET description = ? WHERE id = ?");
  $u->bind_param('si', $desc, $teamId);
  $u->execute();
  $u->close();

  // b) Logo upload
  if (!empty($_FILES['logo']['tmp_name'])) {
    $dest = 'uploads/team_' . $teamId . '_' . basename($_FILES['logo']['name']);
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
      $u = $mysqli->prepare("UPDATE teams SET logo_url = ? WHERE id = ?");
      $u->bind_param('si', $dest, $teamId);
      $u->execute();
      $u->close();
    }
  }

  // c) Transfer ownership
  $newOwner = (int)($_POST['owner_id'] ?? 0);
  if ($newOwner && $newOwner !== $team['creator_id']) {
    $u = $mysqli->prepare("UPDATE teams SET creator_id = ? WHERE id = ?");
    $u->bind_param('ii', $newOwner, $teamId);
    $u->execute();
    $u->close();
  }

  // 1) prepare
  $stmt = $mysqli->prepare("DELETE FROM team_member_roles WHERE team_id = ?");
  if (! $stmt) {
    die("Prepare failed: " . $mysqli->error);
  }

  // 2) bind
  if (! $stmt->bind_param('i', $teamId)) {
    die("Bind failed: " . $stmt->error);
  }

  // 3) execute
  if (! $stmt->execute()) {
    die("Execute failed: " . $stmt->error);
  }

  // 4) clean up
  $stmt->close();

  if (!empty($_POST['roles']) && is_array($_POST['roles'])) {
    $iStmt = $mysqli->prepare("
          INSERT INTO team_member_roles (team_id,user_id,role)
          VALUES (?,?,?)
        ");
    foreach ($_POST['roles'] as $uidKey => $rList) {
      foreach ((array)$rList as $rName) {
        $iStmt->bind_param('iis', $teamId, $uidKey, $rName);
        $iStmt->execute();
      }
    }
    $iStmt->close();
  }

  // redirect back
  header("Location: view_team.php?id={$teamId}");
  exit;
}

// toggle edit mode?
$editMode = $isOwner && isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <!-- Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet" />
  <title><?= htmlspecialchars($team['name']) ?> • <?= htmlspecialchars($team['hack_title']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <style>
    /* css/style.css */

    /* --- GLOBAL STYLES --- */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #222222;
      /* Main background color */
      color: #f0f0f0;
      /* Default light text color */
      line-height: 1.6;
      margin: 0;
      /* Ensure no default body margin */
    }

    /* --- TYPOGRAPHY & LINKS --- */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      color: #ffffff;
      /* Brighter white for headings */
      font-weight: 600;
    }

    /* Style for the main team name heading */
    .container.py-5 h1.h2.mb-1 {
      margin-top: 1.5rem;
      color: #582ec4;
      /* Primary color for main title */
      font-weight: 700;
    }
    
    a {
      color: #7e57c2;
      /* A slightly lighter shade of primary for links */
      text-decoration: none;
      transition: color 0.2s ease-in-out;
    }

    a:hover {
      color: #582ec4;
      /* Primary color on hover */
      text-decoration: underline;
    }

    p.text-muted,
    .small.text-muted {
      color: #a0a0a0 !important;
      /* Lighter muted color for dark background */
    }

    /* --- HEADER (Generic styles for 'includes/header.php') --- */
    /* Anda mungkin perlu menyesuaikan selector ini jika header.php memiliki struktur spesifik */
    /* Misalnya, jika header utama memiliki class .navbar atau .main-header */
    /*
header {
  background-color: #1c1c1c;
  padding: 1rem 0;
  border-bottom: 1px solid #333333;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
header .container { // Jika header Anda menggunakan container
  display: flex;
  justify-content: space-between;
  align-items: center;
}
header a { // Link di dalam header
  color: #f0f0f0;
  font-weight: 500;
}
header a:hover {
  color: #582ec4;
  text-decoration: none;
}
*/


    /* --- CONTAINER & LAYOUT --- */
    .container.py-5 {
      padding-top: 3rem !important;
      padding-bottom: 3rem !important;
    }

    /* --- BUTTONS --- */
    .btn {
      font-weight: 500;
      border-radius: 0.375rem;
      /* Bootstrap 5 default */
      padding: 0.6rem 1.2rem;
      transition: all 0.2s ease-in-out;
      letter-spacing: 0.5px;
    }

    .btn-primary {
      background-color: #582ec4;
      /* Primary color */
      border-color: #582ec4;
      color: #ffffff;
    }

    .btn-primary:hover,
    .btn-primary:focus {
      background-color: #4a25a1;
      /* Darker shade for hover/focus */
      border-color: #4a25a1;
      color: #ffffff;
      box-shadow: 0 0 0 0.25rem rgba(88, 46, 196, 0.5);
    }

    .btn-outline-primary {
      color: #582ec4;
      border-color: #582ec4;
    }

    .btn-outline-primary:hover,
    .btn-outline-primary:focus {
      background-color: #582ec4;
      color: #ffffff;
      box-shadow: 0 0 0 0.25rem rgba(88, 46, 196, 0.5);
    }

    .btn-outline-primary i.fas {
      /* Ensure icon color matches button text on hover */
      color: inherit;
    }


    .btn-secondary-team {
      background-color: #4a4a4a;
      border-color: #4a4a4a;
      color: #f0f0f0;
    }

    .btn-secondary-team:hover,
    .btn-secondary-team:focus {
      background-color: #3a3a3a;
      border-color: #3a3a3a;
      color: #ffffff;
      box-shadow: 0 0 0 0.25rem rgba(74, 74, 74, 0.5);
    }


    /* --- FORMS --- */
    .form-label {
      color: #cccccc;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .form-control,
    .form-select {
      background-color: #2d2d2d;
      border: 1px solid #444444;
      color: #f0f0f0;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
      background-color: #333333;
      color: #f0f0f0;
      border-color: #582ec4;
      box-shadow: 0 0 0 0.25rem rgba(88, 46, 196, 0.3);
    }

    .form-control::placeholder {
      color: #888888;
    }

    textarea.form-control {
      min-height: 120px;
      /* Increased height for better usability */
    }

    .form-check-input {
      background-color: #2d2d2d;
      border: 1px solid #555555;
      margin-top: 0.3em;
      /* Align better with label */
    }

    .form-check-input:checked {
      background-color: #582ec4;
      border-color: #582ec4;
    }

    .form-check-input:focus {
      border-color: #7e57c2;
      box-shadow: 0 0 0 0.25rem rgba(88, 46, 196, 0.25);
    }

    .form-check-label {
      color: #dddddd;
      padding-left: 0.25em;
    }

    /* --- CARDS (for members display) --- */
    .card {
      background-color: #2a2a2a;
      /* Slightly lighter than main bg for depth */
      border: 1px solid #383838;
      border-radius: 0.5rem;
      /* Softer corners */
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      /* More pronounced shadow */
      transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
      height: 100%;
      /* Ensure cards in a row have same height */
    }

    .card:hover {
      transform: translateY(-5px) scale(1.02);
      /* Slight lift and scale on hover */
      box-shadow: 0 10px 25px rgba(88, 46, 196, 0.25);
      /* Primary color glow on hover */
    }

    .card-body {
      padding: 1.5rem;
      /* Increased padding */
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .card-title {
      color: #ffffff;
      font-weight: 600;
      margin-bottom: 0.25rem !important;
      /* Overriding Bootstrap's mb-2 if needed */
    }

    .badge.bg-success {
      background-color: #38a169 !important;
      color: #ffffff;
      font-size: 0.7em;
      padding: 0.3em 0.6em;
      margin-left: 0.5em;
      vertical-align: middle;
      /* Align better with text */
    }

    /* --- IMAGES --- */
    img.img-thumbnail {
      background-color: #2d2d2d;
      border: 2px solid #444444;
      /* Slightly thicker border */
      padding: 0.3rem;
      max-width: 200px;
      /* As per inline style */
      border-radius: 0.375rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    img.rounded-circle {
      /* For avatars in member cards and chat */
      border: 3px solid #582ec4;
      /* Primary color border for avatars */
      box-shadow: 0 0 10px rgba(88, 46, 196, 0.3);
      /* Soft glow */
    }

    /* --- CHAT INTERFACE --- */
    #chat-box {
      background-color: #282828;
      border: 1px solid #582ec4;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1rem;
      max-height: 400px;
      overflow-y: auto;
    }

    /* Custom scrollbar for chat (Webkit browsers - Chrome, Safari, Edge) */
    #chat-box::-webkit-scrollbar {
      width: 10px;
    }

    #chat-box::-webkit-scrollbar-track {
      background: #2d2d2d;
      border-radius: 10px;
    }

    #chat-box::-webkit-scrollbar-thumb {
      background: #582ec4;
      border-radius: 10px;
      border: 2px solid #2d2d2d;
      /* Creates padding around thumb */
    }

    #chat-box::-webkit-scrollbar-thumb:hover {
      background: #4a25a1;
    }

    /* Firefox scrollbar */
    #chat-box {
      scrollbar-width: thin;
      scrollbar-color: #582ec4 #2d2d2d;
    }

    /* Chat message styling */
    #chat-box .d-flex.align-items-start {
      margin-bottom: 0.75rem !important;
      /* Spacing between messages */
    }

    #chat-box .d-flex.align-items-start>img.rounded-circle.me-2 {
      /* Avatar in chat */
      width: 36px !important;
      /* Slightly larger chat avatars */
      height: 36px !important;
      border-width: 2px;
      /* Thinner border for smaller chat avatars */
      box-shadow: 0 0 5px rgba(88, 46, 196, 0.2);
    }

    #chat-box .small.text-muted strong {
      color: #7e57c2;
      /* Username color in chat */
      font-weight: 600;
    }

    #chat-box .small.text-muted {
      /* Timestamp */
      color: #909090 !important;
      font-size: 0.8em;
    }

    /* Message body bubble */
    #chat-box div>div>div:last-child {
      background-color: #353535;
      /* Slightly lighter bubble */
      padding: 0.6rem 0.9rem;
      border-radius: 10px;
      /* More rounded bubbles */
      display: inline-block;
      max-width: calc(100% - 40px);
      /* Ensure it doesn't overflow too much */
      color: #f0f0f0;
      line-height: 1.4;
      word-wrap: break-word;
    }

    /* Input group for chat */
    .input-group {
      border-radius: 0.375rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .input-group .form-control#chat-input {
      border-right: 0;
      border-top-left-radius: 0.375rem;
      border-bottom-left-radius: 0.375rem;
      height: calc(1.5em + 1.2rem + 2px);
      /* Match button height */
    }

    .input-group .form-control#chat-input:focus {
      box-shadow: 0 0 0 0.25rem rgba(88, 46, 196, 0.3);
      border-color: #582ec4;
      z-index: 1;
      /* Ensure focus shadow is on top */
    }

    .input-group #chat-send.btn-primary {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      height: calc(1.5em + 1.2rem + 2px);
      /* Match input height */
      z-index: 2;
      /* Ensure button is on top of input border */
    }


    /* --- SPECIFIC ELEMENT ADJUSTMENTS BASED ON HTML --- */

    /* Edit Mode Form Sections */
    form div.mb-3>strong {
      /* For member names in role editor */
      color: #582ec4;
      /* Primary color for emphasis */
      font-size: 1.1em;
      display: block;
      /* Make it a block for better spacing */
      margin-bottom: 0.5rem;
    }

    form div.mb-3>.form-check.form-check-inline {
      margin-top: 0.3rem;
      margin-right: 1rem;
      /* Spacing between checkboxes */
    }

    /* View Mode - Team Description and Logo */
    .row.mb-4>.col-md-8 p {
      /* Team description */
      font-size: 1.05rem;
      color: #cccccc;
      background-color: #282828;
      padding: 1rem;
      border-radius: 0.375rem;
    }

    /* Member roles list in view mode card */
    .card-body .small.text-muted {
      font-style: normal;
      /* Remove italic for better readability if preferred */
      color: #b0b0b0 !important;
      font-size: 0.85em;
    }

    /* Edit icon on button */
    i.fas.fa-edit {
      margin-right: 0.4em;
    }

    /* --- FOOTER (Generic styles for 'includes/footer.php') --- */
    /*
footer {
  background-color: #1a1a1a;
  color: #888888;
  padding: 2.5rem 0;
  text-align: center;
  border-top: 1px solid #333333;
  margin-top: 3rem;
  font-size: 0.9em;
}
footer a {
  color: #aaaaaa;
}
footer a:hover {
  color: #582ec4;
}
*/

    /* Bootstrap column padding consistency */
    .row>* {
      padding-right: calc(var(--bs-gutter-x) * .5);
      padding-left: calc(var(--bs-gutter-x) * .5);
      margin-top: var(--bs-gutter-y);
    }


    /* Responsive adjustments */
    @media (max-width: 767.98px) {
      h1.h2.mb-1 {
        font-size: 1.75rem;
        /* Slightly smaller on mobile */
      }

      .col-md-4.text-end {
        text-align: left !important;
        /* Stack edit button neatly on mobile */
        margin-top: 1rem;
      }

      .card {
        margin-bottom: 1.5rem;
        /* Ensure cards don't touch if they wrap */
      }

      .card:hover {
        transform: translateY(-3px) scale(1.01);
        /* Less dramatic hover on mobile */
      }

      #chat-box div>div>div:last-child {
        /* Message body on mobile */
        max-width: calc(100% - 10px);
      }

      .container.py-5 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
      }
    }

    @media (max-width: 575.98px) {
      .display-name-roles {
        /* If you had a wrapper for name and roles */
        text-align: center;
      }

      .col-6.col-md-4.col-lg-3 {
        /* Member cards column */
        flex: 0 0 auto;
        width: 50%;
        /* Two cards per row on small screens */
      }
    }
  </style>
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <div class="container py-5">

    <div class="row align-items-center mb-4">
      <div class="col-md-8">
        <h1 class="h2 mb-1"><?= htmlspecialchars($team['name']) ?></h1>
        <p class="text-muted">
          Hackathon:
          <a href="hackathon_view.php?slug=<?= urlencode($team['hack_slug']) ?>">
            <?= htmlspecialchars($team['hack_title']) ?>
          </a>
        </p>
      </div>
      <div class="col-md-4 text-end">
        <?php if ($isOwner && !$editMode): ?>
          <a href="?id=<?= $teamId ?>&edit=1" class="btn btn-outline-primary">
            <i class="fas fa-edit"></i> Edit Team
          </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($editMode): ?>
      <!-- ─── EDIT MODE FORM ─── -->
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="save_team" value="1">

        <!-- Logo -->
        <div class="mb-3">
          <label class="form-label">Team Logo</label>
          <input type="file" name="logo" class="form-control">
        </div>

        <!-- Description -->
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($team['description']) ?></textarea>
        </div>

        <!-- Ownership transfer -->
        <div class="mb-3">
          <label class="form-label">Team Owner</label>
          <?php foreach ($members as $m): ?>
            <div class="form-check">
              <input class="form-check-input"
                type="radio"
                name="owner_id"
                id="owner_<?= $m['id'] ?>"
                value="<?= $m['id'] ?>"
                <?= $m['id'] == $team['creator_id'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="owner_<?= $m['id'] ?>">
                <?= htmlspecialchars($m['display_name']) ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Roles per member -->
        <h5>Member Roles</h5>
        <?php foreach ($members as $m): ?>
          <div class="mb-3">
            <strong><?= htmlspecialchars($m['display_name']) ?></strong><br>
            <?php foreach ($allRoles as $role): ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input"
                  type="checkbox"
                  name="roles[<?= $m['id'] ?>][]"
                  id="role_<?= $m['id'] ?>_<?= md5($role) ?>"
                  value="<?= htmlspecialchars($role) ?>"
                  <?= in_array($role, $memberRoles[$m['id']] ?? []) ? 'checked' : '' ?>>
                <label class="form-check-label"
                  for="role_<?= $m['id'] ?>_<?= md5($role) ?>">
                  <?= htmlspecialchars($role) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="view_team.php?id=<?= $teamId ?>" class="btn btn-secondary-team ms-2">Cancel</a>
      </form>

    <?php else: ?>
      <!-- ─── VIEW MODE ─── -->
      <div class="row mb-4">
        <div class="col-md-8">
          <?php if ($team['description']): ?>
            <p><?= nl2br(htmlspecialchars($team['description'])) ?></p>
          <?php endif; ?>
        </div>
        <div class="col-md-4 text-center">
          <?php if ($team['logo_url']): ?>
            <img src="<?= htmlspecialchars($team['logo_url']) ?>"
              class="img-thumbnail"
              style="max-width:200px">
          <?php endif; ?>
        </div>
      </div>

      <h3 class="h5 mb-3">Members &amp; Roles</h3>
      <div class="row g-3">
        <?php foreach ($members as $m):
          $isOwnerBadge = $m['id'] == $team['creator_id'];
          $rList = $memberRoles[$m['id']] ?? [];
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 text-center">
              <div class="card-body d-flex flex-column align-items-center">
                <img src="<?= htmlspecialchars($m['avatar']) ?>"
                  class="rounded-circle mb-2"
                  style="width:72px;height:72px;object-fit:cover">
                <h6 class="card-title mb-1"><?= htmlspecialchars($m['display_name']) ?>
                  <?php if ($isOwnerBadge): ?>
                    <span class="badge bg-success">Owner</span>
                  <?php endif; ?>
                </h6>
                <?php if ($rList): ?>
                  <p class="small text-muted mb-0">
                    <?= htmlspecialchars(implode(', ', $rList)) ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
<div id="chat-box" style="max-height:400px; overflow-y:auto; padding:1rem; margin-bottom:1rem; margin-top:2rem;"></div>
  <div class="input-group mb-4">
    <input id="chat-input" type="text" class="form-control" placeholder="Type your message…" />
    <button id="chat-send" class="btn btn-primary">Send</button>
  </div>
  <a href="mentors.php" class="btn btn-secondary mb-4">Find a Mentor</a>
  </div>


  <!-- fetch & render loop + send handler -->
  <script>
    (function() {
      const chatBox = document.getElementById('chat-box');
      const input = document.getElementById('chat-input');
      const sendBtn = document.getElementById('chat-send');
      let lastId = 0;

      async function fetchLoop() {
        try {
          const res = await fetch(`team_chat_fetch.php?team_id=<?= $teamId ?>&since=${lastId}`);
          const msgs = await res.json();
          msgs.forEach(msg => {
            // build message bubble
            const row = document.createElement('div');
            row.className = 'd-flex align-items-start mb-2';
            row.innerHTML = `
            <img src="${msg.avatar}" alt="${msg.username}" 
                 class="rounded-circle me-2" 
                 style="width:32px;height:32px;object-fit:cover;">
            <div>
              <div class="small text-muted mb-1">
                <strong>${msg.username}</strong> • ${msg.created_at}
              </div>
              <div>${msg.body}</div>
            </div>
          `;
            chatBox.appendChild(row);
            lastId = msg.id;
          });
          if (msgs.length) {
            chatBox.scrollTop = chatBox.scrollHeight;
          }
        } catch (e) {
          console.error('chat fetch error', e);
        }
      }

      // send a new message
      sendBtn.addEventListener('click', async () => {
        const body = input.value.trim();
        if (!body) return;
        try {
          await fetch('team_chat_send.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              team_id: <?= $teamId ?>,
              body
            })
          });
          input.value = '';
          fetchLoop();
        } catch (e) {
          console.error('chat send error', e);
        }
      });

      // kick off
      fetchLoop();
      setInterval(fetchLoop, 1000);
    })();
  </script>
  
</body>

</html>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>