<?php
require 'config.php';
require 'lib/messages.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$other = (int)($_GET['uid'] ?? 0);
if (!$other || $other == $_SESSION['user_id']) { header('Location: index.php'); exit; }

$cid = getConversationId($mysqli, $_SESSION['user_id'], $other);
header("Location: messages.php?cid=$cid");
?>