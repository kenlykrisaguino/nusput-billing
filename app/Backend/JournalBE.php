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

    public function getJournals($params = [])
    {   
        $status = $this->status;     

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
                    user_class c ON b.virtual_account = c.virtual_account JOIN
                    levels l ON l.id = c.level_id JOIN
                    grades g ON g.id = c.grade_id JOIN
                    sections s ON s.id = c.section_id
                  WHERE 
                    TRUE";

        if($params['year'] != ""){
            $v = $params['year'];
            $query .= " AND YEAR(b.payment_due) = $v";

            if($params['semester'] != ''){
                $v = $params['semester'];
                $query .= $v <= 6 ? " AND MONTH(b.payment_due) <= 6" : " AND MONTH(b.payment_due) > 6";
                
                if($params['month'] != ''){
                    $v = $params['month'];
                    $query .= " AND MONTH(b.payment_due) = $v";
                }
            }
        }

        if($params['level'] != ''){
            $v = $params['level'];
            $query .= " AND l.id = $v";

            if($params['grade'] != ''){
                $v = $params['level'];
                $query .= " AND g.id = $v";

                if($params['section'] != ''){
                    $v = $params['level'];
                    $query .= " AND s.id = $v";
                }
            }
        }

        if($params['va'] != ''){
            $v = $params['va'];
            $query .= " AND b.virtual_account = $v";
        }

        $result = $this->db->fetchAll($this->db->query($query));
        return $result;
    }
}