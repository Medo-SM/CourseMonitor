-- migrations/001_remediation.sql
-- Database remediation migration for CourseMonitor

-- 1. Deduplicate coursework entries before applying unique constraint
-- Keeps the latest record (highest id) for any existing duplicate assessment entries
DELETE c1 FROM coursework c1
INNER JOIN coursework c2 
WHERE c1.id < c2.id 
  AND c1.student_id = c2.student_id 
  AND c1.course_id = c2.course_id 
  AND c1.assessment_type = c2.assessment_type;

-- 2. Add unique constraint to coursework to ensure ON DUPLICATE KEY UPDATE works properly
ALTER TABLE coursework 
  ADD CONSTRAINT unique_student_course_assessment 
  UNIQUE (student_id, course_id, assessment_type);

-- 3. Expand audit_logs action column length and add dedicated reason column
-- Prevents SQL truncation exceptions when descriptive modification reasons are logged
ALTER TABLE audit_logs 
  MODIFY COLUMN action VARCHAR(100) NOT NULL,
  ADD COLUMN reason TEXT DEFAULT NULL AFTER action;
