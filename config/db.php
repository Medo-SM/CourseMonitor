<?php
// config/db.php
// Centralized database configuration loading from environment variables with safe defaults

$host     = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '123';
$db_name  = getenv('DB_NAME') ?: 'CourseMonitor';
$port     = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();

// Support test mode / mocking where a physical database connection is skipped
if (!defined('CM_SKIP_DB') || !CM_SKIP_DB) {
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $connected = @$mysqli->real_connect($host, $username, $password, $db_name, $port);

    if (!$connected || $mysqli->connect_errno) {
        error_log("Database connection failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
        if (defined('TEST_MODE') && TEST_MODE) {
            // In TEST_MODE, keep $mysqli instance for tests to inspect connect_errno/connect_error without exit
        } else {
            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            die(json_encode(['error' => 'Database connection failed. Please contact the administrator.', 'status' => 500]));
        }
    } else {
        $mysqli->set_charset("utf8mb4");
    }
}
?>
