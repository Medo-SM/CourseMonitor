<?php
/**
 * CourseMonitor Shared Navigation Partial (includes/navbar.php)
 * Student Attendance & Grades Made Simple
 * 
 * Role-aware top navigation bar with intuitive menu labels, active states,
 * clean user pill, and accessible landmarks.
 */

if (defined('CM_NAVBAR_RENDERED')) {
    return;
}
define('CM_NAVBAR_RENDERED', true);

if (!function_exists('base_url')) {
    require_once __DIR__ . '/auth_functions.php';
}

$isAuth = function_exists('isLoggedIn') ? isLoggedIn() : false;
$user = $isAuth && function_exists('getCurrentUser') ? getCurrentUser() : null;
$role = $user['role'] ?? null;
$userName = $user['full_name'] ?? $user['username'] ?? 'User';
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

// Determine Home/Dashboard URL based on role
$brandUrl = base_url('/');
if ($role === 'admin') {
    $brandUrl = base_url('/modules/admin/dashboard.php');
} elseif ($role === 'head') {
    $brandUrl = base_url('/modules/head/dashboard.php');
} elseif ($role === 'lecturer') {
    $brandUrl = base_url('/modules/lecturer/dashboard.php');
}

// Role display labels and badge styles (intuitive, human wording)
$roleConfig = [
    'admin' => ['label' => 'Administrator', 'badge' => 'cm-badge-primary'],
    'head' => ['label' => 'Department Head', 'badge' => 'cm-badge-info'],
    'lecturer' => ['label' => 'Teacher', 'badge' => 'cm-badge-success']
];
$currentRoleConfig = $roleConfig[$role] ?? ['label' => 'Guest', 'badge' => 'cm-badge-neutral'];

// Menu items per role (short, intuitive, natural phrasing)
$navLinks = [];
if ($role === 'admin') {
    $navLinks = [
        ['label' => 'Dashboard', 'url' => base_url('/modules/admin/dashboard.php'), 'icon' => 'bi-speedometer2', 'match' => 'admin/dashboard.php'],
        ['label' => 'Users', 'url' => base_url('/modules/admin/users.php'), 'icon' => 'bi-people', 'match' => 'admin/users.php'],
        ['label' => 'Students', 'url' => base_url('/modules/admin/students.php'), 'icon' => 'bi-mortarboard', 'match' => 'admin/students.php'],
        ['label' => 'Courses', 'url' => base_url('/modules/admin/courses.php'), 'icon' => 'bi-book', 'match' => 'admin/courses.php'],
        ['label' => 'Departments', 'url' => base_url('/modules/admin/departments.php'), 'icon' => 'bi-building', 'match' => 'admin/departments.php'],
        ['label' => 'Faculties', 'url' => base_url('/modules/admin/faculties.php'), 'icon' => 'bi-diagram-3', 'match' => 'admin/faculties.php'],
    ];
} elseif ($role === 'head') {
    $navLinks = [
        ['label' => 'Dashboard', 'url' => base_url('/modules/head/dashboard.php'), 'icon' => 'bi-speedometer2', 'match' => 'head/dashboard.php'],
        ['label' => 'Courses', 'url' => base_url('/modules/head/courses.php'), 'icon' => 'bi-book', 'match' => 'head/courses.php'],
        ['label' => 'Review Records', 'url' => base_url('/modules/head/modify_records.php'), 'icon' => 'bi-pencil-square', 'match' => 'head/modify_records.php'],
        ['label' => 'Activity Log', 'url' => base_url('/modules/head/audit_logs.php'), 'icon' => 'bi-clock-history', 'match' => 'head/audit_logs.php'],
        ['label' => 'Reports', 'url' => base_url('/modules/head/reports.php'), 'icon' => 'bi-file-earmark-bar-graph', 'match' => 'head/reports.php'],
    ];
} elseif ($role === 'lecturer') {
    $navLinks = [
        ['label' => 'My Classes', 'url' => base_url('/modules/lecturer/dashboard.php'), 'icon' => 'bi-grid', 'match' => 'lecturer/dashboard.php'],
        ['label' => 'Take Attendance', 'url' => base_url('/modules/lecturer/attendance.php'), 'icon' => 'bi-calendar-check', 'match' => ['lecturer/attendance.php', 'lecturer/view_attendance.php']],
        ['label' => 'Enter Grades', 'url' => base_url('/modules/lecturer/coursework.php'), 'icon' => 'bi-pencil-square', 'match' => 'lecturer/coursework.php'],
        ['label' => 'Gradebook', 'url' => base_url('/modules/lecturer/view_grades.php'), 'icon' => 'bi-table', 'match' => 'lecturer/view_grades.php'],
        ['label' => 'Import Marks', 'url' => base_url('/modules/lecturer/import_excel.php'), 'icon' => 'bi-file-earmark-arrow-up', 'match' => 'lecturer/import_excel.php'],
    ];
} else {
    // Guest Navigation
    $navLinks = [
        ['label' => 'Home', 'url' => base_url('/'), 'icon' => 'bi-house', 'match' => 'index.php'],
        ['label' => 'Login', 'url' => base_url('/public/login.php'), 'icon' => 'bi-box-arrow-in-right', 'match' => 'public/login.php'],
        ['label' => 'Sign Up', 'url' => base_url('/public/signup.php'), 'icon' => 'bi-person-plus', 'match' => 'public/signup.php'],
    ];
}

// Function to check if link is active
if (!function_exists('cm_is_nav_active')) {
    function cm_is_nav_active($matchTarget, $currentScript) {
        if (empty($matchTarget)) return false;
        $current = str_replace('\\', '/', $currentScript);
        if (is_array($matchTarget)) {
            foreach ($matchTarget as $target) {
                $target = str_replace('\\', '/', $target);
                if (str_contains($current, $target)) return true;
            }
            return false;
        }
        $target = str_replace('\\', '/', $matchTarget);
        return str_contains($current, $target);
    }
}

// User initial for clean avatar circle
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<header role="banner">
    <nav class="navbar navbar-expand-lg cm-navbar" aria-label="Main Navigation">
        <div class="container-fluid px-3 px-lg-4">
            <!-- Brand -->
            <a class="cm-navbar-brand" href="<?= htmlspecialchars($brandUrl, ENT_QUOTES, 'UTF-8') ?>">
                <span class="cm-brand-mark" aria-hidden="true">
                    <i class="bi bi-mortarboard-fill"></i>
                </span>
                <span>CourseMonitor</span>
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler border-0 text-white p-1" type="button" data-bs-toggle="collapse" data-bs-target="#cmNavbarContent" aria-controls="cmNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-3"></i>
            </button>

            <!-- Collapsible Nav -->
            <div class="collapse navbar-collapse" id="cmNavbarContent">
                <!-- Nav Links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <?php foreach ($navLinks as $item): ?>
                        <?php 
                        $isActive = cm_is_nav_active($item['match'], $currentScript);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link cm-nav-link <?= $isActive ? 'active' : '' ?>" 
                               href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-1" aria-hidden="true"></i>
                                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- User Controls -->
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <!-- Theme Toggle Button -->
                    <button id="themeToggleBtn" type="button" class="btn btn-sm cm-theme-toggle" aria-label="Toggle dark or light theme" title="Toggle between Dark and Light mode">
                        <i class="bi bi-moon-stars-fill theme-icon-dark" aria-hidden="true"></i>
                        <i class="bi bi-sun-fill theme-icon-light" aria-hidden="true"></i>
                        <span class="d-none d-xl-inline ms-1 theme-toggle-text">Theme</span>
                    </button>

                    <?php if ($user): ?>
                        <div class="cm-user-badge d-none d-sm-inline-flex" title="Signed in as <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="cm-user-avatar" aria-hidden="true"><?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="fw-medium text-truncate" style="max-width: 140px;"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge <?= $currentRoleConfig['badge'] ?> py-1 px-2"><?= htmlspecialchars($currentRoleConfig['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/public/logout.php'), ENT_QUOTES, 'UTF-8') ?>" 
                           class="btn btn-outline-danger btn-sm" 
                           role="button"
                           title="Sign out of CourseMonitor">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
