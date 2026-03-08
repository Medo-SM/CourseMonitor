<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; background-color: #f8f9fa; }
        header { background: #343a40; color: white; padding: 1rem; }
    </style>
</head>
<body>
    <header class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">IT Administrator Dashboard</h1>
        <nav>
            <a href="/CourseMonitor/public/logout.php" class="text-white text-decoration-none">Logout</a>
        </nav>
    </header>
    <main class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">User Management</h5>
                        <p class="card-text">Manage system users (Lecturers, Heads, Admins), reset passwords.</p>
                        <a href="/CourseMonitor/modules/admin/users.php" class="btn btn-primary">Manage Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Student Management</h5>
                        <p class="card-text">Manage students records and enrollments.</p>
                        <a href="/CourseMonitor/modules/admin/students.php" class="btn btn-primary">Manage Students</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Course Management</h5>
                        <p class="card-text">Create courses and assign Lecturers.</p>
                        <a href="/CourseMonitor/modules/admin/courses.php" class="btn btn-primary">Manage Courses</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Content Management</h5>
                        <p class="card-text">Manage announcements and website content.</p>
                        <a href="#" class="btn btn-secondary disabled">Manage Content (Coming Soon)</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Department Management</h5>
                        <p class="card-text">Add/Remove Departments and assign Heads.</p>
                        <a href="/CourseMonitor/modules/admin/departments.php" class="btn btn-primary">Manage Departments</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Faculty Management</h5>
                        <p class="card-text">Manage Faculties (for Lecturers/Depts).</p>
                        <a href="/CourseMonitor/modules/admin/faculties.php" class="btn btn-primary">Manage Faculties</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>