<?php
// api/message_send.php
error_reporting(E_ERROR);
ini_set('display_errors', 0);

require '../config.php';
require '../lib/messages.php';

// only start if not already
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'login required']);
    exit;
}

// note we’re matching your JS keys here:
$cid  = (int)($_POST['cid']  ?? 0);
$text = trim($_POST['body'] ?? '');

if (!$cid || $text === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing data']);
    exit;
}

addMessage($mysqli, $cid, $_SESSION['user_id'], $text);

// grab the last insert id
$newId = $mysqli->insert_id;

echo json_encode(['ok'=>true,'id'=>$newId]);
