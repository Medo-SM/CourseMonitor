<?php
require_once __DIR__ . '/../includes/auth_functions.php';

http_response_code(403);

$pageTitle = 'Page Not Accessible';
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
    $roleLabel = 'Administrator';
} elseif ($role === 'head') {
    $dashboardUrl = base_url('/modules/head/dashboard.php');
    $roleLabel = 'Department Head';
} elseif ($role === 'lecturer') {
    $dashboardUrl = base_url('/modules/lecturer/dashboard.php');
    $roleLabel = 'Teacher';
}
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5" style="max-width: 540px;">
            <div class="cm-card shadow-sm border bg-white text-center">
                <div class="cm-card-body p-4 p-sm-5">
                    <!-- Icon Emblem -->
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-50 text-warning mb-3" style="width: 56px; height: 56px; font-size: 1.75rem;" aria-hidden="true">
                        <i class="bi bi-shield-lock"></i>
                    </div>

                    <h1 class="h4 fw-bold text-slate-900 mb-2">Page Not Accessible</h1>
                    <p class="text-secondary text-sm mb-4">
                        You don't have permission to view this page. Let's get you back to your dashboard.
                    </p>

                    <?php if ($isAuth): ?>
                        <!-- Active Session Details Callout -->
                        <div class="p-3 rounded-2 bg-slate-50 border text-start mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-xs text-muted">Signed in as</span>
                                <span class="badge cm-badge-neutral"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="text-sm fw-semibold text-slate-900 mb-1">
                                <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="text-xs text-secondary">
                                This section is restricted to other roles. You can return to your dashboard or switch accounts below.
                            </div>
                        </div>

                        <!-- Role-based Return Actions -->
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 pt-1">
                            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary px-4">
                                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                                <span>Go to Dashboard</span>
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
                                You need to be signed in to access this page. Please sign in with your account to continue.
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 pt-1">
                            <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary px-4">
                                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                <span>Sign In</span>
                            </a>
                            <a href="<?= htmlspecialchars(base_url('/'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-1" aria-hidden="true"></i>
                                <span>Return to Home</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 mt-4 border-top text-xs text-muted">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        If you believe you need access to this page, please contact your Department Head or system administrator.
                    </div>
                </div>

                <div class="cm-card-footer bg-slate-50 border-top text-center py-3">
                    <span class="text-xs text-muted">
                        CourseMonitor Access Control
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
