<?php
/*  DB credentials – tweak if you changed the root password   */
$host = 'localhost';
$db   = 'hackid';
$user = 'root';
$pass = '';            // ← leave blank on fresh XAMPP

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('DB connection failed: ' . $mysqli->connect_error);
}

session_start();
?>
