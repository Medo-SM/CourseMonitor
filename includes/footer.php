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

    <!-- CourseMonitor Theme Toggle Handler -->
    <script>
    (function() {
        var btn = document.getElementById('themeToggleBtn');
        if (!btn) return;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            var nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

            // Suppress CSS transitions during theme swap (better-ui exact recipe)
            document.documentElement.classList.add('theme-switching');

            document.documentElement.setAttribute('data-theme', nextTheme);
            document.documentElement.setAttribute('data-bs-theme', nextTheme);

            try {
                localStorage.setItem('cm_theme', nextTheme);
            } catch (err) {}

            var metaTheme = document.querySelector('meta[name="theme-color"]');
            if (metaTheme) {
                metaTheme.setAttribute('content', nextTheme === 'dark' ? '#0b0f17' : '#1e293b');
            }

            // Force reflow and restore transitions after frame render
            window.getComputedStyle(document.documentElement).opacity;
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    document.documentElement.classList.remove('theme-switching');
                });
            });
        });
    })();
    </script>

    <?php if (!empty($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
