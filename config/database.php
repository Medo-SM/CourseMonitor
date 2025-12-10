<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendance_system";

$res = new mysqli($host, $user, $pass, $db);

if ($res->connect_error) {
    die("Connection failed: " . $res->connect_error);
}

$res->set_charset("utf8mb4");
