<?php
function fetchTeamMessages(mysqli $db, int $teamId, int $sinceId = 0): array {
  $sinceSql = $sinceId ? "AND m.id > $sinceId" : "";
  $sql = "
    SELECT m.id, m.sender_id, m.body, m.created_at,
           u.username, COALESCE(up.avatar,'img/default-avatar.png') AS avatar
      FROM team_messages m
      JOIN users u       ON u.id = m.sender_id
      LEFT JOIN user_profile up ON up.user_id = u.id
     WHERE m.team_id = ?
       $sinceSql
     ORDER BY m.id
  ";
  $stmt = $db->prepare($sql);
  $stmt->bind_param('i', $teamId);
  $stmt->execute();
  return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addTeamMessage(mysqli $db, int $teamId, int $senderId, string $body): void {
  $stmt = $db->prepare("
    INSERT INTO team_messages (team_id, sender_id, body)
    VALUES (?,?,?)
  ");
  $stmt->bind_param('iis', $teamId, $senderId, $body);
  $stmt->execute();
}
