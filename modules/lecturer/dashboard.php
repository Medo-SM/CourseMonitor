<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Dashboard - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <header class="navbar navbar-dark bg-dark px-3">
        <span class="navbar-brand mb-0 h1">Lecturer Dashboard</span>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="/CourseMonitor/public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </header>
    <main class="container py-4">
        <h2 class="mb-4">My Courses</h2>
        <div id="courseContainer" class="row g-4">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', loadCourses);

        function loadCourses() {
            fetch('/CourseMonitor/api/get_courses.php')
                .then(r => r.json())
                .then(courses => {
                    const container = document.getElementById('courseContainer');
                    if (courses.length === 0) {
                        container.innerHTML = '<div class="col-12"><div class="alert alert-info">No courses assigned to you yet.</div></div>';
                        return;
                    }

                    container.innerHTML = '';
                    courses.forEach(c => {
                        const card = `
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">${c.code} - ${c.name}</h5>
                                        <h6 class="card-subtitle mb-2 text-muted">Credits: ${c.credit_hours}</h6>
                                        <p class="card-text">Manage grades and attendance for this course.</p>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 d-grid gap-2">
                                        <a href="/CourseMonitor/modules/lecturer/attendance.php?course_id=${c.id}" class="btn btn-outline-primary btn-sm">Take Attendance</a>
                                        <a href="/CourseMonitor/modules/lecturer/coursework.php?course_id=${c.id}" class="btn btn-outline-success btn-sm">Enter Grades</a>
                                        <div class="btn-group w-100 mt-1">
                                            <a href="/CourseMonitor/modules/lecturer/view_attendance.php?course_id=${c.id}" class="btn btn-outline-primary btn-sm">View Attendance</a>
                                            <a href="/CourseMonitor/modules/lecturer/view_grades.php?course_id=${c.id}" class="btn btn-outline-info btn-sm">View Grades</a>
                                        </div>
                                        <a href="/CourseMonitor/modules/lecturer/import_excel.php?course_id=${c.id}" class="btn btn-outline-secondary btn-sm mt-1">Import Excel</a>
                                    </div>
                                </div>
                            </div>
                        `;
                        container.innerHTML += card;
                    });
                });
        }
    </script>
</body>
</html>