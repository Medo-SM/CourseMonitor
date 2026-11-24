<?php
/**
 * CourseMonitor - Class Gradebook
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
$pageTitle = 'Class Gradebook';

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
        padding: 5px 8px !important;
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
    <!-- Breadcrumb & Top Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" 
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to My Classes
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/coursework.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="enterGradesBtn"
               class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Enter Grades
            </a>
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/import_excel.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="importCsvBtn"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-earmark-arrow-up me-1" aria-hidden="true"></i>Import from File
            </a>
            <button type="button" 
                    onclick="window.print()" 
                    class="btn btn-outline-secondary btn-sm" 
                    title="Print gradebook report">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="cm-page-tag">
                <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>Academic Records
            </div>
            <h1 class="cm-page-title">Class Gradebook</h1>
            <p class="cm-page-subtitle">Student list with test scores, cumulative total, and academic standing.</p>
        </div>
    </div>

    <!-- Course Filter Card -->
    <div class="cm-card mb-4 no-print">
        <div class="cm-card-header py-3">
            <h2 class="cm-card-title h6 mb-0">
                <i class="bi bi-funnel me-2 text-primary" aria-hidden="true"></i>Select Gradebook
            </h2>
        </div>
        <div class="cm-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
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
                <div class="col-md-5">
                    <label for="studentSearchInput" class="form-label fw-medium">Quick Search Student</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input type="search" 
                               id="studentSearchInput" 
                               class="form-control border-start-0" 
                               placeholder="Search student by name or ID..." 
                               oninput="filterGradebookTable()">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gradebook Summary Stat Cards (dynamic) -->
    <div id="gradebookStatsRow" class="row g-3 mb-4 d-none no-print">
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Enrolled Students</span>
                <div id="statStudentCount" class="cm-stat-val tabular-nums fs-4 mb-0">-</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Assessments Recorded</span>
                <div id="statAssessmentCount" class="cm-stat-val tabular-nums fs-4 mb-0">-</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Class Total Average</span>
                <div id="statClassAverage" class="cm-stat-val tabular-nums fs-4 text-primary mb-0">-</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="cm-stat-card py-3">
                <span class="cm-stat-label">Highest Score</span>
                <div id="statHighestTotal" class="cm-stat-val tabular-nums fs-4 text-success mb-0">-</div>
            </div>
        </div>
    </div>

    <!-- Gradebook Table Card -->
    <div class="cm-card">
        <div class="cm-card-header py-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h6 mb-0">
                    <i class="bi bi-table me-2 text-primary" aria-hidden="true"></i>Assessment Marks Breakdown
                </h2>
                <span id="gradebookTableBadge" class="badge cm-badge-neutral tabular-nums">Select a class</span>
            </div>
            <div class="text-muted small">
                All assessment scores and totals displayed with clear numeric alignment.
            </div>
        </div>
        <div class="cm-card-body p-0">
            <div class="table-responsive" style="max-height: 72vh;">
                <table class="table cm-table cm-table-sticky table-hover align-middle mb-0" id="gradebookTable">
                    <thead class="table-light" id="gradebookThead">
                        <tr>
                            <th scope="col" style="width: 50px;" class="text-center">#</th>
                            <th scope="col" class="tabular-nums" style="min-width: 150px;">Student ID</th>
                            <th scope="col" style="min-width: 190px;">Student Name</th>
                            <th scope="col" class="text-center" style="min-width: 110px;">Assessments</th>
                            <th scope="col" class="text-center tabular-nums" style="min-width: 110px;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="gradebookTbody">
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">Please Select a Class</h3>
                                    <p class="cm-empty-desc mb-0">Choose your assigned class above to view student marks, cumulative totals, and academic standing.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="gradebookTfoot" class="table-light fw-bold border-top-2 d-none">
                        <!-- Summary averages -->
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let rawGradebookData = null;

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('course').value) {
            onCourseChanged();
        }
    });

    function onCourseChanged() {
        const courseId = document.getElementById('course').value;
        const enterBtn = document.getElementById('enterGradesBtn');
        const importBtn = document.getElementById('importCsvBtn');

        if (enterBtn) {
            enterBtn.href = '<?= htmlspecialchars(base_url('/modules/lecturer/coursework.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
        if (importBtn) {
            importBtn.href = '<?= htmlspecialchars(base_url('/modules/lecturer/import_excel.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }

        loadGradebook();
    }

    function loadGradebook() {
        const courseId = document.getElementById('course').value;
        const thead = document.getElementById('gradebookThead');
        const tbody = document.getElementById('gradebookTbody');
        const tfoot = document.getElementById('gradebookTfoot');
        const statsRow = document.getElementById('gradebookStatsRow');
        const badge = document.getElementById('gradebookTableBadge');

        if (!courseId) {
            thead.innerHTML = `
                <tr>
                    <th scope="col" style="width: 50px;" class="text-center">#</th>
                    <th scope="col" class="tabular-nums" style="min-width: 150px;">Student ID</th>
                    <th scope="col" style="min-width: 190px;">Student Name</th>
                    <th scope="col" class="text-center" style="min-width: 110px;">Assessments</th>
                    <th scope="col" class="text-center tabular-nums" style="min-width: 110px;">Total</th>
                </tr>
            `;
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <h3 class="cm-empty-title h6">Please Select a Class</h3>
                            <p class="cm-empty-desc mb-0">Choose your assigned class above to view student marks, cumulative totals, and academic standing.</p>
                        </div>
                    </td>
                </tr>
            `;
            tfoot.classList.add('d-none');
            statsRow.classList.add('d-none');
            badge.textContent = 'Select a class';
            rawGradebookData = null;
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="100" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading gradebook...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Compiling assessment marks and student totals...</p>
                </td>
            </tr>
        `;
        badge.textContent = 'Loading...';

        fetch(`<?= htmlspecialchars(base_url('/api/get_gradebook.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
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
                    badge.textContent = 'Error';
                    statsRow.classList.add('d-none');
                    tfoot.classList.add('d-none');
                    rawGradebookData = null;
                    return;
                }

                rawGradebookData = data;
                renderGradebook(data);
            })
            .catch(err => {
                console.error('Error loading gradebook:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="100" class="text-center py-4 text-danger">
                            <i class="bi bi-wifi-off me-2" aria-hidden="true"></i>Network connection error while retrieving gradebook.
                        </td>
                    </tr>
                `;
                badge.textContent = 'Connection error';
                statsRow.classList.add('d-none');
                tfoot.classList.add('d-none');
                rawGradebookData = null;
            });
    }

    function renderGradebook(data, filterQuery = '') {
        const thead = document.getElementById('gradebookThead');
        const tbody = document.getElementById('gradebookTbody');
        const tfoot = document.getElementById('gradebookTfoot');
        const statsRow = document.getElementById('gradebookStatsRow');
        const badge = document.getElementById('gradebookTableBadge');

        const students = Array.isArray(data.students) ? data.students : [];
        const assessments = Array.isArray(data.assessments) ? data.assessments : [];
        const grades = data.grades || {};

        if (students.length === 0) {
            thead.innerHTML = `
                <tr>
                    <th scope="col">Student ID</th>
                    <th scope="col">Student Name</th>
                    <th scope="col" class="text-center">Grades</th>
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
            badge.textContent = '0 students';
            statsRow.classList.add('d-none');
            tfoot.classList.add('d-none');
            return;
        }

        // 1. Build Header
        let headerHtml = `
            <tr>
                <th scope="col" style="width: 50px;" class="text-center">#</th>
                <th scope="col" class="tabular-nums" style="min-width: 140px;">Student ID</th>
                <th scope="col" style="min-width: 190px;">Student Name</th>
        `;

        if (assessments.length === 0) {
            headerHtml += '<th scope="col" class="text-center text-muted" style="min-width: 160px;">Assessments Recorded</th>';
        } else {
            assessments.forEach(ass => {
                headerHtml += `<th scope="col" class="text-center tabular-nums font-monospace px-2" style="min-width: 110px;">${escapeHtml(ass)}</th>`;
            });
        }

        headerHtml += `
            <th scope="col" class="text-center bg-light tabular-nums fw-bold" style="min-width: 110px;">Cumulative Total</th>
            <th scope="col" class="text-center bg-light" style="min-width: 120px;">Academic Standing</th>
        </tr>
        `;
        thead.innerHTML = headerHtml;

        // Filter students if query present
        const q = filterQuery.toLowerCase().trim();
        const displayStudents = q ? students.filter(s => {
            const sid = (s.student_id_number || '').toLowerCase();
            const name = (s.name || '').toLowerCase();
            return sid.includes(q) || name.includes(q);
        }) : students;

        if (displayStudents.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${assessments.length + 5}" class="text-center py-5">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-search"></i></div>
                            <h3 class="cm-empty-title h6">No Matching Students</h3>
                            <p class="cm-empty-desc mb-0">No student in this class matched "${escapeHtml(filterQuery)}".</p>
                        </div>
                    </td>
                </tr>
            `;
            tfoot.classList.add('d-none');
            return;
        }

        // 2. Build Body Rows
        tbody.innerHTML = '';
        let totalSum = 0;
        let highestTotal = -1;
        const colSums = {};
        const colCounts = {};
        assessments.forEach(a => { colSums[a] = 0; colCounts[a] = 0; });

        displayStudents.forEach((student, index) => {
            const sid = student.id;
            const studentGrades = grades[sid] || {};
            let studentTotal = 0;
            let recordedAny = false;

            let rowHtml = `
                <tr>
                    <td class="text-center text-muted tabular-nums small">${index + 1}</td>
                    <td class="tabular-nums font-monospace fw-medium text-dark">${escapeHtml(student.student_id_number)}</td>
                    <td class="fw-medium text-dark">${escapeHtml(student.name)}</td>
            `;

            if (assessments.length === 0) {
                rowHtml += '<td class="text-center text-muted small">&ndash;</td>';
            } else {
                assessments.forEach(ass => {
                    const grade = studentGrades[ass];
                    if (grade !== undefined && grade !== null && grade !== '') {
                        recordedAny = true;
                        const num = parseFloat(grade);
                        studentTotal += num;
                        colSums[ass] += num;
                        colCounts[ass]++;
                        rowHtml += `<td class="text-center tabular-nums font-monospace fw-medium">${num.toFixed(1)}</td>`;
                    } else {
                        rowHtml += '<td class="text-center text-muted tabular-nums">&ndash;</td>';
                    }
                });
            }

            if (recordedAny) {
                totalSum += studentTotal;
                if (studentTotal > highestTotal) highestTotal = studentTotal;
            }

            // Standing badge (soft pastel badges)
            let standingBadge = '<span class="text-muted tabular-nums small">&ndash;</span>';
            if (recordedAny) {
                if (studentTotal >= 80) {
                    standingBadge = '<span class="badge cm-badge-success tabular-nums"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Honors</span>';
                } else if (studentTotal >= 60) {
                    standingBadge = '<span class="badge cm-badge-info tabular-nums">Good Standing</span>';
                } else if (studentTotal >= 50) {
                    standingBadge = '<span class="badge cm-badge-warning tabular-nums">Passing</span>';
                } else {
                    standingBadge = '<span class="badge cm-badge-danger tabular-nums">At Risk</span>';
                }
            }

            const totalDisplay = recordedAny ? studentTotal.toFixed(1) : '&ndash;';
            rowHtml += `
                <td class="text-center tabular-nums fw-bold text-dark font-monospace fs-6">${totalDisplay}</td>
                <td class="text-center">${standingBadge}</td>
            </tr>
            `;

            tbody.innerHTML += rowHtml;
        });

        // 3. Build Column Averages Footer Row
        if (assessments.length > 0 && displayStudents.length > 0) {
            let footHtml = `
                <tr>
                    <td colspan="3" class="text-end fw-bold text-dark">Assessment Class Averages:</td>
            `;
            assessments.forEach(ass => {
                const count = colCounts[ass];
                const avg = count > 0 ? (colSums[ass] / count).toFixed(1) : '&ndash;';
                footHtml += `<td class="text-center tabular-nums font-monospace text-primary fw-bold">${avg}</td>`;
            });
            const classTotalAvg = displayStudents.length > 0 ? (totalSum / displayStudents.length).toFixed(1) : '&ndash;';
            footHtml += `
                <td class="text-center tabular-nums font-monospace text-primary fw-bold fs-6">${classTotalAvg}</td>
                <td class="text-center text-muted small">&ndash;</td>
            </tr>
            `;
            tfoot.innerHTML = footHtml;
            tfoot.classList.remove('d-none');
        } else {
            tfoot.classList.add('d-none');
        }

        // 4. Update Summary Cards
        statsRow.classList.remove('d-none');
        document.getElementById('statStudentCount').textContent = students.length;
        document.getElementById('statAssessmentCount').textContent = assessments.length;
        document.getElementById('statClassAverage').textContent = students.length > 0 ? (totalSum / students.length).toFixed(1) : '-';
        document.getElementById('statHighestTotal').textContent = highestTotal >= 0 ? highestTotal.toFixed(1) : '-';
        badge.textContent = `${displayStudents.length} of ${students.length} Students`;
    }

    function filterGradebookTable() {
        if (!rawGradebookData) return;
        const q = document.getElementById('studentSearchInput').value;
        renderGradebook(rawGradebookData, q);
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>