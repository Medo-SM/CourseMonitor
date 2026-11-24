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
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Your security session has expired. Please refresh the page and try again.";
    } else {
        $usernameVal = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($usernameVal) || empty($password)) {
            $error = "Please enter both your username or email and your password.";
        } else {
            // Check if user entered an email address; if so, resolve to their username
            $actualUsername = $usernameVal;
            if (str_contains($usernameVal, '@') && isset($mysqli) && $mysqli && !$mysqli->connect_error) {
                $lookup = $mysqli->prepare("SELECT username FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
                if ($lookup) {
                    $lookup->bind_param("s", $usernameVal);
                    $lookup->execute();
                    $res = $lookup->get_result();
                    if ($userRow = $res->fetch_assoc()) {
                        $actualUsername = $userRow['username'];
                    }
                    $lookup->close();
                }
            }

            if (login($actualUsername, $password)) {
                // Redirect based on role
                switch ($_SESSION['role']) {
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
            } else {
                $error = "The username or password you entered is incorrect. Please check your details and try again.";
            }
        }
    }
}

$pageTitle = 'Sign In';
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 my-auto">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4" style="max-width: 440px;">
            <div class="cm-card shadow-sm border">
                <div class="cm-card-body p-4 p-sm-5">
                    <!-- Brand Icon & Header -->
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-primary mb-3" style="width: 52px; height: 52px; font-size: 1.5rem; background-color: var(--cm-bg-subtle);" aria-hidden="true">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h1 class="h4 fw-bold mb-1">Welcome Back</h1>
                        <p class="text-sm text-secondary mb-0">Sign in to your account</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert" aria-live="assertive" id="login-error">
                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <div class="text-xs">
                                <span class="fw-semibold">Unable to sign in:</span> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- Username / Email Field -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Username or Email <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text text-secondary border-end-0" style="background-color: var(--cm-bg-subtle);" id="username-addon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    class="form-control border-start-0" 
                                    required 
                                    aria-required="true" 
                                    autocomplete="username" 
                                    aria-describedby="username-addon <?= !empty($error) ? 'login-error' : '' ?>"
                                    placeholder="Enter your username or email"
                                    value="<?= htmlspecialchars($usernameVal, ENT_QUOTES, 'UTF-8') ?>"
                                    autofocus
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                Password <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text text-secondary border-end-0" style="background-color: var(--cm-bg-subtle);" id="password-addon">
                                    <i class="bi bi-key" aria-hidden="true"></i>
                                </span>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control border-start-0 border-end-0" 
                                    required 
                                    aria-required="true" 
                                    autocomplete="current-password" 
                                    aria-describedby="password-addon password-help"
                                    placeholder="Enter your password"
                                >
                                <button 
                                    type="button" 
                                    class="btn btn-outline-secondary border-start-0 text-secondary px-3" 
                                    id="togglePasswordBtn" 
                                    aria-label="Show password" 
                                    title="Show password"
                                    onclick="togglePasswordVisibility()"
                                >
                                    <i class="bi bi-eye" id="togglePasswordIcon" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="form-text text-xs mt-1" id="password-help">
                                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Passwords are case-sensitive.
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-6 fw-medium">
                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                            <span>Sign In</span>
                        </button>
                    </form>

                    <!-- Account Registration Link -->
                    <div class="text-center pt-3 mt-4 border-top">
                        <span class="text-sm text-secondary">Don't have an account yet?</span>
                        <a href="<?= htmlspecialchars(base_url('/public/signup.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm fw-semibold text-primary ms-1">
                            Create an account
                        </a>
                    </div>
                </div>

                <div class="cm-card-footer border-top text-center py-3">
                    <p class="text-xs text-muted mb-0">
                        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                        CourseMonitor protects your data with secure authentication.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    const toggleBtn = document.getElementById('togglePasswordBtn');

    if (!passwordInput || !toggleIcon || !toggleBtn) return;

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
        toggleBtn.setAttribute('aria-label', 'Hide password');
        toggleBtn.setAttribute('title', 'Hide password');
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
        toggleBtn.setAttribute('aria-label', 'Show password');
        toggleBtn.setAttribute('title', 'Show password');
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
