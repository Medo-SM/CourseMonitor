<?php
// config/db.php
// Centralized, environment-aware database configuration

$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'CourseMonitor';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '123';
$port = (int)(getenv('DB_PORT') ?: 3306);

// Support test mode / mocking where a physical database connection is skipped
if (!defined('CM_SKIP_DB') || !CM_SKIP_DB) {
    $mysqli = @new mysqli($host, $username, $password, $db_name, $port);

    if ($mysqli->connect_error) {
        error_log("Database connection failed: " . $mysqli->connect_error);
        if (defined('TEST_MODE') && TEST_MODE) {
            // Keep $mysqli instance for tests to inspect connect_error
        } else {
            http_response_code(500);
            die("Database connection error. Please contact the administrator.");
        }
    } else {
        $mysqli->set_charset("utf8mb4");
    }
}
?>
