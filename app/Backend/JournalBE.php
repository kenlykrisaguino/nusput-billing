<?php

namespace app\Backend;

class JournalBE
{
    private $db;

    private $status = [
        'paid' => BILL_STATUS_PAID,
        'unpaid' => BILL_STATUS_UNPAID,
        'late' => BILL_STATUS_LATE,
        'inactive' => BILL_STATUS_INACTIVE,
        'active' => BILL_STATUS_ACTIVE,
        'disabled' => BILL_STATUS_DISABLED,
    ];

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getJournals()
    {   
        $status = $this->status;  
        
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
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
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
            SUM(CASE
                WHEN b.trx_status IN ('$status[paid]', '$status[late]') THEN b.trx_amount
                ELSE 0 
            END) AS bank,
            SUM(CASE
                WHEN b.trx_status IN ('$status[unpaid]') THEN b.late_fee
                ELSE 0 
            END) AS denda
        FROM
            bills b JOIN
            users u ON b.user_id = u.id LEFT JOIN
            user_class c ON b.virtual_account = c.virtual_account JOIN
            levels l ON l.id = c.level_id LEFT JOIN
            grades g ON g.id = c.grade_id LEFT JOIN
            sections s ON s.id = c.section_id
        WHERE 
            TRUE $paramQuery";

        $result = $this->db->fetchAssoc($this->db->query($query));
        return $result;
    }
}