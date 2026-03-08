<?php
require_once __DIR__ . '/../includes/auth_functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (login($username, $password)) {
            // Redirect based on role
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: /CourseMonitor/modules/admin/dashboard.php");
                    break;
                case 'lecturer':
                    header("Location: /CourseMonitor/modules/lecturer/dashboard.php");
                    break;
                case 'head':
                    header("Location: /CourseMonitor/modules/head/dashboard.php");
                    break;
                default:
                    header("Location: /CourseMonitor/index.php"); // Fallback
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { height: 100vh; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="card login-card shadow-sm">
        <div class="card-body p-4">
            <h2 class="text-center mb-4">CourseMonitor</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="/CourseMonitor/public/signup.php">Don't have an account? Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>