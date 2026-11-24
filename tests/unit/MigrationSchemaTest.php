<?php
// tests/unit/MigrationSchemaTest.php

class MigrationSchemaTest {

    public function testMigrationFileExistsAndIsNonEmpty() {
        $migrationPath = __DIR__ . '/../../migrations/001_remediation.sql';
        Assert::true(file_exists($migrationPath), "001_remediation.sql migration file must exist");
        $content = file_get_contents($migrationPath);
        Assert::true(strlen($content) > 100, "Migration file must contain meaningful SQL commands");
    }

    public function testCourseworkUniqueConstraintInMigration() {
        $migrationPath = __DIR__ . '/../../migrations/001_remediation.sql';
        $content = file_get_contents($migrationPath);

        // Check for deduplication statement
        Assert::contains('DELETE c1 FROM coursework c1', $content, "Migration must contain coursework deduplication statement");

        // Check for unique constraint statement
        Assert::contains('unique_student_course_assessment', $content, "Migration must define unique_student_course_assessment constraint");
        Assert::contains('(student_id, course_id, assessment_type)', $content, "Unique constraint must cover student_id, course_id, assessment_type");
    }

    public function testAuditLogsExpansionInMigration() {
        $migrationPath = __DIR__ . '/../../migrations/001_remediation.sql';
        $content = file_get_contents($migrationPath);

        // Check for action column modification
        Assert::contains('MODIFY COLUMN action VARCHAR(100)', $content, "Migration must expand action column to VARCHAR(100)");

        // Check for reason column addition
        Assert::contains('ADD COLUMN reason TEXT', $content, "Migration must add dedicated reason column to audit_logs");
    }

    public function testCourseFunctionsFileExistsAndDefinesFunction() {
        $cfPath = __DIR__ . '/../../includes/course_functions.php';
        Assert::true(file_exists($cfPath), "includes/course_functions.php must exist");
        require_once $cfPath;
        Assert::true(function_exists('get_enrolled_students'), "get_enrolled_students function must be defined");
    }
}
?>
