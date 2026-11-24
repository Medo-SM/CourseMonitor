<?php
/**
 * CourseMonitor - Enter Coursework Grades
 * Gradebook interface with student assessment breakdown, live total auto-preview, and instant validation.
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

$courses_stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ? ORDER BY code ASC");
if (!$courses_stmt) {
    die("Error preparing statement: " . $mysqli->error);
}
$courses_stmt->bind_param("i", $lecturer_id);
$courses_stmt->execute();
$result = $courses_stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$courses_stmt->close();

$selectedCourseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$pageTitle = 'Enter Coursework Grades';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Top Links -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" 
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Dashboard
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/view_grades.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="viewGradebookLink"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-table me-1" aria-hidden="true"></i>Full Gradebook
            </a>
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/import_excel.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="importCsvLink"
               class="btn btn-secondary btn-sm">
                <i class="bi bi-file-earmark-arrow-up me-1" aria-hidden="true"></i>Import CSV
            </a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Enter Coursework Grades</h1>
            <p class="cm-page-subtitle">Record and update student assessment marks with assessment breakdown preview, live class average, and auto-computed totals.</p>
        </div>
    </div>

    <!-- Feedback Notification Banner -->
    <div id="feedbackAlert" class="alert d-none mb-4" role="alert" aria-live="polite">
        <div class="d-flex align-items-center gap-2 w-100">
            <i id="feedbackIcon" class="bi fs-5" aria-hidden="true"></i>
            <div id="feedbackMessage" class="flex-grow-1 small"></div>
            <button type="button" class="btn-close btn-sm" aria-label="Dismiss alert" onclick="dismissAlert()"></button>
        </div>
    </div>

    <!-- Assessment Selection Card -->
    <div class="cm-card mb-4">
        <div class="cm-card-header py-3">
            <h2 class="cm-card-title h6 mb-0">
                <i class="bi bi-sliders me-2 text-primary" aria-hidden="true"></i>Assessment Details
            </h2>
        </div>
        <div class="cm-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="course" class="form-label fw-medium">
                        Course <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="course" class="form-select" onchange="onCourseChanged()" required aria-required="true">
                        <option value="">-- Select Assigned Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= intval($course['id']) ?>" <?= ($selectedCourseId === intval($course['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['code'] . ' - ' . $course['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="assessment_type" class="form-label fw-medium">
                        Assessment Type <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="assessment_type" class="form-select" onchange="loadData()" required aria-required="true">
                        <option value="">-- Select Assessment Type --</option>
                        <option value="Assignment 1">Assignment 1</option>
                        <option value="Assignment 2">Assignment 2</option>
                        <option value="Quiz 1">Quiz 1</option>
                        <option value="Quiz 2">Quiz 2</option>
                        <option value="Midterm">Midterm Exam</option>
                        <option value="Final">Final Exam</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Grading Statistics Bar -->
    <div id="gradingStatsBar" class="cm-card mb-4 d-none">
        <div class="cm-card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 small text-secondary">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="fw-semibold text-dark">Grading Summary:</span>
                    <span>Graded: <span id="statGradedCount" class="badge cm-badge-primary tabular-nums">0 / 0</span></span>
                    <span>Class Average: <span id="statClassAvg" class="badge cm-badge-success tabular-nums">-</span></span>
                    <span>Highest: <span id="statHighest" class="badge cm-badge-info tabular-nums">-</span></span>
                    <span>Lowest: <span id="statLowest" class="badge cm-badge-neutral tabular-nums">-</span></span>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Allowed range: 0.00 to 100.00
                </div>
            </div>
        </div>
    </div>

    <!-- Student Grade Table Card -->
    <div class="cm-card">
        <div class="cm-card-header py-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h6 mb-0">
                    <i class="bi bi-person-lines-fill me-2 text-primary" aria-hidden="true"></i>Grade Entry Roster
                </h2>
                <span id="rosterCountBadge" class="badge cm-badge-neutral tabular-nums">Select course &amp; assessment</span>
            </div>
            <div id="quickFillActions" class="d-none d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllGradeInputs()" title="Clear unsubmitted grade fields">
                    <i class="bi bi-eraser me-1" aria-hidden="true"></i>Clear Inputs
                </button>
            </div>
        </div>
        <div class="cm-card-body p-0">
            <form id="gradeForm" onsubmit="saveGrades(event)">
                <div class="table-responsive" style="max-height: 70vh;">
                    <table class="table cm-table table-hover align-middle mb-0" id="gradeTable">
                        <thead class="table-light sticky-top">
                            <tr id="gradeTableHeaderRow">
                                <th scope="col" style="width: 50px;" class="text-center">#</th>
                                <th scope="col" style="width: 170px;" class="tabular-nums">Student ID</th>
                                <th scope="col" style="min-width: 180px;">Student Name</th>
                                <th scope="col" style="width: 180px;" class="text-center">Assessment Grade (0-100)</th>
                                <th scope="col" style="width: 130px;" class="text-center">Total Auto-Preview</th>
                                <th scope="col" style="width: 130px;" class="text-center">Performance</th>
                            </tr>
                        </thead>
                        <tbody id="gradeTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="cm-empty-state py-3">
                                        <div class="cm-empty-icon" aria-hidden="true">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </div>
                                        <h3 class="cm-empty-title h6">Select Course &amp; Assessment Type</h3>
                                        <p class="cm-empty-desc mb-0">Choose both an assigned course and an assessment type above to load the student grade entry table.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="gradeFormFooter" class="d-none cm-card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 px-4">
                    <div class="text-muted small" id="gradeStatusPrompt">
                        Press <kbd>Tab</kbd> or <kbd>Enter</kbd> to move rapidly between student score inputs.
                    </div>
                    <button type="submit" id="saveGradesBtn" class="btn btn-primary px-4">
                        <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>Save Grades
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let currentStudents = [];
    let gradebookOtherGrades = {}; // { student_id: { assessment: grade } }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('course').value) {
            onCourseChanged();
        }
    });

    function onCourseChanged() {
        const courseId = document.getElementById('course').value;
        const viewLink = document.getElementById('viewGradebookLink');
        const importLink = document.getElementById('importCsvLink');

        if (viewLink) {
            viewLink.href = '<?= htmlspecialchars(base_url('/modules/lecturer/view_grades.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
        if (importLink) {
            importLink.href = '<?= htmlspecialchars(base_url('/modules/lecturer/import_excel.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }

        // Prefetch gradebook data for auto-preview calculations
        if (courseId) {
            fetch(`<?= htmlspecialchars(base_url('/api/get_gradebook.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
                .then(r => r.json())
                .then(data => {
                    gradebookOtherGrades = data.grades || {};
                    if (document.getElementById('assessment_type').value) {
                        loadData();
                    }
                })
                .catch(() => {
                    gradebookOtherGrades = {};
                    if (document.getElementById('assessment_type').value) {
                        loadData();
                    }
                });
        } else {
            gradebookOtherGrades = {};
            loadData();
        }
    }

    function loadData() {
        const courseId = document.getElementById('course').value;
        const assessmentType = document.getElementById('assessment_type').value;
        const tbody = document.getElementById('gradeTableBody');
        const countBadge = document.getElementById('rosterCountBadge');
        const statsBar = document.getElementById('gradingStatsBar');
        const formFooter = document.getElementById('gradeFormFooter');
        const quickFill = document.getElementById('quickFillActions');

        if (!courseId || !assessmentType) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <h3 class="cm-empty-title h6">Select Course &amp; Assessment Type</h3>
                            <p class="cm-empty-desc mb-0">Choose both an assigned course and an assessment type above to load the student grade entry table.</p>
                        </div>
                    </td>
                </tr>
            `;
            countBadge.textContent = 'Select course & assessment';
            statsBar.classList.add('d-none');
            formFooter.classList.add('d-none');
            quickFill.classList.add('d-none');
            currentStudents = [];
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading student records...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Retrieving student enrollment roster and existing grades...</p>
                </td>
            </tr>
        `;
        countBadge.textContent = 'Loading...';

        // 1. Fetch Students
        fetch(`<?= htmlspecialchars(base_url('/api/get_students.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(students => {
                if (students.error) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="alert cm-alert-danger d-inline-flex align-items-center mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                                    <span>${escapeHtml(students.error)}</span>
                                </div>
                            </td>
                        </tr>
                    `;
                    countBadge.textContent = 'Error';
                    statsBar.classList.add('d-none');
                    formFooter.classList.add('d-none');
                    quickFill.classList.add('d-none');
                    return;
                }

                if (!Array.isArray(students) || students.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">No Enrolled Students</h3>
                                    <p class="cm-empty-desc mb-0">No students are currently enrolled in this course.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    countBadge.textContent = '0 students';
                    statsBar.classList.add('d-none');
                    formFooter.classList.add('d-none');
                    quickFill.classList.add('d-none');
                    return;
                }

                currentStudents = students;
                countBadge.textContent = `${students.length} ${students.length === 1 ? 'Student' : 'Students'}`;
                statsBar.classList.remove('d-none');
                formFooter.classList.remove('d-none');
                quickFill.classList.remove('d-none');

                // 2. Fetch Existing Assessment Grades
                fetch(`<?= htmlspecialchars(base_url('/api/get_coursework.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}&assessment_type=${encodeURIComponent(assessmentType)}`)
                    .then(r => r.json())
                    .then(existingGrades => {
                        renderTable(students, existingGrades || {});
                    })
                    .catch(() => {
                        renderTable(students, {});
                    });
            })
            .catch(err => {
                console.error('Error fetching students:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-danger">
                            <i class="bi bi-wifi-off me-2" aria-hidden="true"></i>Failed to load students due to a network error.
                        </td>
                    </tr>
                `;
                countBadge.textContent = 'Network error';
            });
    }

    function renderTable(students, existingGrades) {
        const tbody = document.getElementById('gradeTableBody');
        const assessmentType = document.getElementById('assessment_type').value;
        tbody.innerHTML = '';

        students.forEach((student, index) => {
            const sid = Number(student.id);
            const rawGrade = existingGrades[sid] !== undefined ? existingGrades[sid] : '';
            const gradeVal = rawGrade !== '' ? parseFloat(rawGrade).toFixed(2) : '';

            // Compute other coursework points for total auto-preview
            let otherAssessmentsTotal = 0;
            const studentAllGrades = gradebookOtherGrades[sid] || {};
            for (const [atype, val] of Object.entries(studentAllGrades)) {
                if (atype !== assessmentType && val !== undefined && val !== null && val !== '') {
                    otherAssessmentsTotal += parseFloat(val);
                }
            }

            const currentTotal = gradeVal !== '' ? (otherAssessmentsTotal + parseFloat(gradeVal)).toFixed(1) : otherAssessmentsTotal.toFixed(1);
            const performanceBadge = getPerformanceBadge(gradeVal !== '' ? parseFloat(gradeVal) : null);

            const row = document.createElement('tr');
            row.id = `row_student_${sid}`;
            row.innerHTML = `
                <td class="text-center text-muted tabular-nums small">${index + 1}</td>
                <td class="tabular-nums font-monospace fw-medium text-dark">
                    ${escapeHtml(student.student_id_number)}
                </td>
                <td class="fw-medium text-dark">
                    ${escapeHtml(student.name)}
                </td>
                <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-1">
                        <input type="number" 
                               name="grade[${sid}]" 
                               id="grade_input_${sid}"
                               min="0" 
                               max="100" 
                               step="0.01" 
                               value="${escapeHtml(gradeVal)}" 
                               class="form-control form-control-sm grade-input tabular-nums font-monospace text-center fw-medium" 
                               style="width: 110px;"
                               data-student-id="${sid}"
                               data-other-total="${otherAssessmentsTotal}"
                               aria-label="Grade for ${escapeHtml(student.name)}"
                               placeholder="&ndash;"
                               oninput="onGradeInputChange(this)">
                        <span class="small text-muted">/ 100</span>
                    </div>
                </td>
                <td class="text-center">
                    <span id="preview_total_${sid}" class="tabular-nums fw-bold text-dark font-monospace">
                        ${currentTotal}
                    </span>
                </td>
                <td class="text-center" id="perf_badge_${sid}">
                    ${performanceBadge}
                </td>
            `;
            tbody.appendChild(row);
        });

        recalculateStats();
    }

    function onGradeInputChange(input) {
        const valStr = input.value.trim();
        const sid = input.getAttribute('data-student-id');
        const otherTotal = parseFloat(input.getAttribute('data-other-total') || '0');
        const totalSpan = document.getElementById(`preview_total_${sid}`);
        const perfCell = document.getElementById(`perf_badge_${sid}`);

        if (valStr === '') {
            input.classList.remove('is-invalid');
            if (totalSpan) totalSpan.textContent = otherTotal.toFixed(1);
            if (perfCell) perfCell.innerHTML = getPerformanceBadge(null);
            recalculateStats();
            return;
        }

        const val = parseFloat(valStr);
        if (isNaN(val) || val < 0 || val > 100) {
            input.classList.add('is-invalid');
            if (perfCell) perfCell.innerHTML = '<span class="badge cm-badge-danger">Invalid (0-100)</span>';
        } else {
            input.classList.remove('is-invalid');
            if (totalSpan) totalSpan.textContent = (otherTotal + val).toFixed(1);
            if (perfCell) perfCell.innerHTML = getPerformanceBadge(val);
        }

        recalculateStats();
    }

    function getPerformanceBadge(score) {
        if (score === null || isNaN(score)) {
            return '<span class="text-muted tabular-nums small">&ndash;</span>';
        }
        if (score >= 80) {
            return '<span class="badge cm-badge-success tabular-nums"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Excellent</span>';
        } else if (score >= 65) {
            return '<span class="badge cm-badge-info tabular-nums">Good</span>';
        } else if (score >= 50) {
            return '<span class="badge cm-badge-warning tabular-nums">Pass</span>';
        } else {
            return '<span class="badge cm-badge-danger tabular-nums">At Risk</span>';
        }
    }

    function recalculateStats() {
        const inputs = document.querySelectorAll('.grade-input');
        const totalStudents = inputs.length;
        if (totalStudents === 0) return;

        let gradedCount = 0;
        let sum = 0;
        let highest = -1;
        let lowest = 101;

        inputs.forEach(inp => {
            const valStr = inp.value.trim();
            if (valStr !== '') {
                const num = parseFloat(valStr);
                if (!isNaN(num) && num >= 0 && num <= 100) {
                    gradedCount++;
                    sum += num;
                    if (num > highest) highest = num;
                    if (num < lowest) lowest = num;
                }
            }
        });

        document.getElementById('statGradedCount').textContent = `${gradedCount} / ${totalStudents}`;
        document.getElementById('statClassAvg').textContent = gradedCount > 0 ? (sum / gradedCount).toFixed(2) : '-';
        document.getElementById('statHighest').textContent = gradedCount > 0 ? highest.toFixed(1) : '-';
        document.getElementById('statLowest').textContent = gradedCount > 0 ? lowest.toFixed(1) : '-';

        const prompt = document.getElementById('gradeStatusPrompt');
        if (prompt) {
            if (gradedCount === totalStudents) {
                prompt.innerHTML = `<span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>All ${totalStudents} students scored. Ready to save.</span>`;
            } else {
                prompt.innerHTML = `<span><span class="text-dark fw-medium">${gradedCount}</span> of ${totalStudents} students graded (${totalStudents - gradedCount} pending).</span>`;
            }
        }
    }

    function clearAllGradeInputs() {
        if (!confirm('Are you sure you want to clear all grade inputs on this screen?')) return;
        const inputs = document.querySelectorAll('.grade-input');
        inputs.forEach(inp => {
            inp.value = '';
            inp.classList.remove('is-invalid');
            onGradeInputChange(inp);
        });
    }

    function saveGrades(event) {
        event.preventDefault();
        const courseId = document.getElementById('course').value;
        const assessmentType = document.getElementById('assessment_type').value;
        const submitBtn = document.getElementById('saveGradesBtn');

        if (!courseId || !assessmentType) {
            showAlert('danger', 'Please ensure both course and assessment type are selected.');
            return;
        }

        const inputs = document.querySelectorAll('.grade-input');
        const gradeData = [];
        let hasInvalid = false;
        let missingCount = 0;

        inputs.forEach(input => {
            const studentId = input.getAttribute('data-student-id');
            const valStr = input.value.trim();
            if (valStr === '') {
                missingCount++;
            } else {
                const num = parseFloat(valStr);
                if (isNaN(num) || num < 0 || num > 100) {
                    hasInvalid = true;
                    input.classList.add('is-invalid');
                } else {
                    gradeData.push({ student_id: Number(studentId), grade: num });
                }
            }
        });

        if (hasInvalid) {
            showAlert('danger', 'Please correct invalid grades highlighted in red (must be numbers between 0 and 100).');
            return;
        }

        if (gradeData.length === 0) {
            showAlert('warning', 'Please enter at least one valid grade before saving.');
            return;
        }

        if (missingCount > 0) {
            if (!confirm(`${missingCount} student(s) do not have a grade entered for ${assessmentType}. Proceed to save only the entered grades?`)) {
                return;
            }
        }

        submitBtn.disabled = true;
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving Grades...';

        fetch('<?= htmlspecialchars(base_url('/api/save_coursework.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                course_id: Number(courseId),
                assessment_type: assessmentType,
                grades: gradeData,
                csrf_token: '<?= generateCsrfToken(); ?>'
            })
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;

            if (data.success) {
                showAlert('success', `Grades saved successfully for ${gradeData.length} students! Assessment Class Average: ${data.average}`);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                // Refresh gradebook cache in background
                fetch(`<?= htmlspecialchars(base_url('/api/get_gradebook.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
                    .then(res => res.json())
                    .then(resData => { gradebookOtherGrades = resData.grades || {}; });
            } else {
                showAlert('danger', data.error || 'Failed to save coursework grades.');
            }
        })
        .catch(err => {
            console.error('Error saving coursework:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            showAlert('danger', 'Network error encountered while saving grades.');
        });
    }

    function showAlert(type, message) {
        const alertBox = document.getElementById('feedbackAlert');
        const icon = document.getElementById('feedbackIcon');
        const msg = document.getElementById('feedbackMessage');

        alertBox.className = `alert cm-alert-${type} mb-4`;
        icon.className = `bi fs-5 ${type === 'success' ? 'bi-check-circle-fill text-success' : (type === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-x-circle-fill text-danger')}`;
        msg.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function dismissAlert() {
        const alertBox = document.getElementById('feedbackAlert');
        if (alertBox) alertBox.classList.add('d-none');
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
