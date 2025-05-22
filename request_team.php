<?php
// request_team.php

include 'config.php';
// only start a session if one isn't already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

// 1️⃣ Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Login required');
}
$from = (int)$_SESSION['user_id'];

// 2️⃣ Grab team_id from POST _or_ GET
$team = (int)( $_POST['team_id'] ?? $_GET['team_id'] ?? 0 );
if (!$team) {
    http_response_code(400);
    exit('Missing team_id');
}

// 3️⃣ Look up that team’s creator
$stmt = $mysqli->prepare("
    SELECT creator_id 
      FROM teams 
     WHERE id = ?
     LIMIT 1
");
$stmt->bind_param('i', $team);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    http_response_code(404);
    exit('Team not found');
}
$owner = (int)$row['creator_id'];

// 4️⃣ You can’t request your own team
if ($owner === $from) {
    http_response_code(400);
    exit('Cannot request your own team');
}

// 5️⃣ Insert (or ignore if already exists)
$stmt = $mysqli->prepare("
    INSERT IGNORE INTO team_join_requests (team_id, from_user_id)
    VALUES (?, ?)
");
$stmt->bind_param('ii', $team, $from);
$stmt->execute();

// 6️⃣ Redirect back with a flag
header("Location: profile_public.php?id={$owner}&success=requested");
exit;
