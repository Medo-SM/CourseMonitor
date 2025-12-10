<?php

class CourseController
{
    private $res;
    private $teacherId = 1; // temporary until login exists

    public function __construct($res)
    {
        $this->res = $res;
    }

    public function index()
    {
        $tid = $this->teacherId;

        $query = "SELECT * FROM courses WHERE teacher_id = $tid";
        $result = $this->res->query($query);

        $courses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }

        require __DIR__ . '/../Views/courses_index.php';
    }
}
