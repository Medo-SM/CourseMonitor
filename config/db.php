<?php
// config/db.php

$host = 'localhost';
$db_name = 'CourseMonitor';
$username = 'root'; // Change this to your database username
$password = '123';     // Change this to your database password

$mysqli = new mysqli($host, $username, $password, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
