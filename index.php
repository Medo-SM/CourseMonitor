<?php
require_once __DIR__ . '/includes/auth_functions.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header("Location: /CourseMonitor/modules/admin/dashboard.php");
            break;
        case 'head':
            header("Location: /CourseMonitor/modules/head/dashboard.php");
            break;
        case 'lecturer':
            header("Location: /CourseMonitor/modules/lecturer/dashboard.php");
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff 0%, #6c757d 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .landing-card {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .btn-lg { padding: 10px 40px; font-size: 1.2rem; }
    </style>
</head>
<body>
    <div class="landing-card">
        <h1 class="mb-4">CourseMonitor</h1>
        <p class="lead mb-5">Academic Performance & Attendance Management System</p>
        
        <div class="d-grid gap-3">
            <a href="/CourseMonitor/public/login.php" class="btn btn-primary btn-lg">Login</a>
            <a href="/CourseMonitor/public/signup.php" class="btn btn-outline-secondary">Sign Up</a>
        </div>
        
        <p class="mt-4 text-muted small">&copy; <?php echo date('Y'); ?> CourseMonitor System</p>
    </div>
</body>
</html>