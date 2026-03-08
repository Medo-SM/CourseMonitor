<?php
// modules/lecturer/coursework.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

$courses_stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ?");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Coursework</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f8f9fa; }
        .grade-input { width: 100px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Enter Coursework Grades</h2>
            <a href="/CourseMonitor/modules/lecturer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Course</label>
                        <select id="course" class="form-select" onchange="loadData()">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assessment Type</label>
                        <select id="assessment_type" class="form-select" onchange="loadData()">
                            <option value="">-- Select Type --</option>
                            <option value="Assignment 1">Assignment 1</option>
                            <option value="Assignment 2">Assignment 2</option>
                            <option value="Quiz 1">Quiz 1</option>
                            <option value="Quiz 2">Quiz 2</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="gradeForm" onsubmit="saveGrades(event)">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="gradeTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Grade (0-100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center text-muted">Select a course and assessment type to load students.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Grades</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             if (document.getElementById('course').value) {
                 loadData();
             }
        });

        function loadData() {
            const courseId = document.getElementById('course').value;
            const assessmentType = document.getElementById('assessment_type').value;
            const tbody = document.querySelector('#gradeTable tbody');
            
            if (!courseId) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Select a course.</td></tr>';
                return;
            }

            tbody.innerHTML = '<tr><td colspan="3" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>';

            // Load Students with correct path
            fetch(`/CourseMonitor/api/get_students.php?course_id=${courseId}`)
                .then(r => r.json())
                .then(students => {
                    if (students.error) { 
                        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${students.error}</td></tr>`;
                        return; 
                    }
                    
                    if (students.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No students found for this course.</td></tr>';
                        return;
                    }

                    // If assessment type selected, load existing grades
                    if (assessmentType) {
                        fetch(`/CourseMonitor/api/get_coursework.php?course_id=${courseId}&assessment_type=${encodeURIComponent(assessmentType)}`)
                            .then(r => r.json())
                            .then(grades => {
                                renderTable(students, grades);
                            })
                            .catch(e => {
                                console.error("Error fetching grades", e);
                                renderTable(students, {}); // Fallback
                            });
                    } else {
                        renderTable(students, {});
                    }
                })
                .catch(e => {
                    console.error("Error fetching students", e);
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Network Error</td></tr>`;
                });
        }

        function renderTable(students, existingGrades) {
            const tbody = document.querySelector('#gradeTable tbody');
            tbody.innerHTML = '';
            
            students.forEach(student => {
                // existingGrades is an object {student_id: grade}
                const grade = existingGrades[student.id] !== undefined ? existingGrades[student.id] : '';
                const row = `
                    <tr>
                        <td>${student.student_id_number}</td>
                        <td>${student.name}</td>
                        <td>
                            <input type="number" name="grade[${student.id}]" 
                                   min="0" max="100" step="0.01" value="${grade}" 
                                   class="form-control grade-input" data-student-id="${student.id}">
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        function saveGrades(event) {
            event.preventDefault();
            const courseId = document.getElementById('course').value;
            const assessmentType = document.getElementById('assessment_type').value;

            if (!courseId || !assessmentType) {
                alert("Select course and assessment type.");
                return;
            }

            const inputs = document.querySelectorAll('.grade-input');
            const gradeData = [];
            let missingGrades = false;

            inputs.forEach(input => {
                const studentId = input.getAttribute('data-student-id');
                const val = input.value;
                if (val === '') {
                    missingGrades = true;
                } else {
                    gradeData.push({ student_id: studentId, grade: val });
                }
            });

            if (gradeData.length === 0 && missingGrades) {
                alert("Please enter at least one grade.");
                return;
            }

            if (missingGrades) {
                if (!confirm("Some students have missing grades. Continue saving only entered grades?")) return;
            }

            fetch('/CourseMonitor/api/save_coursework.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    course_id: courseId,
                    assessment_type: assessmentType,
                    grades: gradeData,
                    csrf_token: '<?php echo generateCsrfToken(); ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Grades saved! Class Average: ${data.average}`);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(e => alert("Network error"));
        }
    </script>
</body>
</html>