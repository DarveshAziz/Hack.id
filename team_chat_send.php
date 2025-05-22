<?php
// team_chat_send.php
include 'config.php';

// 1) Make sure session is started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Read JSON body if present
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// If we got a JSON object, merge into $_POST
if (is_array($data)) {
    $_POST = $data + $_POST;
}

// 3) Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error'=>'login required']);
    exit;
}

// 4) Sanity check parameters
$teamId = (int)($_POST['team_id'] ?? 0);
$body   = trim((string)($_POST['body'] ?? ''));

if ($teamId <= 0 || $body === '') {
    http_response_code(400);
    echo json_encode(['error'=>'missing team_id or body']);
    exit;
}

// 5) Insert the message
try {
    $stmt = $mysqli->prepare("
        INSERT INTO team_messages (team_id, sender_id, body)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iis', $teamId, $_SESSION['user_id'], $body);
    $stmt->execute();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'db error']);
    exit;
}

// 6) Success
header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
