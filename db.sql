-- =========================================================
--  Student Attendance & Coursework Monitoring System
--  Database Schema (MySQL / MariaDB)
-- =========================================================

-- Use / create database
CREATE DATABASE IF NOT EXISTS attendance_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendance_system;

-- =========================================================
-- 1. Departments
-- =========================================================
CREATE TABLE departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    code        VARCHAR(20)  NOT NULL,
    UNIQUE KEY uq_departments_code (code)
) ENGINE=InnoDB;

-- =========================================================
-- 2. Users (Admin, Lecturer, Department Head)
-- =========================================================
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin', 'lecturer', 'head') NOT NULL,
    department_id   INT UNSIGNED NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_users_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- =========================================================
-- 3. Students
-- =========================================================
CREATE TABLE students (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_number  VARCHAR(50) NOT NULL,   -- University student ID
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NULL,
    department_id   INT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_students_student_number (student_number),
    KEY idx_students_department (department_id),
    CONSTRAINT fk_students_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 4. Courses
-- =========================================================
CREATE TABLE courses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50)  NOT NULL,  -- e.g., CS101
    name            VARCHAR(150) NOT NULL,
    department_id   INT UNSIGNED NOT NULL,
    semester        VARCHAR(20)  NULL,      -- e.g., "Fall", "Spring"
    academic_year   VARCHAR(20)  NULL,      -- e.g., "2024/2025"
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_courses_code (code),
    KEY idx_courses_department (department_id),

    CONSTRAINT fk_courses_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

) ENGINE=InnoDB;

-- =========================================================
-- 5. Course - Student Enrollment
--    (filled from Excel student list per course)
-- =========================================================
CREATE TABLE course_students (
    course_id   INT UNSIGNED NOT NULL,
    student_id  INT UNSIGNED NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (course_id, student_id),
    KEY idx_course_students_student (student_id),

    CONSTRAINT fk_course_students_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_course_students_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. Attendance Sessions
--    One row per class meeting (per course, per date/time)
-- =========================================================
CREATE TABLE attendance_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id       INT UNSIGNED NOT NULL,
    number_of_session INT NOT NULL,
    session_date    DATE NOT NULL,
    session_time    TIME NULL,             -- optional (for time-slot analysis)
    created_by      INT UNSIGNED NOT NULL, -- lecturer user id
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_att_sess_course (course_id),
    KEY idx_att_sess_date (session_date),
    KEY idx_att_sess_created_by (created_by),

    -- Prevent two identical sessions for same course + date + time
    UNIQUE KEY uq_att_sess_unique (course_id, session_date, session_time),

    CONSTRAINT fk_att_sess_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_att_sess_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 7. Attendance Records
--    One row per student per session
-- =========================================================
CREATE TABLE attendance_records (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED NOT NULL,
    student_id      INT UNSIGNED NOT NULL,
    status          ENUM('present', 'absent', 'late') NOT NULL,
    notes           VARCHAR(255) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_att_rec_session (session_id),
    KEY idx_att_rec_student (student_id),

    -- Each student appears only once per session
    UNIQUE KEY uq_att_rec_unique (session_id, student_id),

    CONSTRAINT fk_att_rec_session
        FOREIGN KEY (session_id) REFERENCES attendance_sessions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_att_rec_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. Coursework Items
--    (Assignments, Quizzes, Midterm, Final, etc.)
-- =========================================================
CREATE TABLE coursework_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id       INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,  -- e.g., "Quiz 1", "Midterm"
    type            ENUM('assignment', 'quiz', 'project', 'midterm', 'final', 'other')
                        NOT NULL DEFAULT 'other',
    max_mark        DECIMAL(5,2) NOT NULL,  -- e.g., 20.00
    weight          DECIMAL(5,2) NULL,      -- percentage of final grade (optional)
    due_date        DATE NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_cw_items_course (course_id),

    CONSTRAINT fk_cw_items_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 9. Coursework Grades
--    One row per student per coursework item
-- =========================================================
CREATE TABLE coursework_grades (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coursework_id   INT UNSIGNED NOT NULL,
    student_id      INT UNSIGNED NOT NULL,
    mark            DECIMAL(5,2) NOT NULL,   -- actual grade
    graded_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_cw_grades_cw (coursework_id),
    KEY idx_cw_grades_student (student_id),

    -- Each student has only one grade per coursework item
    UNIQUE KEY uq_cw_grade_unique (coursework_id, student_id),

    CONSTRAINT fk_cw_grades_cw
        FOREIGN KEY (coursework_id) REFERENCES coursework_items(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_cw_grades_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 10. lecturer courses
-- =========================================================
CREATE TABLE LECTURER_COURSES (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,


    CONSTRAINT uq_lecturer_course UNIQUE (course_id, lecturer_id),


    CONSTRAINT fk_lecturer_courses_lecturer
        FOREIGN KEY (lecturer_id) 
        REFERENCES users(id)
        ON UPDATE CASCADE 
        ON DELETE RESTRICT,


    CONSTRAINT fk_lecturer_courses_course 
        FOREIGN KEY (course_id) 
        REFERENCES courses(id)
        ON UPDATE CASCADE 
        ON DELETE CASCADE,


    INDEX idx_course_id (course_id),
    INDEX idx_lecturer_id (lecturer_id)
);

-- =========================================================
-- 11. Optional: Simple seed data (example department + admin)
-- =========================================================
INSERT INTO departments (name, code)
VALUES ('Computer Science', 'CS')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Example admin user (you must change the password hash!)
-- Password here is 'admin123' (generate your own with PHP password_hash)
INSERT INTO users (name, email, password_hash, role, department_id)
VALUES ('System Admin', 'admin@example.com', '$2y$10$CHANGE_ME_HASH', 'admin', NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name);
