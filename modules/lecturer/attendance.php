<?php
/**
 * CourseMonitor - Take Class Attendance
 * Student Attendance & Grades Made Simple
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

// Fetch lecturer's courses
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
$pageTitle = 'Take Class Attendance';

$extraHead = '
<style>
/* Attendance Segmented Control Buttons (Eye-Friendly, Soft, High Contrast) */
.cm-attendance-group {
    display: flex;
    border-radius: var(--radius-md);
    background-color: var(--color-neutral-100);
    padding: 3px;
    gap: 3px;
}
.cm-att-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-sm);
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    min-height: 40px;
    border-radius: var(--radius-sm);
    border: 1px solid transparent;
    background-color: transparent;
    color: var(--cm-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
    user-select: none;
}
.cm-att-btn:hover {
    background-color: rgba(255, 255, 255, 0.7);
    color: var(--cm-text-primary);
}
/* Present Checked: Soft Emerald */
.btn-check:checked + .cm-att-present {
    background-color: var(--color-success-100) !important;
    color: var(--color-success-800) !important;
    border-color: var(--color-success-500) !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(22, 101, 52, 0.1);
}
/* Absent Checked: Soft Rose */
.btn-check:checked + .cm-att-absent {
    background-color: var(--color-danger-100) !important;
    color: var(--color-danger-800) !important;
    border-color: var(--color-danger-500) !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(153, 27, 27, 0.1);
}
/* Late Checked: Soft Amber */
.btn-check:checked + .cm-att-late {
    background-color: var(--color-warning-100) !important;
    color: var(--color-warning-800) !important;
    border-color: var(--color-warning-500) !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(146, 64, 14, 0.1);
}
.btn-check:focus-visible + .cm-att-btn {
    outline: 2px solid var(--cm-primary);
    outline-offset: 2px;
}
</style>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Top Bar Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" 
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to My Classes
        </a>
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/view_attendance.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
           id="viewLogLink"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Attendance History
        </a>
    </div>

    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="cm-page-tag">
                <i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Daily Tracking
            </div>
            <h1 class="cm-page-title">Take Class Attendance</h1>
            <p class="cm-page-subtitle">Choose a date and mark each student. You can also use 'Mark All Present' to save time.</p>
        </div>
    </div>

    <!-- Feedback Banner -->
    <div id="feedbackAlert" class="alert d-none mb-4" role="alert" aria-live="polite">
        <div class="d-flex align-items-center gap-2 w-100">
            <i id="feedbackIcon" class="bi fs-5" aria-hidden="true"></i>
            <div id="feedbackMessage" class="flex-grow-1 small"></div>
            <button type="button" class="btn-close btn-sm" aria-label="Dismiss notification" onclick="dismissAlert()"></button>
        </div>
    </div>

    <!-- Session Configuration Card -->
    <div class="cm-card mb-4">
        <div class="cm-card-header py-3">
            <h2 class="cm-card-title h6 mb-0">
                <i class="bi bi-sliders me-2 text-primary" aria-hidden="true"></i>Class &amp; Date
            </h2>
        </div>
        <div class="cm-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="course" class="form-label fw-medium">
                        Select Class <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="course" class="form-select" onchange="onCourseSelectChange()" required aria-required="true">
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= intval($course['id']) ?>" <?= ($selectedCourseId === intval($course['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['code'] . ' - ' . $course['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label fw-medium">
                        Attendance Date <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="date" 
                           id="date" 
                           class="form-control tabular-nums" 
                           value="<?= date('Y-m-d') ?>" 
                           max="<?= date('Y-m-d') ?>" 
                           required 
                           aria-required="true">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium d-none d-md-block">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" 
                                onclick="markAllStatus('Present')" 
                                class="btn btn-outline-success btn-sm flex-fill py-2" 
                                id="markAllPresentBtn"
                                title="Mark all students as Present">
                            <i class="bi bi-check2-all me-1" aria-hidden="true"></i>Mark All Present
                        </button>
                        <button type="button" 
                                onclick="markAllStatus('Absent')" 
                                class="btn btn-outline-danger btn-sm flex-fill py-2" 
                                id="markAllAbsentBtn"
                                title="Mark all students as Absent">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Mark All Absent
                        </button>
                        <button type="button" 
                                onclick="clearAllStatus()" 
                                class="btn btn-outline-secondary btn-sm py-2 px-3" 
                                title="Reset all status selections">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Session Tally Summary Strip -->
    <div id="sessionSummaryBar" class="cm-card mb-4 d-none">
        <div class="cm-card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 text-secondary small">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="fw-semibold">Attendance Tally:</span>
                    <span>Present: <span id="summaryPresent" class="badge cm-badge-success tabular-nums">0</span></span>
                    <span>Absent: <span id="summaryAbsent" class="badge cm-badge-danger tabular-nums">0</span></span>
                    <span>Late: <span id="summaryLate" class="badge cm-badge-warning tabular-nums">0</span></span>
                    <span>Unmarked: <span id="summaryUnmarked" class="badge cm-badge-neutral tabular-nums">0</span></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span>Enrolled: <span id="summaryTotal" class="badge cm-badge-primary tabular-nums">0</span></span>
                    <span>Attendance Rate: <span id="summaryRate" class="badge cm-badge-info tabular-nums">0%</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Attendance Marking Table -->
    <div class="cm-card">
        <div class="cm-card-header py-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h6 mb-0">
                    <i class="bi bi-people me-2 text-primary" aria-hidden="true"></i>Student Roster
                </h2>
                <span id="studentTableCount" class="badge cm-badge-neutral tabular-nums">Select a class</span>
            </div>
            <div class="text-muted small">
                Click or tap a status button for each student, or use keyboard arrow keys.
            </div>
        </div>
        <div class="cm-card-body p-0">
            <form id="attendanceForm" onsubmit="saveAttendance(event)">
                <div class="table-responsive">
                    <table class="table cm-table table-hover align-middle mb-0" id="studentTable">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 50px;" class="text-center">#</th>
                                <th scope="col" style="width: 170px;" class="tabular-nums">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col" style="width: 360px;" class="text-center">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <div class="cm-empty-state py-3">
                                        <div class="cm-empty-icon" aria-hidden="true">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </div>
                                        <h3 class="cm-empty-title h6">Please Select a Class</h3>
                                        <p class="cm-empty-desc mb-0">Choose your assigned class above to load enrolled students and mark attendance.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="formActionsRow" class="d-none cm-card-footer d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 px-4">
                    <div class="text-muted small" id="completionStatus">
                        Use <kbd>Tab</kbd> to move between students and <kbd>&larr;</kbd> <kbd>&rarr;</kbd> arrows to toggle status.
                    </div>
                    <button type="submit" id="saveSubmitBtn" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-cloud-arrow-up me-2" aria-hidden="true"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let currentStudents = [];

    document.addEventListener('DOMContentLoaded', () => {
        const courseSelect = document.getElementById('course');
        if (courseSelect.value) {
            loadStudents();
        }
    });

    function onCourseSelectChange() {
        const courseId = document.getElementById('course').value;
        const viewLink = document.getElementById('viewLogLink');
        if (viewLink) {
            viewLink.href = '<?= htmlspecialchars(base_url('/modules/lecturer/view_attendance.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
        loadStudents();
    }

    function loadStudents() {
        const courseId = document.getElementById('course').value;
        const tbody = document.getElementById('studentTableBody');
        const countSpan = document.getElementById('studentTableCount');
        const summaryBar = document.getElementById('sessionSummaryBar');
        const actionsRow = document.getElementById('formActionsRow');

        if (!courseId) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <h3 class="cm-empty-title h6">Please Select a Class</h3>
                            <p class="cm-empty-desc mb-0">Choose your assigned class above to load enrolled students and mark attendance.</p>
                        </div>
                    </td>
                </tr>
            `;
            countSpan.textContent = 'Select a class';
            summaryBar.classList.add('d-none');
            actionsRow.classList.add('d-none');
            currentStudents = [];
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading enrolled students...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Retrieving student enrollment roster...</p>
                </td>
            </tr>
        `;
        countSpan.textContent = 'Loading roster...';

        fetch(`<?= htmlspecialchars(base_url('/api/get_students.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                tbody.innerHTML = '';

                if (data.error) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="alert cm-alert-danger d-inline-flex align-items-center mb-0" role="alert">
                                    <i class="bi bi-exclamation-octagon-fill me-2" aria-hidden="true"></i>
                                    <span>${escapeHtml(data.error)}</span>
                                </div>
                            </td>
                        </tr>
                    `;
                    countSpan.textContent = 'Error';
                    summaryBar.classList.add('d-none');
                    actionsRow.classList.add('d-none');
                    currentStudents = [];
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-person-x"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">No Enrolled Students</h3>
                                    <p class="cm-empty-desc mb-0">There are no students currently enrolled in this class.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    countSpan.textContent = '0 students';
                    summaryBar.classList.add('d-none');
                    actionsRow.classList.add('d-none');
                    currentStudents = [];
                    return;
                }

                currentStudents = data;
                countSpan.textContent = `${data.length} ${data.length === 1 ? 'Student' : 'Students'}`;
                summaryBar.classList.remove('d-none');
                actionsRow.classList.remove('d-none');

                data.forEach((student, index) => {
                    const sid = Number(student.id);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center text-muted tabular-nums small">${index + 1}</td>
                        <td class="tabular-nums font-monospace fw-medium">
                            ${escapeHtml(student.student_id_number)}
                        </td>
                        <td class="fw-medium">
                            ${escapeHtml(student.name)}
                        </td>
                        <td>
                            <div class="cm-attendance-group" role="group" aria-label="Attendance for ${escapeHtml(student.name)}">
                                <input type="radio" 
                                       class="btn-check status-radio" 
                                       name="status[${sid}]" 
                                       id="present_${sid}" 
                                       value="Present" 
                                       required 
                                       data-sid="${sid}"
                                       onchange="updateSummaryCounts()">
                                <label class="cm-att-btn cm-att-present" for="present_${sid}">
                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Present
                                </label>

                                <input type="radio" 
                                       class="btn-check status-radio" 
                                       name="status[${sid}]" 
                                       id="absent_${sid}" 
                                       value="Absent" 
                                       data-sid="${sid}"
                                       onchange="updateSummaryCounts()">
                                <label class="cm-att-btn cm-att-absent" for="absent_${sid}">
                                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Absent
                                </label>

                                <input type="radio" 
                                       class="btn-check status-radio" 
                                       name="status[${sid}]" 
                                       id="late_${sid}" 
                                       value="Late" 
                                       data-sid="${sid}"
                                       onchange="updateSummaryCounts()">
                                <label class="cm-att-btn cm-att-late" for="late_${sid}">
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>Late
                                </label>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                updateSummaryCounts();
            })
            .catch(err => {
                console.error('Error fetching students:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-danger">
                            <i class="bi bi-wifi-off me-2" aria-hidden="true"></i>Network connection error while fetching students.
                        </td>
                    </tr>
                `;
                countSpan.textContent = 'Connection error';
                summaryBar.classList.add('d-none');
                actionsRow.classList.add('d-none');
            });
    }

    function markAllStatus(statusValue) {
        if (!currentStudents.length) return;
        const radios = document.querySelectorAll(`input.status-radio[value="${statusValue}"]`);
        radios.forEach(r => r.checked = true);
        updateSummaryCounts();
    }

    function clearAllStatus() {
        const radios = document.querySelectorAll('input.status-radio');
        radios.forEach(r => r.checked = false);
        updateSummaryCounts();
    }

    function updateSummaryCounts() {
        const total = currentStudents.length;
        if (total === 0) return;

        let present = 0, absent = 0, late = 0;
        const radios = document.querySelectorAll('input.status-radio:checked');
        
        radios.forEach(r => {
            if (r.value === 'Present') present++;
            else if (r.value === 'Absent') absent++;
            else if (r.value === 'Late') late++;
        });

        const marked = present + absent + late;
        const unmarked = total - marked;
        const rate = marked > 0 ? ((present / marked) * 100).toFixed(0) : 0;

        document.getElementById('summaryPresent').textContent = present;
        document.getElementById('summaryAbsent').textContent = absent;
        document.getElementById('summaryLate').textContent = late;
        document.getElementById('summaryUnmarked').textContent = unmarked;
        document.getElementById('summaryTotal').textContent = total;
        document.getElementById('summaryRate').textContent = `${rate}%`;

        const completionStatus = document.getElementById('completionStatus');
        if (completionStatus) {
            if (unmarked === 0) {
                completionStatus.innerHTML = `<span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>All ${total} students marked. Ready to save.</span>`;
            } else {
                completionStatus.innerHTML = `<span><span class="fw-medium">${marked}</span> of ${total} students marked (${unmarked} remaining).</span>`;
            }
        }
    }

    function saveAttendance(event) {
        event.preventDefault();
        const courseId = document.getElementById('course').value;
        const dateStr = document.getElementById('date').value;
        const submitBtn = document.getElementById('saveSubmitBtn');

        if (!courseId || !dateStr) {
            showAlert('danger', 'Please choose both a class and a valid date.');
            return;
        }

        const formData = new FormData(event.target);
        const attendanceData = [];

        for (let [key, value] of formData.entries()) {
            const match = key.match(/status\[(\d+)\]/);
            if (match) {
                attendanceData.push({ student_id: Number(match[1]), status: value });
            }
        }

        if (attendanceData.length === 0) {
            showAlert('warning', 'Please mark attendance for at least one student before saving.');
            return;
        }

        if (attendanceData.length < currentStudents.length) {
            const diff = currentStudents.length - attendanceData.length;
            if (!confirm(`Warning: ${diff} student(s) have not been marked. Do you want to proceed saving attendance for the marked students?`)) {
                return;
            }
        }

        submitBtn.disabled = true;
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';

        fetch('<?= htmlspecialchars(base_url('/api/save_attendance.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                course_id: courseId,
                date: dateStr,
                attendance: attendanceData,
                csrf_token: '<?= generateCsrfToken(); ?>'
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;

            if (data.success) {
                showAlert('success', `Attendance successfully saved for ${attendanceData.length} students on ${escapeHtml(dateStr)}.`);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                showAlert('danger', data.error || 'Failed to save attendance records.');
            }
        })
        .catch(err => {
            console.error('Error saving attendance:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            showAlert('danger', 'Network error. Please check your connection and retry.');
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