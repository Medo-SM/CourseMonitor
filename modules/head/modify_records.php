<?php
// modules/head/modify_records.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$head_id = $user['user_id'];

// Get Head's Dept Courses
$courses_stmt = $mysqli->prepare("
    SELECT c.id, c.code, c.name 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    WHERE d.head_id = ?
    ORDER BY c.code ASC
");
$courses_stmt->bind_param("i", $head_id);
$courses_stmt->execute();
$result = $courses_stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$courses_stmt->close();

$pageTitle = 'Review & Modify Records';
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
                <span class="text-secondary small fw-medium">Department Head Moderation</span>
            </div>
            <h1 class="cm-page-title">Review &amp; Modify Records</h1>
            <p class="cm-page-subtitle">Inspect student grade assessments and attendance entries with verified audit logging</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars(base_url('/modules/head/audit_logs.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>View Modification History</span>
            </a>
        </div>
    </div>

    <!-- Governance Banner -->
    <div class="alert cm-alert-info d-flex align-items-start gap-3 mb-4" role="region" aria-label="Governance notice">
        <i class="bi bi-shield-check fs-5 text-primary flex-shrink-0 mt-1" aria-hidden="true"></i>
        <div>
            <div class="fw-semibold text-primary mb-1">Academic Governance &amp; Audit Trail Requirement</div>
            <p class="mb-0 text-secondary small">
                All alterations to student assessment scores or attendance status are permanently preserved in the institutional audit log.
                A clear, documented reason is required for every modification prior to approval.
            </p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card cm-card mb-4">
        <div class="cm-card-header bg-light">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-secondary" aria-hidden="true"></i>
                <h2 class="cm-card-title h6 mb-0">Record Selection &amp; Criteria</h2>
            </div>
        </div>
        <div class="cm-card-body p-4">
            <form id="filterForm" onsubmit="loadRecords(event)" class="row g-3 align-items-end">
                <!-- Course Select -->
                <div class="col-md-5">
                    <label for="course_id" class="form-label">
                        Department Course <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="course_id" class="form-select" onchange="handleCourseChange()" required>
                        <option value="">Select Course...</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int)$c['id'] ?>">
                                <?= htmlspecialchars($c['code'] . ' &ndash; ' . $c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Record Type Select -->
                <div class="col-md-3">
                    <label for="record_type" class="form-label">
                        Record Category <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="record_type" class="form-select" onchange="handleTypeChange()" required>
                        <option value="attendance">Attendance Records</option>
                        <option value="coursework">Coursework &amp; Grades</option>
                    </select>
                </div>

                <!-- Assessment Type (Conditional for Coursework) -->
                <div class="col-md-4" id="assessmentFilterDiv" style="display: none;">
                    <label for="assessment_filter" class="form-label">Assessment Component</label>
                    <select id="assessment_filter" class="form-select" onchange="loadRecords()">
                        <option value="">All Assessment Types</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Toast / Feedback -->
    <div id="actionFeedback" class="alert cm-alert-success d-none mb-4" role="alert">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
                <span id="feedbackMessage">Record modified successfully.</span>
            </div>
            <a href="<?= htmlspecialchars(base_url('/modules/head/audit_logs.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success btn-sm">
                View in Audit Log
            </a>
        </div>
    </div>

    <!-- Records Table Card -->
    <div class="card cm-card">
        <div class="cm-card-header flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h5 mb-0" id="tableHeaderTitle">Course Records</h2>
                <span class="badge cm-badge-neutral tabular-nums" id="recordsBadge">0 records</span>
            </div>

            <!-- Student Search Filter (Client-side) -->
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-light border-end-0 text-muted" id="search-icon">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </span>
                <input type="search" id="studentSearch" class="form-control border-start-0" 
                       placeholder="Filter by student name or ID..." 
                       aria-label="Filter loaded records by student"
                       aria-describedby="search-icon"
                       oninput="filterLoadedRecords()">
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0 rounded-0">
                <table class="table cm-table table-hover align-middle mb-0" id="recordsTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 25%;">Student Name</th>
                            <th scope="col" style="width: 15%;">Student ID</th>
                            <th scope="col" style="width: 25%;" id="contextColHeader">Session Date</th>
                            <th scope="col" style="width: 20%;" id="valueColHeader">Current Value</th>
                            <th scope="col" class="text-end" style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recordsBody">
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="cm-empty-state py-3">
                                    <div class="cm-empty-icon" aria-hidden="true">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <h3 class="cm-empty-title h6">Select a Course to Inspect</h3>
                                    <p class="cm-empty-desc mb-0">Choose a course and record category from the filters above to retrieve student records for review.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Dynamic Empty State -->
            <div id="noRecordsState" class="cm-empty-state d-none py-5">
                <div class="cm-empty-icon" aria-hidden="true">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="cm-empty-title h6">No Records Found</h3>
                <p class="cm-empty-desc mb-0">No entries match the selected course and category criteria. Check if records have been published or try a different filter.</p>
            </div>
        </div>
    </div>
</div>

<!-- Comparison & Approval Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-xl);">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="cm-stat-icon warning" style="width: 32px; height: 32px; font-size: 1rem;" aria-hidden="true">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h2 class="modal-title h5 mb-0" id="editModalTitle">Modify Academic Record</h2>
                        <span class="text-secondary small" id="editModalSubtitle">Review original value and submit audited change</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>

            <form id="modifyForm" onsubmit="saveModification(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="editId">
                    <input type="hidden" id="editType">

                    <!-- Modal Validation Error Banner -->
                    <div id="modalErrorAlert" class="alert cm-alert-danger d-none mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
                        <span id="modalErrorMsg">Please correct the inputs below.</span>
                    </div>

                    <!-- Student Context Card -->
                    <div class="p-3 mb-4 rounded border bg-light">
                        <div class="row g-2 align-items-center">
                            <div class="col-sm-6">
                                <span class="text-secondary small d-block">Student</span>
                                <span class="fw-bold text-dark" id="modalStudentName">&mdash;</span>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <span class="text-secondary small d-block">University ID</span>
                                <span class="font-monospace fw-semibold tabular-nums text-primary" id="modalStudentId">&mdash;</span>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top border-light-subtle d-flex justify-content-between align-items-center">
                                <span class="text-secondary small" id="modalContextLabel">Item Context:</span>
                                <span class="badge cm-badge-neutral font-monospace" id="modalContextVal">&mdash;</span>
                            </div>
                        </div>
                    </div>

                    <!-- Side-by-Side Comparison Container -->
                    <div class="card mb-4 border border-light-subtle">
                        <div class="card-header bg-light-subtle py-2 px-3">
                            <span class="small fw-bold text-secondary text-uppercase tracking-wider">Audit Comparison View</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-center">
                                <!-- Original (Before) -->
                                <div class="col-md-5">
                                    <div class="p-3 rounded border bg-light text-center h-100">
                                        <span class="text-secondary small d-block mb-1">Current Registered Value</span>
                                        <div class="fs-4 fw-bold text-danger text-decoration-line-through tabular-nums" id="originalValueDisplay">
                                            &mdash;
                                        </div>
                                        <span class="text-muted small" id="originalDateDisplay"></span>
                                    </div>
                                </div>

                                <!-- Arrow Divider -->
                                <div class="col-md-2 text-center text-secondary fs-3" aria-hidden="true">
                                    <i class="bi bi-arrow-right d-none d-md-inline"></i>
                                    <i class="bi bi-arrow-down d-md-none"></i>
                                </div>

                                <!-- Proposed (After) Input -->
                                <div class="col-md-5">
                                    <div class="p-3 rounded border border-primary-subtle bg-primary-subtle text-center h-100">
                                        <span class="text-primary small d-block mb-1 fw-semibold">Proposed New Value</span>
                                        <div id="valueInputContainer">
                                            <!-- Dynamic inputs injected by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory Audit Reason / Remarks -->
                    <div class="mb-3">
                        <label for="editReason" class="form-label">
                            Official Reason for Modification <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea id="editReason" class="form-control" rows="3" 
                                  placeholder="Provide clear academic justification (e.g. 'Grade recalculated following regrade request approved by committee', 'Medical excuse verified for absence')..." 
                                  required minlength="5"></textarea>
                        <div class="form-text d-flex justify-content-between">
                            <span>Required for institutional compliance. Preserved in audit records.</span>
                            <span class="text-muted" id="reasonCharCounter">Minimum 5 characters</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="submitModificationBtn">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span>Confirm &amp; Record Modification</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let editModalInstance = null;
    let currentLoadedRecords = [];
    let currentEditingRow = null;

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('editModal');
        if (modalEl) {
            editModalInstance = new bootstrap.Modal(modalEl);
        }
    });

    function handleCourseChange() {
        const courseId = document.getElementById('course_id').value;
        const type = document.getElementById('record_type').value;
        if (type === 'coursework') {
            loadAssessmentTypes();
        }
        if (courseId) {
            loadRecords();
        }
    }

    function handleTypeChange() {
        const type = document.getElementById('record_type').value;
        const assessDiv = document.getElementById('assessmentFilterDiv');
        const contextHeader = document.getElementById('contextColHeader');
        const valueHeader = document.getElementById('valueColHeader');

        if (type === 'attendance') {
            assessDiv.style.display = 'none';
            contextHeader.textContent = 'Session Date';
            valueHeader.textContent = 'Attendance Status';
        } else {
            assessDiv.style.display = 'block';
            contextHeader.textContent = 'Assessment Component';
            valueHeader.textContent = 'Recorded Grade';
            loadAssessmentTypes();
        }
        
        const courseId = document.getElementById('course_id').value;
        if (courseId) {
            loadRecords();
        }
    }

    function loadAssessmentTypes() {
        const courseId = document.getElementById('course_id').value;
        const select = document.getElementById('assessment_filter');
        select.innerHTML = '<option value="">All Assessment Types</option>';
        if (!courseId) return;

        fetch(`<?= htmlspecialchars(base_url('/api/get_assessment_types.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`)
            .then(res => res.json())
            .then(types => {
                if (Array.isArray(types)) {
                    types.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t;
                        opt.textContent = t;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Failed to load assessment types:', err));
    }

    function loadRecords(event) {
        if (event) event.preventDefault();
        const courseId = document.getElementById('course_id').value;
        const type = document.getElementById('record_type').value;
        const tbody = document.getElementById('recordsBody');
        const emptyState = document.getElementById('noRecordsState');
        const recordsBadge = document.getElementById('recordsBadge');

        if (!courseId) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="cm-empty-state py-3">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-search"></i></div>
                            <h3 class="cm-empty-title h6">Select a Course to Inspect</h3>
                            <p class="cm-empty-desc mb-0">Choose a course above to view student attendance entries or coursework assessments.</p>
                        </div>
                    </td>
                </tr>
            `;
            emptyState.classList.add('d-none');
            recordsBadge.textContent = '0 records';
            return;
        }

        let filterVal = '';
        if (type === 'coursework') {
            filterVal = document.getElementById('assessment_filter').value;
        }

        let url = `<?= htmlspecialchars(base_url('/api/search_records.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}&type=${encodeURIComponent(type)}`;
        if (filterVal) {
            url += `&filter_value=${encodeURIComponent(filterVal)}`;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-secondary small">Retrieving student records...</span>
                </td>
            </tr>
        `;
        emptyState.classList.add('d-none');

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-4 text-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i> ${escapeHtml(data.error)}
                            </td>
                        </tr>
                    `;
                    recordsBadge.textContent = '0 records';
                    return;
                }

                currentLoadedRecords = Array.isArray(data) ? data : [];
                recordsBadge.textContent = `${currentLoadedRecords.length} record${currentLoadedRecords.length === 1 ? '' : 's'}`;
                renderRecordRows(currentLoadedRecords);
            })
            .catch(err => {
                console.error('Failed to load records:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Failed to communicate with server.
                        </td>
                    </tr>
                `;
            });
    }

    function filterLoadedRecords() {
        const query = document.getElementById('studentSearch').value.trim().toLowerCase();
        if (!query) {
            renderRecordRows(currentLoadedRecords);
            return;
        }

        const filtered = currentLoadedRecords.filter(row => {
            const nameMatch = row.student_name && row.student_name.toLowerCase().includes(query);
            const idMatch = row.student_id_number && String(row.student_id_number).toLowerCase().includes(query);
            return nameMatch || idMatch;
        });

        renderRecordRows(filtered);
    }

    function renderRecordRows(records) {
        const tbody = document.getElementById('recordsBody');
        const emptyState = document.getElementById('noRecordsState');
        const type = document.getElementById('record_type').value;

        tbody.innerHTML = '';

        if (records.length === 0) {
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');

        records.forEach(row => {
            const tr = document.createElement('tr');
            const context = type === 'attendance' ? row.date : row.assessment_type;

            // Formatted Value Badge
            let valueHtml = '';
            if (type === 'attendance') {
                let badgeClass = 'cm-badge-neutral';
                let iconClass = 'bi-circle';
                if (row.value === 'Present') {
                    badgeClass = 'cm-badge-success';
                    iconClass = 'bi-check-circle-fill';
                } else if (row.value === 'Absent') {
                    badgeClass = 'cm-badge-danger';
                    iconClass = 'bi-x-circle-fill';
                } else if (row.value === 'Late') {
                    badgeClass = 'cm-badge-warning';
                    iconClass = 'bi-clock-fill';
                }
                valueHtml = `<span class="badge ${badgeClass}"><i class="bi ${iconClass} me-1" aria-hidden="true"></i>${escapeHtml(row.value)}</span>`;
            } else {
                const numericGrade = parseFloat(row.value);
                const gradeClass = numericGrade >= 70 ? 'text-success' : (numericGrade >= 50 ? 'text-primary' : 'text-danger');
                valueHtml = `<span class="fw-bold tabular-nums fs-6 ${gradeClass}">${escapeHtml(String(row.value))}</span> <span class="text-muted small">/ 100</span>`;
            }

            tr.innerHTML = `
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(row.student_name)}</div>
                </td>
                <td>
                    <span class="font-monospace text-secondary small tabular-nums">${escapeHtml(row.student_id_number)}</span>
                </td>
                <td>
                    <span class="badge cm-badge-neutral font-monospace">${escapeHtml(context)}</span>
                </td>
                <td>${valueHtml}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm edit-record-btn" aria-label="Modify record for ${escapeHtml(row.student_name)}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                        <span>Modify</span>
                    </button>
                </td>
            `;

            tr.querySelector('.edit-record-btn').addEventListener('click', () => openEdit(row));
            tbody.appendChild(tr);
        });
    }

    function openEdit(row) {
        currentEditingRow = row;
        const type = document.getElementById('record_type').value;

        document.getElementById('editId').value = row.id;
        document.getElementById('editType').value = type;
        document.getElementById('editReason').value = '';
        document.getElementById('modalErrorAlert').classList.add('d-none');

        // Set Student Header Details
        document.getElementById('modalStudentName').textContent = row.student_name;
        document.getElementById('modalStudentId').textContent = row.student_id_number;
        document.getElementById('modalContextLabel').textContent = type === 'attendance' ? 'Session Date:' : 'Assessment Component:';
        document.getElementById('modalContextVal').textContent = type === 'attendance' ? row.date : row.assessment_type;

        // Set Comparison Before View
        document.getElementById('originalValueDisplay').textContent = row.value;
        document.getElementById('originalDateDisplay').textContent = type === 'attendance' ? `Recorded Date: ${row.date}` : '';

        // Build Dynamic Input
        const container = document.getElementById('valueInputContainer');
        if (type === 'attendance') {
            container.innerHTML = `
                <div class="mb-2 text-start">
                    <label for="editDate" class="form-label small mb-1">Session Date</label>
                    <input type="date" id="editDate" class="form-control form-control-sm tabular-nums" value="${escapeHtml(row.date)}" required>
                </div>
                <div class="text-start">
                    <label for="newValue" class="form-label small mb-1">New Attendance Status</label>
                    <select id="newValue" class="form-select form-select-sm fw-bold">
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                    </select>
                </div>
            `;
            const sel = document.getElementById('newValue');
            sel.value = row.value;
        } else {
            container.innerHTML = `
                <div class="text-start">
                    <label for="newValue" class="form-label small mb-1">Adjusted Grade (0 - 100)</label>
                    <input type="number" id="newValue" class="form-control form-control-lg text-center fw-bold tabular-nums" 
                           step="0.01" min="0" max="100" value="${escapeHtml(String(row.value))}" required>
                </div>
            `;
        }

        if (editModalInstance) {
            editModalInstance.show();
        }
    }

    function saveModification(event) {
        event.preventDefault();
        const errorAlert = document.getElementById('modalErrorAlert');
        const errorMsg = document.getElementById('modalErrorMsg');
        errorAlert.classList.add('d-none');

        const id = document.getElementById('editId').value;
        const type = document.getElementById('editType').value;
        const newValueEl = document.getElementById('newValue');
        const newValue = newValueEl ? newValueEl.value.trim() : '';
        const reason = document.getElementById('editReason').value.trim();

        let newDate = null;
        if (type === 'attendance') {
            const dateEl = document.getElementById('editDate');
            newDate = dateEl ? dateEl.value : null;
        }

        if (!reason || reason.length < 5) {
            errorMsg.textContent = 'Please enter a valid justification reason (at least 5 characters).';
            errorAlert.classList.remove('d-none');
            return;
        }

        if (type === 'coursework') {
            const g = parseFloat(newValue);
            if (isNaN(g) || g < 0 || g > 100) {
                errorMsg.textContent = 'Please provide a valid grade score between 0.00 and 100.00.';
                errorAlert.classList.remove('d-none');
                return;
            }
        }

        const submitBtn = document.getElementById('submitModificationBtn');
        submitBtn.disabled = true;

        const payload = {
            id: id,
            type: type,
            new_value: newValue,
            new_date: newDate,
            reason: reason,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        fetch('<?= htmlspecialchars(base_url('/api/update_record_audit.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(resp => {
            submitBtn.disabled = false;
            if (resp.success) {
                if (editModalInstance) {
                    editModalInstance.hide();
                }
                // Show feedback alert
                const feedback = document.getElementById('actionFeedback');
                const feedbackMsg = document.getElementById('feedbackMessage');
                feedbackMsg.textContent = `Record updated successfully: Changed to "${newValue}". Logged to audit trail.`;
                feedback.classList.remove('d-none');
                feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Refresh records
                loadRecords();
            } else {
                errorMsg.textContent = resp.error || 'Failed to modify record. Please verify input data.';
                errorAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            console.error('Error saving modification:', err);
            errorMsg.textContent = 'Network or server error encountered while submitting modification.';
            errorAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
