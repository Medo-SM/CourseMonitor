-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 15, 2026 at 10:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `CourseMonitor`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `course_id`, `date`, `status`, `recorded_at`) VALUES
(1, 1, 3, '2026-02-15', 'Present', '2026-02-15 09:06:50'),
(2, 2, 3, '2026-02-15', 'Present', '2026-02-15 09:06:50'),
(3, 3, 3, '2026-02-15', 'Present', '2026-02-15 09:06:50'),
(4, 4, 3, '2026-02-15', 'Present', '2026-02-15 09:06:51'),
(17, 1, 5, '2026-02-15', 'Present', '2026-02-15 09:19:46'),
(18, 2, 5, '2026-02-15', 'Present', '2026-02-15 09:19:46'),
(19, 3, 5, '2026-02-15', 'Late', '2026-02-15 09:19:46'),
(20, 4, 5, '2026-02-15', 'Absent', '2026-02-15 09:19:46'),
(21, 1, 5, '2026-02-14', 'Absent', '2026-02-15 09:20:51'),
(22, 2, 5, '2026-02-14', 'Present', '2026-02-15 09:20:51'),
(23, 3, 5, '2026-02-14', 'Absent', '2026-02-15 09:20:51'),
(24, 4, 5, '2026-02-14', 'Absent', '2026-02-15 09:20:51');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `type` enum('notice','announcement','info') NOT NULL,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`id`, `title`, `body`, `type`, `is_visible`, `created_at`) VALUES
(1, 'Welcome to CourseMonitor', 'The new system is now live.', 'announcement', 1, '2026-02-13 16:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `credit_hours` int(11) NOT NULL CHECK (`credit_hours` > 0),
  `department_id` int(11) NOT NULL,
  `lecturer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `credit_hours`, `department_id`, `lecturer_id`) VALUES
(1, 'CS101', 'Intro to Programming', 3, 1, 4),
(2, 'CS102', 'Data Structures', 3, 1, 4),
(3, 'BIT111', 'Mathematics (1)', 3, 3, 4),
(4, 'BIT112', 'Computing and Problem Solving', 3, 3, 4),
(5, 'BIT113', 'Principles of Management', 3, 3, 6),
(6, 'BIT114', 'Introduction to Information Technology', 3, 3, NULL),
(7, 'BIT115', 'Programming (1)', 3, 3, NULL),
(8, 'BIT116', 'Proficiency Skills in English (1)', 2, 3, 6),
(9, 'BIT117', 'Arabic Israeli Conflict', 2, 3, NULL),
(10, 'BIT121', 'Mathematics (2)', 3, 3, NULL),
(11, 'BIT122', 'Database Systems', 3, 3, NULL),
(12, 'BIT123', 'Programming (2)', 3, 3, NULL),
(13, 'BIT124', 'Statistic & Probability', 3, 3, NULL),
(14, 'BIT125', 'English Language Integrated Skills', 2, 3, NULL),
(15, 'BIT126', 'Islamic Culture', 3, 3, NULL),
(16, 'BIT127', 'National Culture', 2, 3, NULL),
(17, 'BIT211', 'Web Design', 3, 3, NULL),
(18, 'BIT212', 'Object-Oriented Programming', 3, 3, NULL),
(19, 'BIT213', 'Introduction to Accounting', 3, 3, NULL),
(20, 'BIT214', 'Principles of Marketing', 3, 3, NULL),
(21, 'BIT215', 'Database Management System', 3, 3, NULL),
(22, 'BIT216', 'Academic English', 2, 3, NULL),
(23, 'BIT217', 'Arabic Language (1)', 2, 3, NULL),
(24, 'BIT221', 'Data Communication and Networking', 3, 3, NULL),
(25, 'BIT222', 'Advanced Programming', 3, 3, NULL),
(26, 'BIT223', 'Financial Modeling', 3, 3, NULL),
(27, 'BIT224', 'Web Development', 3, 3, NULL),
(28, 'BIT225', 'System Analysis and Design', 3, 3, NULL),
(29, 'BIT226', 'Operating Systems', 3, 3, NULL),
(30, 'BIT227', 'Arabic Language (2)', 2, 3, NULL),
(31, 'BIT311', 'Communication Skills and Presentation', 3, 3, NULL),
(32, 'BIT312', 'Visual Programming', 3, 3, NULL),
(33, 'BIT313', 'Management Information Systems', 3, 3, NULL),
(34, 'BIT314', 'Information System Security and Audit', 3, 3, NULL),
(35, 'BIT315', 'IT Project Management', 3, 3, NULL),
(36, 'BIT316', 'Software Engineering', 3, 3, NULL),
(37, 'BIT317', 'Research Methodology', 3, 3, NULL),
(38, 'BIT321', 'Mobile Application Development', 3, 3, NULL),
(39, 'BIT322', 'E-Business', 3, 3, NULL),
(40, 'BIT323', 'Total Quality Management', 3, 3, NULL),
(41, 'BIT324', 'Elective (1)', 3, 3, NULL),
(42, 'BIT325', 'Ethical and Professional Issues in Computing', 3, 3, NULL),
(43, 'BIT326', 'Project (1)', 3, 3, NULL),
(44, 'BIT411', 'Decision Support System', 3, 3, NULL),
(45, 'BIT412', 'Strategic Management for IT', 3, 3, NULL),
(46, 'BIT413', 'Entrepreneurship', 3, 3, NULL),
(47, 'BIT414', 'Organizational Behavior', 3, 3, NULL),
(48, 'BIT415', 'Elective (2)', 3, 3, NULL),
(49, 'BIT416', 'Project (2)', 3, 3, NULL),
(50, 'BIT421', 'Industrial Training', 6, 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coursework`
--

CREATE TABLE `coursework` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assessment_type` varchar(50) NOT NULL,
  `grade` decimal(5,2) NOT NULL CHECK (`grade` >= 0 and `grade` <= 100),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coursework`
--

INSERT INTO `coursework` (`id`, `student_id`, `course_id`, `assessment_type`, `grade`, `recorded_at`) VALUES
(1, 1, 3, 'Quiz 2', 5.00, '2026-02-15 08:24:48'),
(2, 1, 3, 'Quiz 2', 5.00, '2026-02-15 08:29:28'),
(3, 2, 3, 'Quiz 2', 4.99, '2026-02-15 08:29:28'),
(4, 3, 3, 'Quiz 2', 3.00, '2026-02-15 08:29:28'),
(5, 4, 3, 'Quiz 2', 5.00, '2026-02-15 08:29:28'),
(6, 1, 3, 'Assignment 1', 5.00, '2026-02-15 08:29:40'),
(7, 2, 3, 'Assignment 1', 5.00, '2026-02-15 08:29:40'),
(8, 3, 3, 'Assignment 1', 5.00, '2026-02-15 08:29:40'),
(9, 4, 3, 'Assignment 1', 5.00, '2026-02-15 08:29:40'),
(10, 1, 3, 'Assignment 2', 2.00, '2026-02-15 08:29:52'),
(11, 2, 3, 'Assignment 2', 3.00, '2026-02-15 08:29:52'),
(12, 3, 3, 'Assignment 2', 1.00, '2026-02-15 08:29:52'),
(13, 4, 3, 'Assignment 2', 2.00, '2026-02-15 08:29:52'),
(14, 1, 3, 'Quiz 1', 4.00, '2026-02-15 08:30:10'),
(15, 2, 3, 'Quiz 1', 4.50, '2026-02-15 08:30:10'),
(16, 3, 3, 'Quiz 1', 2.00, '2026-02-15 08:30:10'),
(17, 4, 3, 'Quiz 1', 3.00, '2026-02-15 08:30:10'),
(18, 1, 3, 'Midterm', 3.00, '2026-02-15 08:30:29'),
(19, 2, 3, 'Midterm', 20.00, '2026-02-15 08:30:29'),
(20, 3, 3, 'Midterm', 15.00, '2026-02-15 08:30:29'),
(21, 4, 3, 'Midterm', 20.00, '2026-02-15 08:30:29'),
(22, 1, 3, 'Final', 100.00, '2026-02-15 08:31:02'),
(23, 2, 3, 'Final', 50.00, '2026-02-15 08:31:02'),
(24, 3, 3, 'Final', 20.00, '2026-02-15 08:31:02'),
(25, 4, 3, 'Final', 40.00, '2026-02-15 08:31:02'),
(26, 1, 3, 'Assignment 1', 5.00, '2026-02-15 09:05:03'),
(27, 2, 3, 'Assignment 1', 5.00, '2026-02-15 09:05:03'),
(28, 3, 3, 'Assignment 1', 5.00, '2026-02-15 09:05:03'),
(29, 4, 3, 'Assignment 1', 5.00, '2026-02-15 09:05:03'),
(30, 1, 5, 'Assignment 1', 4.00, '2026-02-15 09:24:28'),
(31, 2, 5, 'Assignment 1', 4.00, '2026-02-15 09:24:28'),
(32, 3, 5, 'Assignment 1', 5.00, '2026-02-15 09:24:28'),
(33, 4, 5, 'Assignment 1', 6.00, '2026-02-15 09:24:28'),
(34, 1, 5, 'Assignment 2', 5.00, '2026-02-15 09:24:37'),
(35, 2, 5, 'Assignment 2', 6.00, '2026-02-15 09:24:37'),
(36, 3, 5, 'Assignment 2', 3.00, '2026-02-15 09:24:37'),
(37, 4, 5, 'Assignment 2', 5.00, '2026-02-15 09:24:37'),
(38, 1, 5, 'Quiz 1', 6.00, '2026-02-15 09:24:49'),
(39, 2, 5, 'Quiz 1', 7.00, '2026-02-15 09:24:49'),
(40, 3, 5, 'Quiz 1', 6.00, '2026-02-15 09:24:49'),
(41, 4, 5, 'Quiz 1', 7.00, '2026-02-15 09:24:49'),
(42, 1, 5, 'Quiz 2', 7.00, '2026-02-15 09:25:07'),
(43, 2, 5, 'Quiz 2', 6.00, '2026-02-15 09:25:07'),
(44, 3, 5, 'Quiz 2', 8.00, '2026-02-15 09:25:07'),
(45, 4, 5, 'Quiz 2', 7.00, '2026-02-15 09:25:07'),
(46, 1, 5, 'Midterm', 7.00, '2026-02-15 09:25:26'),
(47, 2, 5, 'Midterm', 6.00, '2026-02-15 09:25:26'),
(48, 3, 5, 'Midterm', 7.00, '2026-02-15 09:25:26'),
(49, 4, 5, 'Midterm', 6.00, '2026-02-15 09:25:26'),
(50, 1, 5, 'Final', 6.00, '2026-02-15 09:25:36'),
(51, 2, 5, 'Final', 7.00, '2026-02-15 09:25:36'),
(52, 3, 5, 'Final', 5.00, '2026-02-15 09:25:36'),
(53, 4, 5, 'Final', 6.00, '2026-02-15 09:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `course_semesters`
--

CREATE TABLE `course_semesters` (
  `course_id` int(11) NOT NULL,
  `semester_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_semesters`
--

INSERT INTO `course_semesters` (`course_id`, `semester_number`) VALUES
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 3),
(18, 3),
(19, 3),
(20, 3),
(21, 3),
(22, 3),
(23, 3),
(24, 4),
(25, 4),
(26, 4),
(27, 4),
(28, 4),
(29, 4),
(30, 4),
(31, 5),
(32, 5),
(33, 5),
(34, 5),
(35, 5),
(36, 5),
(37, 5),
(38, 6),
(39, 6),
(40, 6),
(41, 6),
(42, 6),
(43, 6),
(44, 7),
(45, 7),
(46, 7),
(47, 7),
(48, 7),
(49, 7),
(50, 8);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `head_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `head_id`, `faculty_id`) VALUES
(1, 'Computer Science', 3, 4),
(2, 'PlaceHolder', 3, 4),
(3, 'Business Inforamation Technology (BIT)', 5, 5);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL DEFAULT 'Spring 2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `semester`) VALUES
(1, 1, 1, 'Spring 2026'),
(4, 1, 2, 'Spring 2026'),
(2, 2, 1, 'Spring 2026'),
(3, 3, 1, 'Spring 2026');

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `name`) VALUES
(3, 'Faculty of Arts'),
(2, 'Faculty of Engineering'),
(1, 'Faculty of Science'),
(4, 'Faculty of Technology'),
(5, 'Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `current_semester` int(11) DEFAULT 1 COMMENT 'The academic semester level of the student (e.g., 1, 2, ... 8)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id_number`, `name`, `email`, `department_id`, `current_semester`) VALUES
(1, 'S2026001', 'Alice Brown', 'alice@student.com', 3, 1),
(2, 'S2026002', 'Bob White', 'bob@student.com', 3, 1),
(3, 'S2026003', 'Charlie Green', 'charlie@student.com', 3, 1),
(4, 'B10126001', 'Khaled Alqadsy', 'khaled@gmail.com', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('lecturer','head','admin') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `is_active`, `created_at`, `department_id`, `faculty_id`) VALUES
(1, 'admin', '$2y$10$HPPOPvaw2JqnIIJpsOn8y.ZotJcm1indgUKiYTglULar/bGpcru1K', 'admin@coursemonitor.com', 'admin', 'System Administrator', 1, '2026-02-13 16:08:35', NULL, NULL),
(2, 'lecturer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@coursemonitor.com', 'lecturer', 'John Doe', 1, '2026-02-13 16:08:35', NULL, 5),
(3, 'head1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@coursemonitor.com', 'head', 'Jane Smith', 1, '2026-02-13 16:08:35', NULL, NULL),
(4, 'Mohammed', '$2y$10$mv44iydkFa4F/AazsqCytumWuVWevWmcu7oQ7N58TA04UTdgOXR4W', 'malsmawi1@gmail.com', 'lecturer', 'Moahmmed Assem', 1, '2026-02-13 16:13:59', NULL, 5),
(5, 'Alsamawi', '$2y$10$5.uiN.oH5F/DwAS5vV5SQ.b3NH4MW2MqsVw1TyeR0t9sotfxQvG56', 'malsmawi11@gmail.com', 'head', 'Mohammed Alsamawi', 1, '2026-02-15 05:50:12', NULL, NULL),
(6, 'khaled', '$2y$10$LiqrC1IIztj7.WpaApxFe.UUdKEmZCO3x3mFExw7f9sXN/M222U9e', 'k7713@gmail.com', 'lecturer', 'khaled', 1, '2026-02-15 09:15:22', NULL, 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`date`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modified_by` (`modified_by`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `lecturer_id` (`lecturer_id`);

--
-- Indexes for table `coursework`
--
ALTER TABLE `coursework`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_semesters`
--
ALTER TABLE `course_semesters`
  ADD PRIMARY KEY (`course_id`,`semester_number`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `head_id` (`head_id`),
  ADD KEY `fk_dept_faculty` (`faculty_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id_number` (`student_id_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_dept` (`department_id`),
  ADD KEY `fk_user_faculty` (`faculty_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `coursework`
--
ALTER TABLE `coursework`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_course_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coursework`
--
ALTER TABLE `coursework`
  ADD CONSTRAINT `fk_cw_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cw_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_semesters`
--
ALTER TABLE `course_semesters`
  ADD CONSTRAINT `fk_cs_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
