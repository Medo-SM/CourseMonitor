<?php
require_once __DIR__ . '/../includes/auth_functions.php';
logout();
header("Location: /CourseMonitor/public/login.php");
exit;
?>
