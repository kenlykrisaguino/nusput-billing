<?php

namespace app\Backend;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';

class RecapBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getRecaps()
    {        
        $status = [
            'paid' => BILL_STATUS_PAID,
            'unpaid' => BILL_STATUS_UNPAID,
            'late' => BILL_STATUS_LATE,
            'inactive' => BILL_STATUS_INACTIVE,
            'active' => BILL_STATUS_ACTIVE,
            'disabled' => BILL_STATUS_DISABLED
        ];

        $params = [
            'search' => $_GET['search'] ??  NULL_VALUE,
            'academic_year' => $_GET['year-filter'] ??  NULL_VALUE,
            'semester' => $_GET['semester-filter'] ??  NULL_VALUE,
            'month' => $_GET['month-filter'] ??  NULL_VALUE,
            'level' => $_GET['level-filter'] ??  NULL_VALUE,
            'grade' => $_GET['grade-filter'] ??  NULL_VALUE,
            'section' => $_GET['section-filter'] ??  NULL_VALUE,
        ];

        $paramQuery = NULL_VALUE;

        if($params['search'] != NULL_VALUE){
            $paramQuery .= " AND u.name LIKE '%$params[search]%'";
        }

        if($params['academic_year'] != NULL_VALUE){
            $academicYear = explode('/', $params['academic_year'], 2);
            $years = [
                'min' => "$academicYear[0]-07-01",
                'max' => "$academicYear[1]-06-30"
            ];

            $paramQuery .= " AND b.payment_due BETWEEN '$years[min]' AND '$years[max]' ";
        }

        if($params['semester'] != NULL_VALUE){
            $year = explode('/', $params['academic_year'], 2);

            if ($params['semester'] == 2) {
                $paramQuery .= " AND YEAR(b.payment_due) = $year[1]";
            } else {
                $paramQuery .= " AND YEAR(b.payment_due) = $year[0]";
            }
        }

        if($params['month'] != NULL_VALUE){
            $paramQuery .= " AND MONTH(b.payment_due) = $params[month]";
        }

        if($params['level'] != NULL_VALUE){
            $paramQuery .= " AND l.id = $params[level]";
        }
        if($params['grade'] != NULL_VALUE){
            $paramQuery .= " AND g.id = $params[grade]";
        }
        if($params['section'] != NULL_VALUE){
            $paramQuery .= " AND s.id = $params[section]";
        }

        $query = "SELECT
                  c.virtual_account, u.name, u.parent_phone,
                  CONCAT(
                    COALESCE(l.name, ''),
                    ' ', 
                    COALESCE(g.name, ''), 
                    ' ', 
                    COALESCE(s.name, '')
                  ) AS class_name,
                  COALESCE(SUM(CASE 
                    WHEN b.trx_status = '{$status['paid']}' 
                    OR b.trx_status = '{$status['late']}' 
                    THEN b.trx_amount ELSE 0 END), 0) 
                    +
                    COALESCE(SUM(CASE 
                        WHEN b.trx_status = '{$status['late']}' 
                        THEN b.late_fee ELSE 0 END), 0) 
                    AS penerimaan,
                    COALESCE(SUM(CASE 
                        WHEN b.trx_status = '{$status['unpaid']}' 
                        THEN b.late_fee ELSE 0 END), 0) 
                    AS tunggakan
                  FROM bills b
                  INNER JOIN users u ON b.user_id = u.id
                  INNER JOIN user_class c ON u.id = c.user_id
                  LEFT JOIN levels l ON c.level_id = l.id
                  LEFT JOIN grades g ON c.grade_id = g.id
                  LEFT JOIN sections s ON c.section_id = s.id
                  WHERE 
                    TRUE 
                    $paramQuery
                  GROUP BY c.virtual_account, u.name, u.parent_phone,
                  CONCAT(
                    COALESCE(l.name, ''),
                    ' ', 
                    COALESCE(g.name, ''), 
                    ' ', 
                    COALESCE(s.name, '')
                  )";
        
        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }
}