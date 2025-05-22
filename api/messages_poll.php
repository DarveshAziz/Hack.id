<?php
// api/messages_poll.php
error_reporting(E_ERROR);
ini_set('display_errors', 0);

require '../config.php';
require '../lib/messages.php';

// assume config.php started the session already

header('Content-Type: application/json');

$cid   = (int)($_GET['cid']   ?? 0);
$after = (int)($_GET['after'] ?? 0);

$out = fetchMessages($mysqli, $cid, $after);
echo json_encode($out);
