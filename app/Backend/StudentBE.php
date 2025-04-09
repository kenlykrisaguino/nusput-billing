<?php

namespace app\Backend;

class StudentBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getStudents($params = [])
    {        
        $query = "SELECT
                  u.nis, u.name, l.name AS level, g.name AS grade, s.name AS section,
                  u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee,
                  MAX(p.trx_timestamp) AS latest_payment
                  FROM users u
                  LEFT JOIN user_class c ON u.id = c.user_id
                  INNER JOIN levels l ON c.level_id = l.id
                  INNER JOIN grades g ON c.grade_id = g.id
                  INNER JOIN sections s ON c.section_id = s.id
                  LEFT JOIN payments p ON u.id = p.user_id
                  WHERE u.role = 'ST' AND c.date_left IS NULL
                  ";

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query .= " AND (
                        u.nis LIKE '%$search%' OR 
                        u.name LIKE '%$search%' OR 
                        u.phone LIKE '%$search%' OR 
                        u.email LIKE '%$search%' OR 
                        u.parent_phone LIKE '%$search%' OR 
                        c.virtual_account LIKE '%$search%'
                    )";
        }

        $query .= " GROUP BY
                u.nis, u.name, l.name, g.name, s.name,
                u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee";

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }
}