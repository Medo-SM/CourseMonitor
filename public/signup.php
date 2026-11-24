<?php
require_once __DIR__ . '/../includes/auth_functions.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            header("Location: " . base_url('/modules/admin/dashboard.php'));
            break;
        case 'head':
            header("Location: " . base_url('/modules/head/dashboard.php'));
            break;
        case 'lecturer':
            header("Location: " . base_url('/modules/lecturer/dashboard.php'));
            break;
        default:
            header("Location: " . base_url('/'));
            break;
    }
    exit;
}

$error = '';
$success = '';

$usernameVal = '';
$fullNameVal = '';
$emailVal = '';
$roleVal = '';
$selectedDept = null;
$selectedFac = null;

// Preload departments and faculties for server-side rendering and resilient dropdowns
$departmentsList = [];
$deptQuery = $mysqli->query("SELECT id, name FROM departments ORDER BY name ASC");
if ($deptQuery) {
    while ($row = $deptQuery->fetch_assoc()) {
        $departmentsList[] = $row;
    }
}

$facultiesList = [];
$facQuery = $mysqli->query("SELECT id, name FROM faculties ORDER BY name ASC");
if ($facQuery) {
    while ($row = $facQuery->fetch_assoc()) {
        $facultiesList[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Your security session has expired. Please refresh the page and try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? intval($_POST['department_id']) : null;
        $faculty_id = isset($_POST['faculty_id']) && $_POST['faculty_id'] !== '' ? intval($_POST['faculty_id']) : null;

        // Keep values for form repopulation on validation error
        $usernameVal = $username;
        $fullNameVal = $fullName;
        $emailVal = $email;
        $roleVal = $role;
        $selectedDept = $department_id;
        $selectedFac = $faculty_id;

        // Validation
        if (empty($username) || empty($fullName) || empty($email) || empty($password)) {
            $error = "All required fields must be completed.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirmPassword) {
            $error = "The passwords you entered do not match. Please try again.";
        } elseif (!in_array($role, ['lecturer', 'head'], true)) {
            $error = "Please select a valid role (Teacher or Department Head).";
        } elseif ($role === 'head' && !$department_id) {
            $error = "Please choose your department.";
        } elseif ($role === 'lecturer' && !$faculty_id) {
            $error = "Please choose your faculty.";
        } else {
            // Check duplicates
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $error = "That username or email is already registered. If you already have an account, you can sign in below.";
                }
                $stmt->close();
            }

            if (empty($error)) {
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                $isActive = 1; 
                
                // Clear the irrelevant ID based on role
                if ($role === 'head') {
                    $faculty_id = null;
                }
                if ($role === 'lecturer') {
                    $department_id = null;
                }

                $insertStmt = $mysqli->prepare("INSERT INTO users (username, email, full_name, role, department_id, faculty_id, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("sssssisi", $username, $email, $fullName, $role, $department_id, $faculty_id, $hashedPwd, $isActive);
                    if ($insertStmt->execute()) {
                        $newUserId = $insertStmt->insert_id;
                        if ($role === 'head' && $department_id) {
                            $updateDept = $mysqli->prepare("UPDATE departments SET head_id = ? WHERE id = ?");
                            if ($updateDept) {
                                $updateDept->bind_param("ii", $newUserId, $department_id);
                                $updateDept->execute();
                                $updateDept->close();
                            }
                        }
                        $loginUrl = htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8');
                        $success = "Your account has been created. You can now sign in with your credentials.";
                    } else {
                        $error = "Registration failed: " . $insertStmt->error;
                    }
                    $insertStmt->close();
                } else {
                    $error = "Database service error: " . $mysqli->error;
                }
            }
        }
    }
}

$pageTitle = 'Create an Account';
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7" style="max-width: 620px;">
            <div class="cm-card shadow-sm border bg-white">
                <div class="cm-card-body p-4 p-sm-5">
                    <!-- Brand Icon & Header -->
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-slate-100 text-primary mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;" aria-hidden="true">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h1 class="h4 fw-bold text-slate-900 mb-1">Create an Account</h1>
                        <p class="text-sm text-secondary mb-0">Join CourseMonitor to manage your courses and students</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert" aria-live="assertive">
                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <div class="text-xs">
                                <span class="fw-semibold">Notice:</span> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="cm-card border-success bg-white text-center p-4 mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-50 text-success mb-3" style="width: 48px; height: 48px; font-size: 1.5rem;" aria-hidden="true">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <h2 class="h5 fw-bold text-slate-900 mb-2">Account Created Successfully</h2>
                            <p class="text-sm text-secondary mb-4"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                            <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary px-4">
                                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                                <span>Sign In</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" class="needs-validation">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                            <!-- Fieldset 1: Account Details -->
                            <fieldset class="mb-4">
                                <legend class="h6 fw-bold text-slate-900 border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-person-badge text-primary" aria-hidden="true"></i>
                                    <span>Account Details</span>
                                </legend>

                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="username" class="form-label">
                                            Username <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="username" 
                                            name="username" 
                                            class="form-control" 
                                            required 
                                            aria-required="true" 
                                            autocomplete="username" 
                                            placeholder="e.g. jdoe" 
                                            value="<?= htmlspecialchars($usernameVal, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="full_name" class="form-label">
                                            Full Name <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="full_name" 
                                            name="full_name" 
                                            class="form-control" 
                                            required 
                                            aria-required="true" 
                                            autocomplete="name" 
                                            placeholder="e.g. Dr. Jane Doe" 
                                            value="<?= htmlspecialchars($fullNameVal, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                    </div>

                                    <div class="col-12">
                                        <label for="email" class="form-label">
                                            Email Address <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-control" 
                                            required 
                                            aria-required="true" 
                                            autocomplete="email" 
                                            placeholder="name@university.edu" 
                                            value="<?= htmlspecialchars($emailVal, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                        <div class="form-text text-xs">Used for sign in and account notices.</div>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="password" class="form-label">
                                            Password <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input 
                                                type="password" 
                                                id="password" 
                                                name="password" 
                                                class="form-control" 
                                                required 
                                                aria-required="true" 
                                                autocomplete="new-password" 
                                                placeholder="At least 6 characters"
                                            >
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-secondary border-start-0 text-secondary" 
                                                id="togglePasswordBtn1" 
                                                aria-label="Show password" 
                                                title="Show password" 
                                                onclick="toggleVisibility('password', 'togglePasswordIcon1', 'togglePasswordBtn1')"
                                            >
                                                <i class="bi bi-eye" id="togglePasswordIcon1" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="confirm_password" class="form-label">
                                            Confirm Password <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input 
                                                type="password" 
                                                id="confirm_password" 
                                                name="confirm_password" 
                                                class="form-control" 
                                                required 
                                                aria-required="true" 
                                                autocomplete="new-password" 
                                                placeholder="Re-enter your password"
                                            >
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-secondary border-start-0 text-secondary" 
                                                id="togglePasswordBtn2" 
                                                aria-label="Show password confirmation" 
                                                title="Show password confirmation" 
                                                onclick="toggleVisibility('confirm_password', 'togglePasswordIcon2', 'togglePasswordBtn2')"
                                            >
                                                <i class="bi bi-eye" id="togglePasswordIcon2" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Fieldset 2: Your School & Role -->
                            <fieldset class="mb-4">
                                <legend class="h6 fw-bold text-slate-900 border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-mortarboard text-primary" aria-hidden="true"></i>
                                    <span>Your School &amp; Role</span>
                                </legend>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="role" class="form-label">
                                            Your Role <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <select id="role" name="role" class="form-select" required aria-required="true" onchange="toggleAffiliation()">
                                            <option value="">Choose your role...</option>
                                            <option value="lecturer" <?= $roleVal === 'lecturer' ? 'selected' : '' ?>>Teacher (Course Instructor)</option>
                                            <option value="head" <?= $roleVal === 'head' ? 'selected' : '' ?>>Department Head</option>
                                        </select>
                                        <div class="form-text text-xs">Determines your workspace access and features.</div>
                                    </div>

                                    <!-- Department Selection (For Head of Department) -->
                                    <div class="col-12" id="deptGroup" style="<?= $roleVal === 'head' ? 'display:block;' : 'display:none;' ?>">
                                        <label for="department_id" class="form-label">
                                            Department <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <select id="department_id" name="department_id" class="form-select" aria-describedby="dept-help">
                                            <option value="">Select your department...</option>
                                            <?php foreach ($departmentsList as $dept): ?>
                                                <option value="<?= (int)$dept['id'] ?>" <?= $selectedDept === (int)$dept['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-xs" id="dept-help">The academic department you manage.</div>
                                    </div>

                                    <!-- Faculty Selection (For Lecturer) -->
                                    <div class="col-12" id="facultyGroup" style="<?= $roleVal === 'lecturer' ? 'display:block;' : 'display:none;' ?>">
                                        <label for="faculty_id" class="form-label">
                                            Faculty <span class="text-danger" aria-hidden="true">*</span>
                                        </label>
                                        <select id="faculty_id" name="faculty_id" class="form-select" aria-describedby="faculty-help">
                                            <option value="">Select your faculty...</option>
                                            <?php foreach ($facultiesList as $fac): ?>
                                                <option value="<?= (int)$fac['id'] ?>" <?= $selectedFac === (int)$fac['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($fac['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-xs" id="faculty-help">The faculty your courses belong to.</div>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Submit Action -->
                            <button type="submit" class="btn btn-primary w-100 py-2 fs-6 fw-medium">
                                <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                                <span>Create Account</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Sign In Link -->
                    <div class="text-center pt-3 mt-4 border-top">
                        <span class="text-sm text-secondary">Already have an account?</span>
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm fw-semibold text-primary ms-1">
                            Sign In
                        </a>
                    </div>
                </div>

                <div class="cm-card-footer bg-slate-50 border-top text-center py-3">
                    <p class="text-xs text-muted mb-0">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                        Your information is safely stored and protected with role-based access.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAffiliation() {
    const role = document.getElementById('role').value;
    const deptGroup = document.getElementById('deptGroup');
    const facultyGroup = document.getElementById('facultyGroup');
    const deptSelect = document.getElementById('department_id');
    const facSelect = document.getElementById('faculty_id');
    
    if (deptGroup) deptGroup.style.display = 'none';
    if (facultyGroup) facultyGroup.style.display = 'none';

    if (role === 'head') {
        if (deptGroup) deptGroup.style.display = 'block';
        if (deptSelect && deptSelect.options.length <= 1) {
            loadDepartments();
        }
    } else if (role === 'lecturer') {
        if (facultyGroup) facultyGroup.style.display = 'block';
        if (facSelect && facSelect.options.length <= 1) {
            loadFaculties();
        }
    }
}

function loadDepartments() {
    const apiUrl = <?= json_encode(base_url('/api/get_departments.php')) ?>;
    fetch(apiUrl)
        .then(r => r.ok ? r.json() : [])
        .then(depts => {
            const select = document.getElementById('department_id');
            if (!select || !Array.isArray(depts)) return;
            select.innerHTML = '<option value="">Select your department...</option>';
            depts.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                select.appendChild(opt);
            });
        })
        .catch(() => {});
}

function loadFaculties() {
    const apiUrl = <?= json_encode(base_url('/api/get_faculties.php?public=1')) ?>;
    fetch(apiUrl)
        .then(r => r.ok ? r.json() : [])
        .then(list => {
            const select = document.getElementById('faculty_id');
            if (!select || !Array.isArray(list)) return;
            select.innerHTML = '<option value="">Select your faculty...</option>';
            list.forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = f.name;
                select.appendChild(opt);
            });
        })
        .catch(() => {});
}

function toggleVisibility(inputId, iconId, btnId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const btn = document.getElementById(btnId);
    if (!input || !icon || !btn) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
        btn.setAttribute('aria-label', 'Hide password');
        btn.setAttribute('title', 'Hide password');
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('title', 'Show password');
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
