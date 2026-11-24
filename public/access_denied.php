<?php
require_once __DIR__ . '/../includes/auth_functions.php';

http_response_code(403);

$pageTitle = '403 Forbidden - Access Restricted';
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$isAuth = isLoggedIn();
$user = $isAuth ? getCurrentUser() : null;
$role = $user['role'] ?? null;
$userName = $user['full_name'] ?? $user['username'] ?? 'User';

$dashboardUrl = base_url('/');
$roleLabel = 'Guest';

if ($role === 'admin') {
    $dashboardUrl = base_url('/modules/admin/dashboard.php');
    $roleLabel = 'System Administrator';
} elseif ($role === 'head') {
    $dashboardUrl = base_url('/modules/head/dashboard.php');
    $roleLabel = 'Department Head';
} elseif ($role === 'lecturer') {
    $dashboardUrl = base_url('/modules/lecturer/dashboard.php');
    $roleLabel = 'Course Lecturer';
}
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5" style="max-width: 560px;">
            <div class="cm-card shadow-sm border bg-white text-center">
                <div class="cm-card-body p-4 p-sm-5">
                    <!-- Restriction Emblem -->
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-50 text-warning mb-3" style="width: 64px; height: 64px; font-size: 2rem;" aria-hidden="true">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>

                    <h1 class="h3 fw-bold text-slate-900 mb-2">403 · Access Restricted</h1>
                    <p class="text-secondary text-sm mb-4">
                        You do not have permission to view or manage the requested academic resource.
                    </p>

                    <?php if ($isAuth): ?>
                        <!-- Active Session Details Callout -->
                        <div class="p-3 rounded-2 bg-slate-50 border text-start mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-xs text-muted font-monospace">AUTHENTICATED SESSION</span>
                                <span class="badge cm-badge-neutral"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="text-sm fw-semibold text-slate-900 mb-1">
                                <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="text-xs text-secondary">
                                This module is restricted to specific academic tiers. Your current role does not have authorization to access this area.
                            </div>
                        </div>

                        <!-- Role-based Return Actions -->
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 pt-2">
                            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary px-4">
                                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                                <span>Return to Your Dashboard</span>
                            </a>
                            <a href="<?= htmlspecialchars(base_url('/public/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>
                                <span>Switch Account</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Unauthenticated Notice -->
                        <div class="p-3 rounded-2 bg-slate-50 border text-start mb-4">
                            <div class="text-xs text-secondary">
                                Your session may have expired or you have not yet authenticated into the CourseMonitor academic portal.
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 pt-2">
                            <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary px-4">
                                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                <span>Sign In to CourseMonitor</span>
                            </a>
                            <a href="<?= htmlspecialchars(base_url('/'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-1" aria-hidden="true"></i>
                                <span>Return to Home</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 mt-4 border-top text-xs text-muted">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        If you believe you require access to this section, please contact your Academic Department Head or the System Administrator.
                    </div>
                </div>

                <div class="cm-card-footer bg-slate-50 border-top text-center py-3">
                    <span class="text-xs text-muted">
                        CourseMonitor Institutional Security Enforcement
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
