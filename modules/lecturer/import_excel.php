<?php
/**
 * CourseMonitor - Import Grades (CSV)
 * Polished batch grade upload interface with dropzone, template generator, and validation table.
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$user_id = $_SESSION['user_id'];

// Get Lecturer's Courses
$stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ? ORDER BY code ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$courses = [];
while ($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

$selectedCourseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$pageTitle = 'Import Grades via CSV';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars(base_url('/modules/lecturer/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" 
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Dashboard
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/coursework.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="enterGradesLink"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Enter Grades Online
            </a>
            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/view_grades.php' . ($selectedCourseId ? '?course_id=' . $selectedCourseId : '')), ENT_QUOTES, 'UTF-8') ?>" 
               id="viewGradesLink"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-table me-1" aria-hidden="true"></i>Full Gradebook
            </a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Import Assessment Grades</h1>
            <p class="cm-page-subtitle">Batch upload student scores via standardized CSV spreadsheets with automated ID verification and error auditing.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Instructions & Format Guidelines -->
        <div class="col-lg-5">
            <!-- Instructions Card -->
            <div class="cm-card mb-4">
                <div class="cm-card-header py-3">
                    <h2 class="cm-card-title h6 mb-0">
                        <i class="bi bi-info-circle me-2 text-primary" aria-hidden="true"></i>Import Instructions
                    </h2>
                </div>
                <div class="cm-card-body">
                    <ol class="ps-3 mb-0 text-secondary small d-flex flex-column gap-2">
                        <li>
                            <strong class="text-dark">Select Course &amp; Assessment:</strong> Choose which assigned course and assessment component (e.g. Midterm, Quiz 1) you are importing.
                        </li>
                        <li>
                            <strong class="text-dark">Download Pre-filled Template:</strong> Click the <em>Download Template</em> button to obtain a customized CSV file with all currently enrolled students pre-populated.
                        </li>
                        <li>
                            <strong class="text-dark">Enter Student Marks:</strong> Fill in the scores (0.00 to 100.00) in the third column (<code>Grade</code>) of the spreadsheet.
                        </li>
                        <li>
                            <strong class="text-dark">Upload &amp; Verify:</strong> Drag and drop the completed <code>.csv</code> file into the upload zone and submit. Review any validation notices immediately.
                        </li>
                    </ol>
                </div>
            </div>

            <!-- CSV Format Guidelines Card -->
            <div class="cm-card">
                <div class="cm-card-header py-3">
                    <h2 class="cm-card-title h6 mb-0">
                        <i class="bi bi-file-spreadsheet me-2 text-primary" aria-hidden="true"></i>CSV Format Guidelines
                    </h2>
                </div>
                <div class="cm-card-body">
                    <p class="text-muted small mb-2">The CSV file must match the following three-column header structure:</p>
                    <div class="table-responsive mb-3">
                        <table class="table cm-table table-bordered mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="tabular-nums">Student Number</th>
                                    <th scope="col">Student Name</th>
                                    <th scope="col" class="text-end tabular-nums">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-monospace tabular-nums">S10245</td>
                                    <td>Alice Johnson</td>
                                    <td class="text-end font-monospace tabular-nums text-success fw-bold">88.50</td>
                                </tr>
                                <tr>
                                    <td class="font-monospace tabular-nums">S10246</td>
                                    <td>Bob Smith</td>
                                    <td class="text-end font-monospace tabular-nums text-success fw-bold">92.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert cm-alert-info py-2 px-3 mb-0 small">
                        <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
                        <span>Student IDs must match enrolled course records. Empty grade cells will be skipped.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Upload Form & Dropzone -->
        <div class="col-lg-7">
            <div class="cm-card">
                <div class="cm-card-header py-3">
                    <h2 class="cm-card-title h6 mb-0">
                        <i class="bi bi-cloud-arrow-up me-2 text-primary" aria-hidden="true"></i>Upload Spreadsheet
                    </h2>
                </div>
                <div class="cm-card-body p-4">
                    <form id="importForm" onsubmit="uploadFile(event)">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Course Select -->
                        <div class="mb-3">
                            <label for="course_id" class="form-label fw-medium">
                                Target Course <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <select id="course_id" name="course_id" class="form-select" onchange="onCourseSelected()" required aria-required="true">
                                <option value="">-- Select Assigned Course --</option>
                                <?php foreach($courses as $c): ?>
                                    <option value="<?= intval($c['id']) ?>" <?= ($selectedCourseId === intval($c['id'])) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Assessment Type Select -->
                        <div class="mb-4">
                            <label for="assessment_type" class="form-label fw-medium">
                                Assessment Type <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <select id="assessment_type" name="assessment_type" class="form-select" required aria-required="true">
                                <option value="">-- Select Assessment Type --</option>
                                <option value="Assignment 1">Assignment 1</option>
                                <option value="Assignment 2">Assignment 2</option>
                                <option value="Quiz 1">Quiz 1</option>
                                <option value="Quiz 2">Quiz 2</option>
                                <option value="Midterm">Midterm Exam</option>
                                <option value="Final">Final Exam</option>
                            </select>
                            <div class="form-text">All grades extracted from this file will be registered under this assessment type.</div>
                        </div>

                        <!-- Modern Dropzone -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">
                                CSV Spreadsheet File <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <div id="dropzoneContainer" 
                                 class="p-4 border border-2 border-dashed rounded-3 text-center bg-slate-50 transition-colors"
                                 style="border-color: var(--cm-border-default); cursor: pointer;"
                                 onclick="triggerFileInput()"
                                 ondragover="handleDragOver(event)"
                                 ondragleave="handleDragLeave(event)"
                                 ondrop="handleFileDrop(event)">
                                <input type="file" 
                                       id="fileInput" 
                                       name="file" 
                                       class="d-none" 
                                       accept=".csv,text/csv" 
                                       required 
                                       aria-label="Select CSV file for upload"
                                       onchange="handleFileSelected(event)">
                                
                                <div id="dropzonePrompt">
                                    <div class="mb-2 text-primary fs-1">
                                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <p class="fw-semibold text-dark mb-1">Drag and drop your completed CSV file here</p>
                                    <p class="text-muted small mb-3">or click to browse your computer files</p>
                                    <span class="badge cm-badge-neutral font-monospace">Accepts .CSV format only</span>
                                </div>

                                <div id="fileSelectedDisplay" class="d-none align-items-center justify-content-center gap-2 py-2">
                                    <i class="bi bi-file-earmark-spreadsheet text-success fs-3" aria-hidden="true"></i>
                                    <div class="text-start">
                                        <div id="selectedFileName" class="fw-semibold text-dark text-truncate" style="max-width: 280px;">filename.csv</div>
                                        <div id="selectedFileSize" class="text-muted small tabular-nums font-monospace">0 KB</div>
                                    </div>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm ms-3" 
                                            onclick="clearSelectedFile(event)" 
                                            title="Remove selected file">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm" 
                                    onclick="downloadTemplate(event)"
                                    title="Download pre-filled CSV template for selected course">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>Download Class Template
                            </button>
                            <button type="submit" id="submitImportBtn" class="btn btn-primary px-4">
                                <i class="bi bi-upload me-1" aria-hidden="true"></i>Import Grades
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Dynamic Result Area -->
            <div id="resultArea" class="mt-4 d-none" aria-live="polite">
                <div class="cm-card">
                    <div class="cm-card-header py-3">
                        <h3 class="cm-card-title h6 mb-0">
                            <i class="bi bi-card-checklist me-2 text-primary" aria-hidden="true"></i>Import Results
                        </h3>
                    </div>
                    <div class="cm-card-body p-4">
                        <div id="resultMessage" class="mb-3"></div>
                        <div id="errorSection" class="d-none">
                            <h4 class="h6 text-danger fw-semibold mb-2">Validation Errors Found:</h4>
                            <div class="table-responsive">
                                <table class="table cm-table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 50px;" class="text-center">#</th>
                                            <th scope="col">Error Description / Unmatched Record</th>
                                        </tr>
                                    </thead>
                                    <tbody id="errorTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    function triggerFileInput() {
        document.getElementById('fileInput').click();
    }

    function onCourseSelected() {
        const courseId = document.getElementById('course_id').value;
        const enterLink = document.getElementById('enterGradesLink');
        const viewLink = document.getElementById('viewGradesLink');

        if (enterLink) {
            enterLink.href = '<?= htmlspecialchars(base_url('/modules/lecturer/coursework.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
        if (viewLink) {
            viewLink.href = '<?= htmlspecialchars(base_url('/modules/lecturer/view_grades.php'), ENT_QUOTES, 'UTF-8') ?>' + 
                (courseId ? '?course_id=' + encodeURIComponent(courseId) : '');
        }
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('dropzoneContainer');
        dropzone.style.borderColor = 'var(--cm-primary)';
        dropzone.style.backgroundColor = 'var(--color-primary-50)';
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('dropzoneContainer');
        dropzone.style.borderColor = 'var(--cm-border-default)';
        dropzone.style.backgroundColor = 'var(--cm-bg-subtle)';
    }

    function handleFileDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        handleDragLeave(e);

        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            const file = files[0];
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please upload a file in .CSV format.');
                return;
            }
            const input = document.getElementById('fileInput');
            input.files = files;
            displaySelectedFileInfo(file);
        }
    }

    function handleFileSelected(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            displaySelectedFileInfo(files[0]);
        }
    }

    function displaySelectedFileInfo(file) {
        document.getElementById('dropzonePrompt').classList.add('d-none');
        const display = document.getElementById('fileSelectedDisplay');
        display.classList.remove('d-none');
        display.classList.add('d-flex');

        document.getElementById('selectedFileName').textContent = file.name;
        const kb = (file.size / 1024).toFixed(1);
        document.getElementById('selectedFileSize').textContent = `${kb} KB`;
    }

    function clearSelectedFile(e) {
        e.stopPropagation();
        const input = document.getElementById('fileInput');
        input.value = '';
        document.getElementById('fileSelectedDisplay').classList.remove('d-flex');
        document.getElementById('fileSelectedDisplay').classList.add('d-none');
        document.getElementById('dropzonePrompt').classList.remove('d-none');
    }

    function downloadTemplate(event) {
        event.preventDefault();
        const courseId = document.getElementById('course_id').value;
        
        if (!courseId) {
            alert("Please select a target course before downloading the student template.");
            document.getElementById('course_id').focus();
            return;
        }

        window.location.href = `<?= htmlspecialchars(base_url('/api/download_grade_template.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${encodeURIComponent(courseId)}`;
    }

    function uploadFile(event) {
        event.preventDefault();
        const form = document.getElementById('importForm');
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitImportBtn');

        const resultArea = document.getElementById('resultArea');
        const resultMsg = document.getElementById('resultMessage');
        const errorSection = document.getElementById('errorSection');
        const errorTbody = document.getElementById('errorTableBody');
        
        resultArea.classList.remove('d-none');
        errorSection.classList.add('d-none');
        errorTbody.innerHTML = '';
        resultMsg.innerHTML = `
            <div class="d-flex align-items-center gap-2 text-primary py-2">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Validating and processing spreadsheet data...</span>
            </div>
        `;

        submitBtn.disabled = true;
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';

        fetch('<?= htmlspecialchars(base_url('/api/import_grades.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(resp => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;

            if (resp.success) {
                const count = Number(resp.count);
                resultMsg.innerHTML = `
                    <div class="alert cm-alert-success d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-check-circle-fill text-success fs-5" aria-hidden="true"></i>
                        <div>
                            <strong>Import Complete!</strong> Successfully imported and recorded 
                            <span class="badge cm-badge-success tabular-nums fs-6">${count}</span> student grades.
                        </div>
                    </div>
                `;

                if (resp.errors && resp.errors.length > 0) {
                    errorSection.classList.remove('d-none');
                    resp.errors.forEach((err, idx) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-center text-muted tabular-nums">${idx + 1}</td>
                            <td class="text-danger">${escapeHtml(err)}</td>
                        `;
                        errorTbody.appendChild(tr);
                    });
                }
            } else {
                resultMsg.innerHTML = `
                    <div class="alert cm-alert-danger d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-x-circle-fill text-danger fs-5" aria-hidden="true"></i>
                        <div>
                            <strong>Import Failed:</strong> ${escapeHtml(resp.error || 'Unknown error occurred.')}
                        </div>
                    </div>
                `;
            }
            resultArea.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(err => {
            console.error('Error importing file:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            resultMsg.innerHTML = `
                <div class="alert cm-alert-danger d-flex align-items-center gap-2 mb-0" role="alert">
                    <i class="bi bi-wifi-off text-danger fs-5" aria-hidden="true"></i>
                    <div>
                        <strong>Network Error:</strong> Failed to communicate with the import server.
                    </div>
                </div>
            `;
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
