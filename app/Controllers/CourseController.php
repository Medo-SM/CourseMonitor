<?php

class CourseController
{
    private $res;
    private $lecturerId = 1; // change this to the lecturer user's id

    public function __construct($res)
    {
        $this->res = $res;
    }

    public function index()
    {
        $sql = "
            SELECT c.*
            FROM courses c
            INNER JOIN LECTURER_COURSES lc ON lc.course_id = c.id
            WHERE lc.lecturer_id = ?
            ORDER BY c.id DESC
        ";

        $stmt = $this->res->prepare($sql);m
        if (!$stmt) {
            die("Prepare failed: " . $this->res->error);
        }

        $stmt->bind_param("i", $this->lecturerId);
        $stmt->execute();

        $result = $stmt->get_result();
        $courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $stmt->close();

        require __DIR__ . '/../Views/courses_index.php';
    }
}
