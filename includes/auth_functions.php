<?php
// includes/auth_functions.php

// Configure hardened session cookie settings before session_start
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Dynamically resolves application-relative paths to absolute web root URLs.
 * Eliminates hardcoded subdirectory assumptions (e.g., /CourseMonitor/).
 */
function base_url($path = '') {
    if (defined('BASE_URL')) {
        $base = rtrim(BASE_URL, '/');
    } else {
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
        
        $base = '';
        if (!empty($docRoot) && str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        } else {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $parts = array_values(array_filter(explode('/', str_replace('\\', '/', $scriptDir))));
            
            // Pop recognized subdirectories to find project root
            while (!empty($parts)) {
                $last = end($parts);
                if (in_array($last, ['modules', 'api', 'public', 'includes', 'config', 'tests', 'admin', 'head', 'lecturer'])) {
                    array_pop($parts);
                } else {
                    break;
                }
            }
            $base = empty($parts) ? '' : '/' . implode('/', $parts);
        }
    }

    $base = rtrim($base, '/');
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $base !== '' ? $base : '/';
    }
    return ($base !== '' ? $base : '') . '/' . $cleanPath;
}

/**
 * Detects whether the current HTTP request is targeted at an API endpoint.
 */
function isApiRequest() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return str_contains($script, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($accept, 'application/json'));
}

/**
 * Standardized JSON success response envelope.
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!defined('TEST_MODE') || !TEST_MODE) {
        exit;
    }
}

/**
 * Standardized JSON error response envelope.
 */
function json_error($message, $statusCode = 400) {
    json_response(['error' => $message, 'status' => $statusCode], $statusCode);
}

function login($username, $password) {
    global $mysqli;

    if (!$mysqli || $mysqli->connect_error) {
        return false;
    }

    $stmt = $mysqli->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ? AND is_active = 1");
    if (!$stmt) {
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

            // Refresh CSRF token on login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        // Session timeout (30 minutes)
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
    $_SESSION = [];
    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params["path"] ?: '/',
            $params["domain"] ?: '',
            $params["secure"] ?? false,
            $params["httponly"] ?? true
        );
        // Explicit root path expiration
        setcookie(session_name(), '', time() - 3600, '/');
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function requireRole($allowed_roles) {
    $allowed = (array)$allowed_roles;
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            json_error('Unauthorized: Authentication required', 401);
            return;
        }
        header("Location: " . base_url('/public/login.php'));
        if (!defined('TEST_MODE') || !TEST_MODE) {
            exit;
        }
        return;
    }

    if (!in_array($_SESSION['role'] ?? '', $allowed, true)) {
        if (isApiRequest()) {
            json_error('Forbidden: Access denied', 403);
            return;
        }
        header("Location: " . base_url('/public/access_denied.php'));
        if (!defined('TEST_MODE') || !TEST_MODE) {
            exit;
        }
        return;
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
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
?>
