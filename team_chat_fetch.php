<?php
// team_chat_fetch.php
require __DIR__.'/config.php';
//session_start();

// 1) auth & membership check
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  exit('login required');
}
$teamId = (int)($_GET['team_id']  ?? 0);
$since  = (int)($_GET['since']    ?? 0);

$stmt = $mysqli->prepare("
  SELECT 1
    FROM team_members
   WHERE team_id = ? AND user_id = ?
");
$stmt->bind_param('ii', $teamId, $_SESSION['user_id']);
$stmt->execute();
if (! $stmt->get_result()->fetch_row()) {
  http_response_code(403);
  exit('not a member');
}
$stmt->close();

// 2) fetch new messages
$stmt = $mysqli->prepare("
  SELECT
    m.id,
    m.body,
    DATE_FORMAT(m.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
    COALESCE(up.display_name, u.username) AS username,
    COALESCE(u.avatar, 'img/default-avatar.png') AS avatar
  FROM team_messages AS m
  JOIN users            AS u  ON u.id = m.sender_id
  LEFT JOIN user_profile AS up ON up.user_id  = u.id
  WHERE m.team_id = ?
    AND m.id      > ?
  ORDER BY m.id ASC
");
$stmt->bind_param('ii', $teamId, $since);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
  $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
