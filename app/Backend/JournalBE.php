<?php

namespace app\Backend;

class JournalBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getJournals()
    {        
        $query = "";

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }
}