<?php
/**
 * CourseMonitor - Attendance History (Multi-Session Matrix)
 * Student Attendance & Grades Made Simple
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

// Get Courses
$stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ? ORDER BY code ASC");
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$res = $stmt->get_result();
$courses = [];
while ($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

$selectedCourseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$pageTitle = 'Attendance History';

$extraHead = '
<style>
@media print {
    .cm-navbar, .cm-footer, .no-print, .skip-link, .cm-page-header button, .cm-page-header a {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        font-size: 11pt;
    }
    .cm-card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-responsive {
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    .table th, .table td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 6px !important;
    }
    .badge {
        border: 1px solid #94a3b8 !important;
        background: transparent !important;
        color: #000000 !important;
    }
}
</style>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" 
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to My Classes
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/attendance.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="takeAttendanceLink"
               class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Take Class Attendance
            </a>
            <button type="button" 
                    onclick="window.print()" 
                    class="btn btn-outline-secondary btn-sm" 
                    title="Print attendance report">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="cm-page-tag">
                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Attendance Records
            </div>
            <h1 class="cm-page-title">Attendance History</h1>
            <p class="cm-page-subtitle">Review past class attendance sessions, see student attendance rates, and spot attendance issues early.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="cm-card mb-4 no-print">
        <div class="cm-card-header py-3">
            <h2 class="cm-card-title h6 mb-0">
                <i class="bi bi-funnel me-2 text-primary" aria-hidden="true"></i>Filter Sessions
            </h2>
        </div>
        <div class="cm-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="course" class="form-label fw-medium">
                        Select Class <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="course" class="form-select" onchange="onCourseChanged()" required aria-required="true">
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= intval($c['id']) ?>" <?= ($selectedCourseId === intval($c['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['code'] . ' - ' . $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label fw-medium">From Date</label>
                    <input type="date" id="from_date" class="form-control tabular-nums" onchange="loadAttendance()">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label fw-medium">To Date</label>
                    <input type="date" id="to_date" class="form-control tabular-nums" onchange="loadAttendance()">
                </div>
                <div class="col-md-1">
                    <button type="button" 
                            class="btn btn-outline-secondary btn-sm w-100 py-2" 
                            onclick="resetDateFilters()" 
                            title="Reset date filters">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Strip -->
    <div id="metricsRow" class="row g-3 mb-4 d-none no-print">
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Enrolled Students</span>
                <div id="statEnrolled" class="cm-stat-val tabular-nums fs-4 mb-0">-</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Sessions Held</span>
                <div id="statSessions" class="cm-stat-val tabular-nums fs-4 mb-0">-</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Average Attendance</span>
                <div id="statAvgRate" class="cm-stat-val tabular-nums fs-4 text-success mb-0">-%</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Below 70% Attendance</span>
                <div id="statAtRisk" class="cm-stat-val tabular-nums fs-4 text-danger mb-0">-</div>
            </div>
        </div>
    </div>

    <!-- Attendance Matrix Table -->
    <div class="cm-card">
        <div class="cm-card-header py-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h6 mb-0">
                    <i class="bi bi-calendar3 me-2 text-primary" aria-hidden="true"></i>Session Matrix
                </h2>
            </div>
            <div class="d-flex align-items-center gap-2 small">
                <span class="badge cm-badge-success px-2 py-1"><i class="bi bi-check-lg me-1"></i>P = Present</span>
                <span class="badge cm-badge-danger px-2 py-1"><i class="bi bi-x-lg me-1"></i>A = Absent</span>
                <span class="badge cm-badge-warning px-2 py-1"><i class="bi bi-clock me-1"></i>L = Late</span>
            </div>
        </div>
        <div class="cm-card-body p-0">
            <div class="table-responsive" style="max-height: 72vh;">
                <table class="table cm-table cm-table-sticky table-hover align-middle mb-0" id="attendanceTable">
                    <thead id="matrixThead">
                        <tr>
                            <th scope="col" style="min-width: 140px;">Student ID</th>
                            <th scope="col" style="min-width: 180px;">Student Name</th>
                            <th scope="col" class="text-center" style="min-width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="matrixTbody">
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">Please Select a Class</h3>
                                    <p class="cm-empty-desc mb-0">Choose your assigned class above to view the complete session history.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('course').value) {
            loadAttendance();
        }
    });

    function onCourseChanged() {
        const courseId = document.getElementById('course').value;
        const takeBtn = document.getElementById('takeAttendanceLink');
        if (takeBtn) {
            takeBtn.href = '<?= htmlspecialchars(base_url('/modules/lecturer/attendance.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
        loadAttendance();
    }

    function resetDateFilters() {
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        loadAttendance();
    }

    function loadAttendance() {
        const courseId = document.getElementById('course').value;
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;
        
        const thead = document.getElementById('matrixThead');
        const tbody = document.getElementById('matrixTbody');
        const metricsRow = document.getElementById('metricsRow');

        if (!courseId) {
            thead.innerHTML = `
                <tr>
                    <th scope="col" style="min-width: 140px;">Student ID</th>
                    <th scope="col" style="min-width: 180px;">Student Name</th>
                    <th scope="col" class="text-center" style="min-width: 100px;">Status</th>
                </tr>
            `;
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center py-5 text-muted">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <h3 class="cm-empty-title h6">Please Select a Class</h3>
                            <p class="cm-empty-desc mb-0">Choose your assigned class above to view the complete session history.</p>
                        </div>
                    </td>
                </tr>
            `;
            metricsRow.classList.add('d-none');
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="100" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading attendance records...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Compiling class attendance records...</p>
                </td>
            </tr>
        `;

        let url = `<?= htmlspecialchars(base_url('/api/get_attendance_matrix.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`;
        if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
        if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="100" class="text-center py-4">
                                <div class="alert cm-alert-danger d-inline-flex align-items-center mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                                    <span>${escapeHtml(data.error)}</span>
                                </div>
                            </td>
                        </tr>
                    `;
                    metricsRow.classList.add('d-none');
                    return;
                }

                const students = Array.isArray(data.students) ? data.students : [];
                const dates = Array.isArray(data.dates) ? data.dates : [];
                const attendance = data.attendance || {};

                if (students.length === 0) {
                    thead.innerHTML = `
                        <tr>
                            <th scope="col">Student ID</th>
                            <th scope="col">Student Name</th>
                            <th scope="col" class="text-center">Status</th>
                        </tr>
                    `;
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">No Enrolled Students</h3>
                                    <p class="cm-empty-desc mb-0">No students are currently enrolled in this class.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    metricsRow.classList.add('d-none');
                    return;
                }

                // 1. Build Headers
                let headerHtml = `
                    <tr>
                        <th scope="col" class="tabular-nums" style="min-width: 140px;">Student ID</th>
                        <th scope="col" style="min-width: 190px;">Student Name</th>
                `;

                if (dates.length === 0) {
                    headerHtml += '<th scope="col" class="text-center text-muted">Sessions</th>';
                } else {
                    dates.forEach(d => {
                        headerHtml += `<th scope="col" class="text-center tabular-nums font-monospace small px-2" style="min-width: 90px;" title="Session: ${escapeHtml(d)}">${escapeHtml(d)}</th>`;
                    });
                }

                headerHtml += `
                    <th scope="col" class="text-center" style="min-width: 48px;" title="Present Count">P</th>
                    <th scope="col" class="text-center" style="min-width: 48px;" title="Absent Count">A</th>
                    <th scope="col" class="text-center" style="min-width: 48px;" title="Late Count">L</th>
                    <th scope="col" class="text-center" style="min-width: 90px;" title="Attendance Rate">Rate</th>
                </tr>
                `;
                thead.innerHTML = headerHtml;

                // 2. Build Rows & Compute Metrics
                tbody.innerHTML = '';
                let totalStudentsPresentRateSum = 0;
                let atRiskCount = 0;

                students.forEach(student => {
                    const sid = student.id;
                    const studentAtt = attendance[sid] || {};
                    let pCount = 0, aCount = 0, lCount = 0;
                    let totalSessionsRecorded = 0;

                    let rowHtml = `
                        <tr>
                            <td class="tabular-nums font-monospace fw-medium">${escapeHtml(student.student_id_number)}</td>
                            <td class="fw-medium">${escapeHtml(student.name)}</td>
                    `;

                    if (dates.length === 0) {
                        rowHtml += '<td class="text-center text-muted small">&ndash;</td>';
                    } else {
                        dates.forEach(d => {
                            const status = studentAtt[d];
                            if (status) {
                                totalSessionsRecorded++;
                                const firstChar = status.charAt(0).toUpperCase();
                                if (firstChar === 'P') {
                                    pCount++;
                                    rowHtml += `<td class="text-center"><span class="badge cm-badge-success tabular-nums px-2 py-1" title="Present: ${escapeHtml(d)}">P</span></td>`;
                                } else if (firstChar === 'A') {
                                    aCount++;
                                    rowHtml += `<td class="text-center"><span class="badge cm-badge-danger tabular-nums px-2 py-1" title="Absent: ${escapeHtml(d)}">A</span></td>`;
                                } else if (firstChar === 'L') {
                                    lCount++;
                                    rowHtml += `<td class="text-center"><span class="badge cm-badge-warning tabular-nums px-2 py-1" title="Late: ${escapeHtml(d)}">L</span></td>`;
                                } else {
                                    rowHtml += `<td class="text-center"><span class="badge cm-badge-neutral tabular-nums">${escapeHtml(firstChar)}</span></td>`;
                                }
                            } else {
                                rowHtml += '<td class="text-center text-muted tabular-nums" title="Unrecorded">&ndash;</td>';
                            }
                        });
                    }

                    // Calculate Student Attendance Rate
                    let rateVal = null;
                    let rateBadge = '<span class="text-muted tabular-nums">&ndash;</span>';
                    if (totalSessionsRecorded > 0) {
                        rateVal = (pCount / totalSessionsRecorded) * 100;
                        totalStudentsPresentRateSum += rateVal;
                        
                        let badgeClass = 'cm-badge-success';
                        if (rateVal < 70) {
                            badgeClass = 'cm-badge-danger';
                            atRiskCount++;
                        } else if (rateVal < 80) {
                            badgeClass = 'cm-badge-warning';
                        }
                        rateBadge = `<span class="badge ${badgeClass} tabular-nums fw-bold">${rateVal.toFixed(0)}%</span>`;
                    }

                    rowHtml += `
                        <td class="text-center tabular-nums fw-bold text-success">${pCount}</td>
                        <td class="text-center tabular-nums fw-bold text-danger">${aCount}</td>
                        <td class="text-center tabular-nums fw-bold text-warning">${lCount}</td>
                        <td class="text-center tabular-nums">${rateBadge}</td>
                    </tr>
                    `;

                    tbody.innerHTML += rowHtml;
                });

                // Update Metrics Row
                metricsRow.classList.remove('d-none');
                document.getElementById('statEnrolled').textContent = students.length;
                document.getElementById('statSessions').textContent = dates.length;
                
                const avgClassRate = students.length > 0 && dates.length > 0
                    ? (totalStudentsPresentRateSum / students.length).toFixed(1) + '%'
                    : '-';
                document.getElementById('statAvgRate').textContent = avgClassRate;
                document.getElementById('statAtRisk').textContent = atRiskCount;
            })
            .catch(err => {
                console.error('Error fetching attendance matrix:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="100" class="text-center py-4 text-danger">
                            <i class="bi bi-wifi-off me-2" aria-hidden="true"></i>Network connection error while fetching attendance matrix.
                        </td>
                    </tr>
                `;
                metricsRow.classList.add('d-none');
            });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>