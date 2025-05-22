<?php
// invite_team.php
include 'config.php';
session_start();

// 1️⃣ Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Login required');
}

$from = (int)$_SESSION['user_id'];
$to   = (int)($_POST['to_user_id'] ?? 0);
$team = (int)($_POST['team_id']    ?? 0);

if (!$to || !$team) {
    http_response_code(400);
    exit('Missing data');
}

// 2️⃣ Verify you actually own that team
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM teams WHERE id = ? AND creator_id = ?");
$stmt->bind_param('ii', $team, $from);
$stmt->execute();
if ($stmt->get_result()->fetch_row()[0] === 0) {
    http_response_code(403);
    exit('Not your team');
}

// 3️⃣ Insert the invitation (avoid duplicates)
$stmt = $mysqli->prepare("
    INSERT IGNORE INTO team_invitations
      (team_id, from_user_id, to_user_id)
    VALUES (?,?,?)
");
$stmt->bind_param('iii', $team, $from, $to);
$stmt->execute();

// 4️⃣ Redirect back with a flash (or simple message)
header("Location: profile_public.php?id={$to}&success=invited");
exit;
