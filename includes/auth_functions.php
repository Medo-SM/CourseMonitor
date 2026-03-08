<?php
session_start();

require_once __DIR__ . '/../config/db.php';

function login($username, $password) {
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ? AND is_active = 1");
    if (!$stmt) {
        // Log error
        error_log("Login prepare failed: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['last_activity'] = time();

            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        // Check for timeout (e.g., 30 minutes)
        if (time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
            logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

function requireRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: /CourseMonitor/public/login.php");
        exit;
    }

    if (!in_array($_SESSION['role'], (array)$allowed_roles)) {
        header("Location: /CourseMonitor/public/access_denied.php");
        exit;
    }
}

function getCurrentUser() {
    return isset($_SESSION['user_id']) ? $_SESSION : null;
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>