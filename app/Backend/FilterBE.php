<?php

namespace app\Backend;

use App\Helpers\ApiResponse;

class FilterBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getClassDetails()
    {
        $level = $_GET['level'] ?? '';
        $grade = $_GET['grade'] ?? '';
        $section = $_GET['section'] ?? '';

        $query  = "SELECT id, name FROM levels";
        $levels = $this->db->fetchAll($this->db->query($query));

        if($level!=''){
            $query = "SELECT id, name FROM grades WHERE level_id=$level";
            $grades = $this->db->fetchAll($this->db->query($query));
        }
        
        if($grade!=''){
            $query = "SELECT id, name FROM sections WHERE grade_id = $grade";
            $sections = $this->db->fetchAll($this->db->query($query));
        }

        if($section!=''){
            $query = "SELECT 
                        l.id AS level_id, g.id AS grade_id, s.id AS section_id, 
                        l.name AS level, g.name AS grade, s.name AS section 
                      FROM 
                        sections s 
                        INNER JOIN grades g ON s.grade_id = g.id
                        INNER JOIN levels l ON g.level_id = l.id
                      WHERE s.id = $section";
            $details = $this->db->fetchAll($this->db->query($query));   
        }

        $results = [
            "levels" => $levels ?? [],
            "grades" => $grades ?? [],
            "sections" => $sections ?? [],
            "details" => $details ?? []
        ];

        return ApiResponse::success($results);
    }
}