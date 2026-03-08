<?php
// modules/lecturer/import_excel.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$user_id = $_SESSION['user_id'];

// Get Lecturer's Courses
$stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$courses = [];
while ($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Grades - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Import Grades (CSV)</h2>
            <a href="/CourseMonitor/modules/lecturer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Instructions:</strong> 
                    <ol>
                        <li>Select a Course and Assessment Type.</li>
                        <li>Download the <a href="#" onclick="downloadTemplate(event)">Class Template (CSV)</a> with student names pre-filled.</li>
                        <li>Enter grades in the <strong>Grade</strong> column.</li>
                        <li>Upload the file below.</li>
                    </ol>
                </div>

                <form id="importForm" onsubmit="uploadFile(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select id="course_id" name="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assessment Type</label>
                        <select id="assessment_type" name="assessment_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="Assignment 1">Assignment 1</option>
                            <option value="Assignment 2">Assignment 2</option>
                            <option value="Quiz 1">Quiz 1</option>
                            <option value="Quiz 2">Quiz 2</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Final">Final</option>
                        </select>
                        <div class="form-text">This type will be applied to all grades in the file.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" id="file" name="file" class="form-control" accept=".csv" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="downloadTemplate(event)">Download Template</button>
                        <button type="submit" class="btn btn-primary">Import Grades</button>
                    </div>
                </form>

                <div id="resultArea" class="mt-4" style="display:none;">
                    <h4>Import Result</h4>
                    <div id="resultMessage"></div>
                    <ul id="errorList" class="text-danger"></ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadTemplate(event) {
            event.preventDefault();
            const courseId = document.getElementById('course_id').value;
            
            if (!courseId) {
                alert("Please select a course first.");
                return;
            }

            window.location.href = `/CourseMonitor/api/download_grade_template.php?course_id=${courseId}`;
        }

        function uploadFile(event) {
            event.preventDefault();
            const form = document.getElementById('importForm');
            const formData = new FormData(form);

            const resultArea = document.getElementById('resultArea');
            const resultMsg = document.getElementById('resultMessage');
            const errorList = document.getElementById('errorList');
            
            resultArea.style.display = 'none';
            errorList.innerHTML = '';
            resultMsg.innerHTML = '<div class="spinner-border text-primary"></div> Processing...';
            resultArea.style.display = 'block';

            fetch('/CourseMonitor/api/import_grades.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    resultMsg.innerHTML = `<div class="alert alert-success">Successfully imported ${resp.count} records.</div>`;
                    if (resp.errors && resp.errors.length > 0) {
                        resp.errors.forEach(e => {
                            const li = document.createElement('li');
                            li.textContent = e;
                            errorList.appendChild(li);
                        });
                        resultMsg.innerHTML += `<div class="alert alert-warning">Some records failed (see list below).</div>`;
                    }
                } else {
                    resultMsg.innerHTML = `<div class="alert alert-danger">${resp.error}</div>`;
                }
            })
            .catch(err => {
                resultMsg.innerHTML = `<div class="alert alert-danger">Network Error</div>`;
            });
        }
    </script>
</body>
</html>