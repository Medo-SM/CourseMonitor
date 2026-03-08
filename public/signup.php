<?php
require_once __DIR__ . '/../includes/auth_functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $role = $_POST['role'];
        $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? intval($_POST['department_id']) : null;
        $faculty_id = isset($_POST['faculty_id']) && $_POST['faculty_id'] !== '' ? intval($_POST['faculty_id']) : null;

        // Validation
        if (empty($username) || empty($fullName) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } elseif (!in_array($role, ['lecturer', 'head'])) {
            $error = "Invalid role selected.";
        } elseif ($role === 'head' && !$department_id) {
             $error = "Please select a Department.";
        } elseif ($role === 'lecturer' && !$faculty_id) {
             $error = "Please select a Faculty.";
        } else {
            // Check duplicates
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $error = "Username or Email already exists.";
                }
                $stmt->close();
            }

            if (empty($error)) {
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                $isActive = 1; 
                
                // Clear the irrelevant ID based on role
                if ($role === 'head') $faculty_id = null;
                if ($role === 'lecturer') $department_id = null;

                $insertStmt = $mysqli->prepare("INSERT INTO users (username, email, full_name, role, department_id, faculty_id, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("sssssiis", $username, $email, $fullName, $role, $department_id, $faculty_id, $hashedPwd, $isActive);
                    if ($insertStmt->execute()) {
                        $success = "Registration successful! You can now <a href='/CourseMonitor/public/login.php'>login</a>.";
                    } else {
                        $error = "Registration failed: " . $insertStmt->error;
                    }
                    $insertStmt->close();
                } else {
                    $error = "Database error: " . $mysqli->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .signup-card { max-width: 450px; width: 100%; margin: 2rem 0; }
    </style>
</head>
<body>
    <div class="card signup-card shadow-sm">
        <div class="card-body p-4">
            <h2 class="text-center mb-4">Sign Up</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required onchange="toggleAffiliation()">
                            <option value="">Select Role</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="head">Department Head</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="deptGroup" style="display:none;">
                        <label for="department_id" class="form-label">Department</label>
                        <select id="department_id" name="department_id" class="form-select">
                            <option value="">Select Department</option>
                        </select>
                        <div class="form-text">Required for Department Heads.</div>
                    </div>

                    <div class="mb-3" id="facultyGroup" style="display:none;">
                        <label for="faculty_id" class="form-label">Faculty</label>
                        <select id="faculty_id" name="faculty_id" class="form-select">
                            <option value="">Select Faculty</option>
                        </select>
                        <div class="form-text">Required for Lecturers.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Register</button>
                </form>
            <?php endif; ?>
            <div class="text-center mt-3">
                <a href="/CourseMonitor/public/login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
    
    <script>
        function toggleAffiliation() {
            const role = document.getElementById('role').value;
            const deptGroup = document.getElementById('deptGroup');
            const facultyGroup = document.getElementById('facultyGroup');
            
            deptGroup.style.display = 'none';
            facultyGroup.style.display = 'none';

            if (role === 'head') {
                deptGroup.style.display = 'block';
                if(document.getElementById('department_id').options.length <= 1) loadDepartments();
            } else if (role === 'lecturer') {
                facultyGroup.style.display = 'block';
                if(document.getElementById('faculty_id').options.length <= 1) loadFaculties();
            }
        }

        function loadDepartments() {
            fetch('/CourseMonitor/api/get_departments.php')
                .then(r => r.json())
                .then(depts => {
                    const select = document.getElementById('department_id');
                    select.innerHTML = '<option value="">Select Department</option>';
                    depts.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        select.appendChild(opt);
                    });
                });
        }

        function loadFaculties() {
            fetch('/CourseMonitor/api/get_faculties.php?public=1') 
                .then(r => r.json())
                .then(list => {
                    const select = document.getElementById('faculty_id');
                    select.innerHTML = '<option value="">Select Faculty</option>';
                    list.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.name;
                        select.appendChild(opt);
                    });
                });
        }
    </script>
</body>
</html>