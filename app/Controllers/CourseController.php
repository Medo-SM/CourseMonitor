<?php

class CourseController
{
    private mysqli $res;

    // TEMPORARY until login is implemented
    // Change this to an existing lecturer user ID from your `users` table
    private int $lecturerId = 1;

    public function __construct(mysqli $res)
    {
        $this->res = $res;
    }

    public function index(): void
    {
        $sql = "
            SELECT 
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year
            FROM courses c
            INNER JOIN LECTURER_COURSES lc 
                ON lc.course_id = c.id
            WHERE lc.lecturer_id = ?
            ORDER BY c.id DESC
        ";

        $stmt = $this->res->prepare($sql);
        if (!$stmt) {
            // Stop early if SQL is wrong
            die('SQL prepare error: ' . $this->res->error);
        }

        $stmt->bind_param('i', $this->lecturerId);
        $stmt->execute();

        $result = $stmt->get_result();

        // ALWAYS define $courses before loading the view
        $courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $stmt->close();

        require __DIR__ . '/../Views/courses_index.php';
    }
}
