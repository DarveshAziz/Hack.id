<?php
include 'config.php';
session_start();
if (!$_SESSION['user_id'] || !$_POST['hackathon_id']) {
  http_response_code(400); exit;
}
$userId = (int)$_SESSION['user_id'];
$hid    = (int)$_POST['hackathon_id'];
$stmt = $mysqli->prepare(
  "INSERT IGNORE INTO registrations (user_id,hackathon_id) VALUES (?,?)"
);
$stmt->bind_param('ii',$userId,$hid);
$stmt->execute();
$res = $mysqli->query("SELECT * FROM hackathons ORDER BY id DESC");
$h = $res->fetch_assoc();
$themes = json_decode($h['themes'], true) ?: [];
if (preg_match('~https?://([^.]+)\.devpost\.com~i', $h['link'], $m)) {
  $slug = $m[1];
} else {
  $slug = '';
}
header("Location: hackathon_view.php?slug=". $mysqli->real_escape_string($slug) ."&tab=overview");
