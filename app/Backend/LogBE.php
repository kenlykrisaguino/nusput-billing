<?php

namespace app\Backend;

class LogBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getLogs()
    {        
        $query = "SELECT * FROM logs ORDER BY created_at";

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }
}