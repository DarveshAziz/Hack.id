<?php
// inbox.php
include 'config.php';
session_start();

// 1️⃣ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$me = (int)$_SESSION['user_id'];

// 2️⃣ Fetch pending *invitations to me*
$invStmt = $mysqli->prepare("
  SELECT 
    ti.id,
    t.id   AS team_id,
    t.name AS team_name,
    u.username  AS from_user,
    ti.created_at
  FROM team_invitations ti
  JOIN teams  t ON ti.team_id      = t.id
  JOIN users  u ON ti.from_user_id = u.id
  WHERE ti.to_user_id = ?
    AND ti.status     = 'pending'
  ORDER BY ti.created_at DESC
");
$invStmt->bind_param('i', $me);
$invStmt->execute();
$inboxInvites = $invStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3️⃣ Fetch pending *join-requests* on my teams
$reqStmt = $mysqli->prepare("
  SELECT 
    r.id,
    t.id   AS team_id,
    t.name AS team_name,
    u.username  AS from_user,
    r.created_at
  FROM team_join_requests r
  JOIN teams  t ON r.team_id      = t.id
  JOIN users  u ON r.from_user_id = u.id
  WHERE t.creator_id = ?
    AND r.status     = 'pending'
  ORDER BY r.created_at DESC
");
$reqStmt->bind_param('i', $me);
$reqStmt->execute();
$inboxRequests = $reqStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Inbox • Hack.id</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <?php include 'includes/header.php'; ?>

  <div class="container py-5">
    <h2>Inbox</h2>

    <h4 class="mt-4">Team Invitations</h4>
    <?php if (count($inboxInvites)): ?>
      <ul class="list-group">
        <?php foreach ($inboxInvites as $inv): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($inv['from_user']) ?></strong>
              invited you to join
              <em><?= htmlspecialchars($inv['team_name']) ?></em><br>
              <small class="text-muted"><?= $inv['created_at'] ?></small>
            </div>
            <div>
              <form action="invite_response.php" method="post" class="d-inline">
                <input type="hidden" name="invite_id" value="<?= $inv['id'] ?>">
                <button name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
              </form>
              <form action="invite_response.php" method="post" class="d-inline">
                <input type="hidden" name="invite_id" value="<?= $inv['id'] ?>">
                <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">No pending invitations.</p>
    <?php endif; ?>

    <h4 class="mt-4">Team Join Requests</h4>
    <?php if (count($inboxRequests)): ?>
      <ul class="list-group">
        <?php foreach ($inboxRequests as $r): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($r['from_user']) ?></strong>
              requested to join
              <em><?= htmlspecialchars($r['team_name']) ?></em><br>
              <small class="text-muted"><?= $r['created_at'] ?></small>
            </div>
            <div>
              <form action="request_response.php" method="post" class="d-inline">
                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                <button name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
              </form>
              <form action="request_response.php" method="post" class="d-inline">
                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">No pending join-requests.</p>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
