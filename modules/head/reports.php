<?php
// modules/head/reports.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get Department Info
$dept_stmt = $mysqli->prepare("SELECT id, name FROM departments WHERE head_id = ?");
$dept_stmt->bind_param("i", $user_id);
$dept_stmt->execute();
$dept_res = $dept_stmt->get_result();
$dept = $dept_res->fetch_assoc();
$dept_stmt->close();

$dept_id = $dept ? (int)$dept['id'] : 0;
$dept_name = $dept ? $dept['name'] : 'Department';

// Fetch courses for filter: only courses in Dept managed by THIS Head, with lecturer & semester metadata
$courses_stmt = $mysqli->prepare("
    SELECT c.id, c.code, c.name, c.lecturer_id, u.full_name as lecturer_name,
           GROUP_CONCAT(cs.semester_number) as semesters
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    LEFT JOIN users u ON c.lecturer_id = u.id
    LEFT JOIN course_semesters cs ON c.id = cs.course_id
    WHERE d.head_id = ?
    GROUP BY c.id
    ORDER BY c.code ASC
");
$courses_stmt->bind_param("i", $user_id);
$courses_stmt->execute();
$result = $courses_stmt->get_result();
$courses = [];
$lecturers = [];

while ($row = $result->fetch_assoc()) {
    $row['semester_list'] = $row['semesters'] ? array_map('intval', explode(',', $row['semesters'])) : [];
    $courses[] = $row;

    if ($row['lecturer_id'] && $row['lecturer_name']) {
        $lecturers[$row['lecturer_id']] = $row['lecturer_name'];
    }
}
$courses_stmt->close();

$pageTitle = 'Department Performance Reports';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    @media print {
        .no-print, header, footer, .skip-link, .btn, .cm-navbar, .cm-footer {
            display: none !important;
        }
        body {
            background-color: #ffffff !important;
            color: #000000 !important;
            font-size: 12pt;
        }
        .container-fluid {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card, .cm-card {
            border: 1px solid #94a3b8 !important;
            box-shadow: none !important;
            break-inside: avoid;
        }
        .cm-print-header {
            display: block !important;
            margin-bottom: 2rem;
            border-bottom: 2px solid #000;
            padding-bottom: 1rem;
        }
    }
    .cm-print-header {
        display: none;
    }
</style>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Print-Only Official Header -->
    <div class="cm-print-header text-center">
        <h1 class="h3 fw-bold mb-1">CourseMonitor Academic Management System</h1>
        <h2 class="h5 mb-1">Department Performance &amp; Attendance Report</h2>
        <p class="mb-0 text-muted small">Department: <?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?> &bull; Generated: <?= date('F j, Y - H:i') ?></p>
    </div>

    <!-- Screen Page Header -->
    <div class="cm-page-header no-print">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= htmlspecialchars(base_url('/modules/head/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Dashboard
                </a>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small fw-medium"><?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h1 class="cm-page-title">Department Reports</h1>
            <p class="cm-page-subtitle">Aggregate academic grade distributions, attendance compliance rates, and at-risk student tracking</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm" aria-label="Print or export PDF">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>
                <span>Print / Save PDF</span>
            </button>
            <button type="button" onclick="exportCsvReport()" class="btn btn-outline-secondary btn-sm" id="csvExportBtn" aria-label="Export report to CSV">
                <i class="bi bi-download me-1" aria-hidden="true"></i>
                <span>Export CSV</span>
            </button>
            <a href="<?= htmlspecialchars(base_url('/modules/head/modify_records.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                <span>Modify Records</span>
            </a>
        </div>
    </div>

    <!-- Filter Controls Card -->
    <div class="card cm-card mb-4 no-print">
        <div class="cm-card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-secondary" aria-hidden="true"></i>
                <h2 class="cm-card-title h6 mb-0">Report Generation Parameters</h2>
            </div>
        </div>
        <div class="cm-card-body p-4">
            <div class="row g-3 align-items-end">
                <!-- Course Filter -->
                <div class="col-md-4">
                    <label for="courseFilter" class="form-label">Filter by Course</label>
                    <select id="courseFilter" class="form-select" onchange="loadReports()">
                        <option value="">All Department Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" 
                                    data-lecturer="<?= (int)$c['lecturer_id'] ?>" 
                                    data-semesters="<?= htmlspecialchars(json_encode($c['semester_list']), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($c['code'] . ' &ndash; ' . $c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div class="col-md-3">
                    <label for="semesterFilter" class="form-label">Semester Filter</label>
                    <select id="semesterFilter" class="form-select" onchange="filterCourseOptions()">
                        <option value="">All Semesters (1 to 8)</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Lecturer Filter -->
                <div class="col-md-3">
                    <label for="lecturerFilter" class="form-label">Lecturer Filter</label>
                    <select id="lecturerFilter" class="form-select" onchange="filterCourseOptions()">
                        <option value="">All Lecturers</option>
                        <?php foreach ($lecturers as $lid => $lname): ?>
                            <option value="<?= (int)$lid ?>"><?= htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" onclick="loadReports()" class="btn btn-primary btn-sm w-100 py-2">
                        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                        <span>Generate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="reportLoading" class="text-center py-5 d-none no-print">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Compiling academic report...</span>
        </div>
        <p class="text-secondary small mt-2">Aggregating departmental performance data...</p>
    </div>

    <!-- Report Container -->
    <div id="reportContainer" style="display: none;">
        <!-- Report Header Summary Banner -->
        <div class="card cm-card mb-4">
            <div class="cm-card-body p-4 text-center">
                <span class="badge cm-badge-neutral text-uppercase tracking-wider mb-2 font-monospace">Official Department Analytics</span>
                <h2 class="h4 fw-bold text-dark mb-1" id="reportTitle">Department Performance Report</h2>
                <p class="text-secondary small mb-0">
                    Department: <strong class="text-dark"><?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                    Compiled at <span id="reportDate" class="tabular-nums font-monospace">--</span>
                </p>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-4 mb-4">
            <!-- Average Grade Card -->
            <div class="col-md-6">
                <div class="cm-stat-card border-primary">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Average Grade</span>
                        <div class="cm-stat-icon primary" aria-hidden="true"><i class="bi bi-award"></i></div>
                    </div>
                    <div class="cm-stat-val tabular-nums text-primary" id="avgGrade">--</div>
                    <p class="cm-stat-meta">
                        <span class="text-secondary">Across graded coursework components (Scale 0 &ndash; 100)</span>
                    </p>
                </div>
            </div>

            <!-- Average Attendance Card -->
            <div class="col-md-6">
                <div class="cm-stat-card border-success" id="attCard">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Department Attendance Rate</span>
                        <div class="cm-stat-icon success" aria-hidden="true"><i class="bi bi-calendar-check"></i></div>
                    </div>
                    <div class="cm-stat-val tabular-nums text-success" id="attPercent">--%</div>
                    <p class="cm-stat-meta">
                        <span class="text-secondary">Percentage of recorded sessions marked Present</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Two Column Breakdown: Grade Distribution & At-Risk Students -->
        <div class="row g-4 mb-4">
            <!-- Grade Distribution Table -->
            <div class="col-lg-6">
                <div class="card cm-card h-100">
                    <div class="cm-card-header bg-light">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart text-secondary" aria-hidden="true"></i>
                            <h3 class="cm-card-title h6 mb-0">Grade Distribution Breakdown</h3>
                        </div>
                        <span class="badge cm-badge-neutral tabular-nums" id="totalGradesBadge">0 grades</span>
                    </div>
                    <div class="cm-card-body p-0">
                        <div class="table-responsive border-0 rounded-0">
                            <table class="table cm-table table-hover align-middle mb-0" id="gradeTable">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 35%;">Grade Range</th>
                                        <th scope="col" class="text-center" style="width: 25%;">Student Count</th>
                                        <th scope="col" style="width: 40%;">Proportion</th>
                                    </tr>
                                </thead>
                                <tbody id="gradeDistBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- At-Risk Students Table -->
            <div class="col-lg-6">
                <div class="card cm-card h-100 border-danger-subtle">
                    <div class="cm-card-header bg-danger-subtle text-danger">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <h3 class="cm-card-title h6 mb-0 text-danger">At-Risk Students (Attendance &lt; 75%)</h3>
                        </div>
                        <span class="badge cm-badge-danger tabular-nums" id="riskCountBadge">0 flagged</span>
                    </div>
                    <div class="cm-card-body p-0">
                        <div class="table-responsive border-0 rounded-0">
                            <table class="table cm-table table-hover align-middle mb-0" id="riskTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Student Name</th>
                                        <th scope="col" style="width: 110px;">Course</th>
                                        <th scope="col" class="text-end" style="width: 120px;">Att. Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="riskBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let currentReportData = null;

    document.addEventListener('DOMContentLoaded', loadReports);

    function filterCourseOptions() {
        const sem = document.getElementById('semesterFilter').value;
        const lec = document.getElementById('lecturerFilter').value;
        const select = document.getElementById('courseFilter');
        const options = select.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // Skip "All Courses"

            const courseLec = opt.getAttribute('data-lecturer');
            let courseSems = [];
            try {
                courseSems = JSON.parse(opt.getAttribute('data-semesters') || '[]');
            } catch(e) {}

            const matchesLec = !lec || courseLec === lec;
            const matchesSem = !sem || courseSems.includes(parseInt(sem, 10));

            if (matchesLec && matchesSem) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Trigger load
        loadReports();
    }

    function loadReports() {
        const courseSelect = document.getElementById('courseFilter');
        const courseId = courseSelect.value;
        const container = document.getElementById('reportContainer');
        const loading = document.getElementById('reportLoading');

        loading.classList.remove('d-none');
        container.style.display = 'none';

        let url = '<?= htmlspecialchars(base_url('/api/get_reports.php'), ENT_QUOTES, 'UTF-8') ?>';
        if (courseId) {
            url += `?course_id=${encodeURIComponent(courseId)}`;
            const selectedText = courseSelect.options[courseSelect.selectedIndex].text;
            document.getElementById('reportTitle').textContent = `Course Performance: ${selectedText}`;
        } else {
            document.getElementById('reportTitle').textContent = 'Department Performance Report';
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('d-none');
                if (data.error) {
                    alert(data.error);
                    return;
                }

                currentReportData = data;
                renderReportContent(data);
                container.style.display = 'block';
            })
            .catch(err => {
                loading.classList.add('d-none');
                console.error('Failed to load report:', err);
                alert('An error occurred while compiling the report. Please try again.');
            });
    }

    function renderReportContent(data) {
        document.getElementById('reportDate').textContent = new Date().toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit'
        });

        // KPI Averages
        document.getElementById('avgGrade').textContent = data.avg_grade || '0.00';
        const attRate = parseFloat(data.attendance_percent || 0);
        const attEl = document.getElementById('attPercent');
        attEl.textContent = attRate.toFixed(1) + '%';
        if (attRate < 75) {
            attEl.className = 'cm-stat-val tabular-nums text-danger';
        } else {
            attEl.className = 'cm-stat-val tabular-nums text-success';
        }

        // Grade Distribution
        const distBody = document.getElementById('gradeDistBody');
        distBody.innerHTML = '';

        const gradeLabels = {
            'A': { label: '90 – 100 (A)', badge: 'cm-badge-success', bg: 'bg-success' },
            'B': { label: '80 – 89 (B)', badge: 'cm-badge-primary', bg: 'bg-primary' },
            'C': { label: '70 – 79 (C)', badge: 'cm-badge-info', bg: 'bg-info' },
            'D': { label: '60 – 69 (D)', badge: 'cm-badge-warning', bg: 'bg-warning' },
            'F': { label: '0 – 59 (F)', badge: 'cm-badge-danger', bg: 'bg-danger' }
        };

        let totalGrades = 0;
        for (const [key, count] of Object.entries(data.grade_distribution || {})) {
            totalGrades += parseInt(count, 10);
        }
        document.getElementById('totalGradesBadge').textContent = `${totalGrades} grade${totalGrades === 1 ? '' : 's'}`;

        for (const [key, meta] of Object.entries(gradeLabels)) {
            const count = (data.grade_distribution && data.grade_distribution[key]) ? parseInt(data.grade_distribution[key], 10) : 0;
            const pct = totalGrades > 0 ? ((count / totalGrades) * 100).toFixed(1) : '0.0';

            distBody.innerHTML += `
                <tr>
                    <td>
                        <span class="badge ${meta.badge} font-monospace me-1">${key}</span>
                        <span class="small text-secondary">${meta.label}</span>
                    </td>
                    <td class="text-center font-monospace fw-semibold tabular-nums">
                        ${count}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px;" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar ${meta.bg}" style="width: ${pct}%;"></div>
                            </div>
                            <span class="text-muted small tabular-nums" style="min-width: 42px;">${pct}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }

        // At-Risk Students
        const riskBody = document.getElementById('riskBody');
        riskBody.innerHTML = '';
        const atRiskList = Array.isArray(data.at_risk) ? data.at_risk : [];
        document.getElementById('riskCountBadge').textContent = `${atRiskList.length} flagged`;

        if (atRiskList.length === 0) {
            riskBody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center py-4 text-success">
                        <i class="bi bi-check-circle me-1"></i> Excellent! No students currently fall below the 75% attendance threshold.
                    </td>
                </tr>
            `;
        } else {
            atRiskList.forEach(row => {
                const pct = parseFloat(row.att_pct).toFixed(1);
                riskBody.innerHTML += `
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(row.student_name)}</div>
                        </td>
                        <td>
                            <span class="badge cm-badge-neutral font-monospace">${escapeHtml(row.course_code)}</span>
                        </td>
                        <td class="text-end">
                            <span class="badge cm-badge-danger tabular-nums font-monospace">${pct}%</span>
                        </td>
                    </tr>
                `;
            });
        }
    }

    function exportCsvReport() {
        if (!currentReportData) {
            alert('Please wait for the report to finish generating before exporting.');
            return;
        }

        let csvContent = 'data:text/csv;charset=utf-8,';
        csvContent += 'CourseMonitor Academic Report\n';
        csvContent += `Department,${JSON.stringify('<?= $dept_name ?>')}\n`;
        csvContent += `Generated,${new Date().toISOString()}\n`;
        csvContent += `Average Grade,${currentReportData.avg_grade}\n`;
        csvContent += `Average Attendance,${currentReportData.attendance_percent}%\n\n`;

        csvContent += 'Grade Distribution\n';
        csvContent += 'Grade,Count\n';
        for (const [g, count] of Object.entries(currentReportData.grade_distribution || {})) {
            csvContent += `${g},${count}\n`;
        }

        csvContent += '\nAt-Risk Students (Attendance < 75%)\n';
        csvContent += 'Student Name,Course Code,Attendance Rate\n';
        (currentReportData.at_risk || []).forEach(r => {
            csvContent += `"${r.student_name}","${r.course_code}","${parseFloat(r.att_pct).toFixed(1)}%"\n`;
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `Department_Report_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
