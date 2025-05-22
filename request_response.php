<?php
// request_response.php
include 'config.php';
session_start();

// 1) Must be logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "Please log in first.";
    header('Location: login.php');
    exit;
}

$userId    = (int) $_SESSION['user_id'];
$requestId = (int) ($_POST['request_id'] ?? 0);
$action    = $_POST['action'] ?? '';

// 2) Validate inputs
if (!$requestId || !in_array($action, ['accept','reject'], true)) {
    $_SESSION['flash'] = "Invalid request action.";
    header('Location: inbox.php');
    exit;
}

// 3) Load the pending join-request & ensure *I* am the team owner
$stmt = $mysqli->prepare("
    SELECT r.team_id, r.from_user_id
      FROM team_join_requests r
      JOIN teams t ON r.team_id = t.id
     WHERE r.id = ?
       AND t.creator_id = ?
       AND r.status = 'pending'
");
$stmt->bind_param('ii', $requestId, $userId);
$stmt->execute();
$stmt->bind_result($teamId, $fromUserId);
if (!$stmt->fetch()) {
    $_SESSION['flash'] = "Join request not found or already handled.";
    $stmt->close();
    header('Location: inbox.php');
    exit;
}
$stmt->close();

// 4) Mark request accepted/rejected
$stmt = $mysqli->prepare("
    UPDATE team_join_requests
       SET status = ?
     WHERE id = ?
");
$stmt->bind_param('si', $action, $requestId);
$stmt->execute();
$stmt->close();

// 5) If accepted, add the requester to team_members (no dupes)
if ($action === 'accept') {
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) FROM team_members
         WHERE team_id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $teamId, $fromUserId);
    $stmt->execute();
    $stmt->bind_result($c);
    $stmt->fetch();
    $stmt->close();

    if ($c === 0) {
        $stmt = $mysqli->prepare("
            INSERT INTO team_members (team_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param('ii', $teamId, $fromUserId);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['flash'] = "User has been added to your team.";
} else {
    $_SESSION['flash'] = "Join request rejected.";
}

header('Location: inbox.php');
exit;
