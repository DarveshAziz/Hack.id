<?php
/*  lib/messages.php  – chat helper functions  */
function getConversationId(mysqli $db, int $a, int $b): int {
    $u1 = min($a,$b);
    $u2 = max($a,$b);
    $stmt = $db->prepare(
        "INSERT IGNORE INTO conversations (user1_id,user2_id) VALUES (?,?)"
    );
    $stmt->bind_param('ii', $u1, $u2);
    $stmt->execute();
    return (int)$db->query(
        "SELECT id FROM conversations WHERE user1_id=$u1 AND user2_id=$u2"
    )->fetch_column();
}

function addMessage(mysqli $db, int $cid, int $from, string $text): int {
    $stmt = $db->prepare(
        "INSERT INTO messages (conversation_id,sender_id,body)
         VALUES (?,?,?)"
    );
    $stmt->bind_param('iis', $cid, $from, $text);
    $stmt->execute();
    return $db->insert_id;          // 🔙 give back the autoincrement id
}

function fetchMessages(mysqli $db, int $cid, int $after=0): array {
    $afterSql = $after ? "AND id > $after" : '';
    return $db->query(
        "SELECT id,sender_id,body,sent_at
           FROM messages
          WHERE conversation_id=$cid $afterSql
          ORDER BY id"
    )->fetch_all(MYSQLI_ASSOC);
}
?>