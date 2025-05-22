<?php
// invite_response.php
include 'config.php';
session_start();

// 1) Must be logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "Please log in first.";
    header('Location: login.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$inviteId = (int) ($_POST['invite_id'] ?? 0);
$action   = $_POST['action'] ?? '';

// 2) Validate inputs
if (!$inviteId || !in_array($action, ['accept','reject'], true)) {
    $_SESSION['flash'] = "Invalid invitation action.";
    header('Location: inbox.php');
    exit;
}

// 3) Load the pending invitation & ensure it's for me
$stmt = $mysqli->prepare("
    SELECT team_id
      FROM team_invitations
     WHERE id = ?
       AND to_user_id = ?
       AND status = 'pending'
");
$stmt->bind_param('ii', $inviteId, $userId);
$stmt->execute();
$stmt->bind_result($teamId);
if (!$stmt->fetch()) {
    $_SESSION['flash'] = "Invitation not found or already handled.";
    $stmt->close();
    header('Location: inbox.php');
    exit;
}
$stmt->close();

// 4) Mark invitation accepted/rejected
$stmt = $mysqli->prepare("
    UPDATE team_invitations
       SET status = ?
     WHERE id = ?
");
$stmt->bind_param('si', $action, $inviteId);
$stmt->execute();
$stmt->close();

// 5) If accepted, add to team_members (avoid duplicates)
if ($action === 'accept') {
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) FROM team_members
         WHERE team_id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $teamId, $userId);
    $stmt->execute();
    $stmt->bind_result($c);
    $stmt->fetch();
    $stmt->close();

    if ($c === 0) {
        $stmt = $mysqli->prepare("
            INSERT INTO team_members (team_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param('ii', $teamId, $userId);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['flash'] = "You’ve joined the team!";
} else {
    $_SESSION['flash'] = "Invitation declined.";
}

header('Location: inbox.php');
exit;
