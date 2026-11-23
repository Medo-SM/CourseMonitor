-- migrations/001_remediation.sql
-- Database remediation migration for CourseMonitor
-- Milestone M1: Architecture & Security Foundations

-- Step 1: Deduplicate coursework entries before applying unique constraint.
-- Removes duplicate (student_id, course_id, assessment_type) rows,
-- preserving the latest record with the highest id.
DELETE c1 FROM coursework c1
INNER JOIN coursework c2 
WHERE c1.id < c2.id 
  AND c1.student_id = c2.student_id 
  AND c1.course_id = c2.course_id 
  AND c1.assessment_type = c2.assessment_type;

-- Step 2: Add unique constraint to coursework to guarantee data integrity
-- and ensure ON DUPLICATE KEY UPDATE activates properly on duplicate attempts.
ALTER TABLE coursework 
  ADD CONSTRAINT unique_student_course_assessment 
  UNIQUE (student_id, course_id, assessment_type);

-- Step 3: Expand audit_logs action column length to VARCHAR(100) and add dedicated reason column.
-- Prevents SQL truncation exceptions when detailed modification reasons are logged.
ALTER TABLE audit_logs 
  MODIFY COLUMN action VARCHAR(100) NOT NULL,
  ADD COLUMN reason TEXT DEFAULT NULL;

-- Step 4: Backfill reason from legacy action strings if formatted as 'UPDATE (Reason: %)'
UPDATE audit_logs 
  SET reason = SUBSTRING(action, 17, CHAR_LENGTH(action) - 17),
      action = 'UPDATE'
  WHERE action LIKE 'UPDATE (Reason: %)';
