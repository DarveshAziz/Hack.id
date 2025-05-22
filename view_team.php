<?php
// view_team.php — main page for a single team (with Edit mode)
include 'config.php';
session_start();

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
        $dest = 'uploads/team_'.$teamId.'_'.basename($_FILES['logo']['name']);
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
  <title><?= htmlspecialchars($team['name']) ?> • <?= htmlspecialchars($team['hack_title']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css"        rel="stylesheet">
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
                   <?= $m['id']==$team['creator_id']?'checked':'' ?>>
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
      <a href="view_team.php?id=<?= $teamId ?>" class="btn btn-secondary ms-2">Cancel</a>
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
        $isOwnerBadge = $m['id']==$team['creator_id'];
        $rList = $memberRoles[$m['id']] ?? [];
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="card h-100 text-center">
            <div class="card-body d-flex flex-column align-items-center">
              <img src="<?= htmlspecialchars($m['avatar']) ?>"
                   class="rounded-circle mb-2"
                   style="width:72px;height:72px;object-fit:cover">
              <h6 class="card-title mb-1"><?= htmlspecialchars($m['display_name']) ?>
                <?php if($isOwnerBadge): ?>
                  <span class="badge bg-success">Owner</span>
                <?php endif; ?>
              </h6>
              <?php if($rList): ?>
                <p class="small text-muted mb-0">
                  <?= htmlspecialchars(implode(', ',$rList)) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>
<div id="chat-box" style="max-height:400px; overflow-y:auto; border:1px solid #ddd; padding:1rem; margin-bottom:1rem;"></div>
  <div class="input-group mb-4">
    <input id="chat-input" type="text" class="form-control" placeholder="Type your message…" />
    <button id="chat-send" class="btn btn-primary">Send</button>
  </div>

  <!-- fetch & render loop + send handler -->
  <script>
  (function(){
    const chatBox = document.getElementById('chat-box');
    const input   = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    let lastId = 0;

    async function fetchLoop(){
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
      } catch(e){
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
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ team_id: <?= $teamId ?>, body })
        });
        input.value = '';
        fetchLoop();
      } catch(e){
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
