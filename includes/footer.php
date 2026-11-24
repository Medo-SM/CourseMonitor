<?php
/**
 * CourseMonitor Shared Footer Partial (includes/footer.php)
 * Student Attendance & Grades Made Simple
 */

if (defined('CM_FOOTER_RENDERED')) {
    return;
}
define('CM_FOOTER_RENDERED', true);
?>
    </main>

    <!-- Institutional Footer Landmark -->
    <footer class="cm-footer mt-auto" role="contentinfo">
        <div class="container-fluid px-3 px-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary">&copy; <?= date('Y') ?> CourseMonitor.</span>
                    <span class="d-none d-sm-inline text-muted">Student Attendance &amp; Grades Made Simple</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge cm-badge-neutral font-monospace">v2.4</span>
                    <span class="text-muted small">All rights reserved</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS CDN (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
