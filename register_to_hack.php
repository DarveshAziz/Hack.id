<?php
include 'config.php';
include 'header.php';
?>
<!DOCTYPE html>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    
/>
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
