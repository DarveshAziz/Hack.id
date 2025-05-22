<?php
// 1) bootstrap
include "config.php";
//if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require __DIR__ . "/vendor/autoload.php";

// 2) build client
$client = new Google\Client;
$client->setClientId("844878097440-7fd98ruf2jkfhhalfrb4aut9nda5jhd7.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/Hack.id/oauth2callback.php");
$client->addScope("email");
$client->addScope("profile");

// 3) must have code
if (empty($_GET["code"])) {
    header("Location: index.php");
    exit;
}

// 4) exchange & fetch profile
$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
if (isset($token["error"])) {
    exit("OAuth error: ".$token["error"]);
}
$client->setAccessToken($token["access_token"]);
$oauth    = new Google\Service\Oauth2($client);
$userinfo = $oauth->userinfo->get();

$stmt = $mysqli->prepare("
  SELECT id, username, COALESCE(avatar,'img/default-avatar.png') AS avatar
    FROM users
   WHERE email = ?
");
$stmt->bind_param('s', $userinfo->email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  // first time Google‐login — insert into users
  $username = str_replace(' ', '_', strtolower($userinfo->name));
  $avatar   = $userinfo->picture;  // or download/store locally
  $stmt = $mysqli->prepare("
    INSERT INTO users (username,email,avatar)
    VALUES (?,?,?)
  ");
  $stmt->bind_param('sss', $username, $userinfo->email, $avatar);
  $stmt->execute();
  $userId = $stmt->insert_id;
  $stmt->close();
} else {
  $userId   = $user['id'];
  $username = $user['username'];
  $avatar   = $user['avatar'];
}

// 2) now _set_ your session
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;
$_SESSION['avatar']   = $avatar;

// 3) redirect back to the home page
header('Location: index.php');
exit;