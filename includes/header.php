<?php
/**
 * CourseMonitor Shared Header Partial (includes/header.php)
 * 
 * Usage:
 *   $pageTitle = 'Manage Courses'; // Optional page title
 *   $showNavbar = true;            // Optional, set false on landing or auth if custom nav needed
 *   $extraHead = '<style>...</style>'; // Optional extra head tags
 *   require_once __DIR__ . '/includes/header.php';
 */

if (!function_exists('base_url')) {
    require_once __DIR__ . '/auth_functions.php';
}

$titleStr = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' | CourseMonitor' 
    : 'CourseMonitor | Academic Performance & Attendance Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="CourseMonitor - Academic Performance & Attendance Management System">
    <meta name="theme-color" content="#1e3a8a">
    <title><?= $titleStr ?></title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CourseMonitor High-Craft Design System -->
    <link href="<?= htmlspecialchars(base_url('/public/css/app.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">

    <?php if (!empty($extraHead)): ?>
        <?= $extraHead ?>
    <?php endif; ?>
</head>
<body class="bg-body d-flex flex-column min-vh-100">
    <!-- Accessible Skip to Content Link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php
    // Include the role-aware institutional navbar unless explicitly disabled
    if (!isset($showNavbar) || $showNavbar !== false) {
        require_once __DIR__ . '/navbar.php';
    }
    ?>

    <!-- Main Content Landmark -->
    <main id="main-content" class="flex-grow-1" tabindex="-1">
