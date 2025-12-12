<?php
require __DIR__ . '/../config/database.php';

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'courses':
        require __DIR__ . '/../app/Controllers/CourseController.php';
        (new CourseController($res))->index();
        exit;

    default:
        echo "<h1>Attendance System</h1>";
        echo "<a href='?page=courses'>My Courses</a>";
        exit;
}
