<?php
// modules/head/audit_logs.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$pageTitle = 'Department Audit Trail';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= htmlspecialchars(base_url('/modules/head/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Dashboard
                </a>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small fw-medium">Department Head Oversight</span>
            </div>
            <h1 class="cm-page-title">Institutional Audit Trail</h1>
            <p class="cm-page-subtitle">Chronological record of verified grade updates, attendance modifications, and administrative revisions</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" onclick="loadLogs()" class="btn btn-outline-secondary btn-sm" aria-label="Refresh audit logs">
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
                <span>Refresh Log</span>
            </button>
            <a href="<?= htmlspecialchars(base_url('/modules/head/modify_records.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                <span>Modify Records</span>
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Total Logged Events</span>
                    <div class="cm-stat-icon primary" aria-hidden="true"><i class="bi bi-journal-text"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums" id="statTotalEvents">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Tracked modifications</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Grade Revisions</span>
                    <div class="cm-stat-icon success" aria-hidden="true"><i class="bi bi-award"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums text-primary" id="statGradeEvents">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Coursework adjustments</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Attendance Revisions</span>
                    <div class="cm-stat-icon info" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums text-info" id="statAttendanceEvents">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Presence &amp; absence updates</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Latest Activity</span>
                    <div class="cm-stat-icon warning" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
                </div>
                <div class="fs-6 fw-bold tabular-nums text-dark mt-2 mb-1" id="statLatestTime">-</div>
                <p class="cm-stat-meta"><span class="text-secondary" id="statLatestModifier">Awaiting logs</span></p>
            </div>
        </div>
    </div>

    <!-- Audit Log Table Card -->
    <div class="card cm-card">
        <div class="cm-card-header flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
            <!-- Filters -->
            <div class="d-flex flex-grow-1 flex-wrap gap-2">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-light border-end-0 text-muted" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="search" id="logSearch" class="form-control border-start-0" 
                           placeholder="Search student, course, or reason..." 
                           aria-label="Search audit log entries"
                           aria-describedby="search-addon"
                           oninput="filterLogs()">
                </div>

                <div style="min-width: 170px;">
                    <label for="tableFilter" class="visually-hidden">Filter by Entity Type</label>
                    <select id="tableFilter" class="form-select" onchange="filterLogs()">
                        <option value="">All Categories</option>
                        <option value="attendance">Attendance Records</option>
                        <option value="coursework">Coursework &amp; Grades</option>
                    </select>
                </div>
            </div>

            <!-- Result Count -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small" id="logCountLabel">Loading logs...</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0 rounded-0">
                <table class="table cm-table table-hover align-middle mb-0" id="auditTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 70px;">ID</th>
                            <th scope="col" style="width: 160px;">Date &amp; Time</th>
                            <th scope="col" style="width: 110px;">Entity</th>
                            <th scope="col" style="width: 220px;">Subject Details</th>
                            <th scope="col" style="width: 90px;">Action</th>
                            <th scope="col" style="width: 220px;">Value Transition</th>
                            <th scope="col">Justification / Reason</th>
                            <th scope="col" style="width: 120px;">Modified By</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading audit logs...</span>
                                </div>
                                <span class="text-secondary small">Retrieving audit trail...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyAuditState" class="cm-empty-state d-none py-5">
                <div class="cm-empty-icon" aria-hidden="true">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h2 class="cm-empty-title h6" id="emptyTitle">No Audit Records Found</h2>
                <p class="cm-empty-desc mb-0" id="emptyDesc">No historical modifications match your current filter settings.</p>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let allLogs = [];

    document.addEventListener('DOMContentLoaded', loadLogs);

    function loadLogs() {
        const tbody = document.getElementById('logTableBody');
        const emptyState = document.getElementById('emptyAuditState');
        const countLabel = document.getElementById('logCountLabel');

        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-secondary small">Fetching audit entries...</span>
                </td>
            </tr>
        `;
        emptyState.classList.add('d-none');

        fetch('<?= htmlspecialchars(base_url('/api/get_audit_logs.php'), ENT_QUOTES, 'UTF-8') ?>')
            .then(res => res.json())
            .then(data => {
                allLogs = Array.isArray(data) ? data : [];
                updateStats(allLogs);
                renderLogs(allLogs);
            })
            .catch(err => {
                console.error('Failed to load audit logs:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Failed to retrieve audit trail. Please refresh.
                        </td>
                    </tr>
                `;
                countLabel.textContent = 'Error loading logs';
            });
    }

    function updateStats(logs) {
        const total = logs.length;
        const gradeEvents = logs.filter(l => l.table_name === 'coursework').length;
        const attEvents = logs.filter(l => l.table_name === 'attendance').length;

        document.getElementById('statTotalEvents').textContent = total;
        document.getElementById('statGradeEvents').textContent = gradeEvents;
        document.getElementById('statAttendanceEvents').textContent = attEvents;

        if (total > 0 && logs[0].modified_at) {
            const latestDate = new Date(logs[0].modified_at);
            document.getElementById('statLatestTime').textContent = latestDate.toLocaleDateString(undefined, { 
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
            document.getElementById('statLatestModifier').textContent = `By ${logs[0].modifier_name || 'System'}`;
        } else {
            document.getElementById('statLatestTime').textContent = 'None';
            document.getElementById('statLatestModifier').textContent = 'No records';
        }
    }

    function filterLogs() {
        const query = document.getElementById('logSearch').value.trim().toLowerCase();
        const tableFilter = document.getElementById('tableFilter').value;

        const filtered = allLogs.filter(row => {
            const matchesTable = !tableFilter || row.table_name === tableFilter;

            let reason = row.reason || row.action || '';
            const matchesQuery = !query ||
                (row.details && row.details.toLowerCase().includes(query)) ||
                (row.modifier_name && row.modifier_name.toLowerCase().includes(query)) ||
                (String(row.id).includes(query)) ||
                (String(row.record_id).includes(query)) ||
                (reason.toLowerCase().includes(query));

            return matchesTable && matchesQuery;
        });

        renderLogs(filtered);
    }

    function renderLogs(logs) {
        const tbody = document.getElementById('logTableBody');
        const emptyState = document.getElementById('emptyAuditState');
        const countLabel = document.getElementById('logCountLabel');

        tbody.innerHTML = '';

        if (logs.length === 0) {
            emptyState.classList.remove('d-none');
            countLabel.textContent = 'Showing 0 records';
            return;
        }

        emptyState.classList.add('d-none');
        countLabel.textContent = `Showing ${logs.length} record${logs.length === 1 ? '' : 's'}`;

        logs.forEach(row => {
            const tr = document.createElement('tr');

            // Format date & time
            const dateObj = new Date(row.modified_at);
            const dateStr = isNaN(dateObj.getTime()) ? row.modified_at : dateObj.toLocaleDateString(undefined, {
                year: 'numeric', month: 'short', day: '2-digit'
            });
            const timeStr = isNaN(dateObj.getTime()) ? '' : dateObj.toLocaleTimeString(undefined, {
                hour: '2-digit', minute: '2-digit'
            });

            // Extract Clean Reason
            let reason = row.reason || row.action || 'No justification provided';
            if (typeof reason === 'string' && reason.includes('(Reason:')) {
                reason = reason.substring(reason.indexOf('(Reason:') + 9, reason.length - 1).trim();
            } else if (typeof reason === 'string' && reason.startsWith('UPDATE: ')) {
                reason = reason.substring(8).trim();
            }

            // Action Badge
            let actionBadgeClass = 'cm-badge-neutral';
            let actionText = 'UPDATE';
            if (typeof row.action === 'string') {
                if (row.action.includes('CREATE')) {
                    actionBadgeClass = 'cm-badge-success';
                    actionText = 'CREATE';
                } else if (row.action.includes('DELETE')) {
                    actionBadgeClass = 'cm-badge-danger';
                    actionText = 'DELETE';
                } else {
                    actionBadgeClass = 'cm-badge-info';
                    actionText = 'UPDATE';
                }
            }

            // Entity badge
            const isAttendance = row.table_name === 'attendance';
            const entityBadge = isAttendance 
                ? '<span class="badge cm-badge-info font-monospace">Attendance</span>' 
                : '<span class="badge cm-badge-primary font-monospace">Coursework</span>';

            tr.innerHTML = `
                <td>
                    <span class="font-monospace text-muted small tabular-nums">#${escapeHtml(String(row.id))}</span>
                </td>
                <td class="text-nowrap">
                    <div class="fw-medium small tabular-nums text-dark">${escapeHtml(dateStr)}</div>
                    <div class="text-muted small tabular-nums">${escapeHtml(timeStr)}</div>
                </td>
                <td>${entityBadge}</td>
                <td>
                    <div class="fw-semibold small text-dark">${escapeHtml(row.details || `Record #${row.record_id}`)}</div>
                    <span class="text-muted small tabular-nums">Ref: #${escapeHtml(String(row.record_id))}</span>
                </td>
                <td>
                    <span class="badge ${actionBadgeClass}">${escapeHtml(actionText)}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1 small">
                        <span class="text-danger text-decoration-line-through tabular-nums">${escapeHtml(row.old_value || 'None')}</span>
                        <i class="bi bi-arrow-right text-muted" aria-hidden="true"></i>
                        <span class="text-success fw-bold tabular-nums">${escapeHtml(row.new_value || 'None')}</span>
                    </div>
                </td>
                <td>
                    <div class="small text-secondary" style="max-width: 320px; line-height: 1.4;">
                        ${escapeHtml(reason)}
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1 small text-nowrap">
                        <i class="bi bi-person-fill text-secondary" aria-hidden="true"></i>
                        <span class="fw-medium text-dark">${escapeHtml(row.modifier_name || 'Head')}</span>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
