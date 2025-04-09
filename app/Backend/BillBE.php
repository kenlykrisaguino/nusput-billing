<?php

namespace app\Backend;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';
use App\Helpers\ApiResponse as Response;
use App\Helpers\Call;
use App\Helpers\FormatHelper;

class BillBE
{
    private $db;
    private $status = [
        'paid' => BILL_STATUS_PAID,
        'unpaid' => BILL_STATUS_UNPAID,
        'late' => BILL_STATUS_LATE,
        'inactive' => BILL_STATUS_INACTIVE,
        'active' => BILL_STATUS_ACTIVE,
        'disabled' => BILL_STATUS_DISABLED
    ];

    public function __construct($database)
    {
        $this->db = $database;
    }
    

    public function getBills($params = [])
    {           
        $status = $this->status;

        $params['semester'] = $params['semester'] ?? Call::semester();
        $params['academic_year'] = $params['academic_year'] ?? Call::academicYear();

        $filterYear = $params['semester'] == SECOND_SEMESTER ? substr($params['academic_year'], -4) : substr($params['academic_year'], -4) - 1;
        $finalMonth = $params['semester'] == SECOND_SEMESTER ? 6 : 12;

        $query = "SELECT 
                  b.virtual_account, u.nis, u.name,
                  CONCAT(
                    COALESCE(l.name, ''),
                    ' ', 
                    COALESCE(g.name, ''), 
                    ' ', 
                    COALESCE(S.name, '')
                  ) AS class_name,
                  SUM(CASE WHEN b.trx_status = '$status[late]' THEN c.late_fee ELSE 0 END) + SUM(CASE WHEN b.trx_status = '$status[paid]' OR b.trx_status = '$status[late]' THEN b.trx_amount ELSE 0 END) AS penerimaan,
                  SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) + SUM(CASE WHEN b.trx_status IN ('$status[active]', '$status[unpaid]') THEN b.trx_amount ELSE 0 END) AS tagihan, 
                  (SELECT SUM(late_fee) FROM bills nb WHERE nb.virtual_account = b.virtual_account AND MONTH(nb.payment_due) <= $finalMonth AND YEAR(nb.payment_due)<=$filterYear) AS tunggakan, ";
    
        $months = Call::monthNameSemester($params['semester']);

        $monthQuery = [];

        foreach($months as $num => $month){
            $monthQuery[] = "
                SUM(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_amount + b.late_fee ELSE 0 END) AS `$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_status ELSE '' END) AS `Status$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_detail ELSE '' END) AS `Detail$month` 
            ";
        }

        $filterQuery = "";

        if (!empty($params['search'])) {
            $search = $params['search'];
            $filterQuery .= " AND (
                        u.nis LIKE '%$search%' OR 
                        u.name LIKE '%$search%' OR 
                        c.virtual_account LIKE '%$search%'
                    )";
        }

        $query .= implode(", ", $monthQuery);

        $query .= " FROM 
                   bills b 
                   INNER JOIN users u ON b.user_id = u.id
                   INNER JOIN user_class c ON u.id = c.user_id
                   INNER JOIN levels l ON c.level_id = l.id
                   INNER JOIN grades g ON c.grade_id = g.id
                   INNER JOIN sections S ON c.section_id = S.id
                   WHERE TRUE $filterQuery";

        $query .= "GROUP BY 
                  b.virtual_account, u.nis, u.name,
                  CONCAT(
                    COALESCE(l.name, ''),
                    ' ', 
                    COALESCE(g.name, ''), 
                    ' ', 
                    COALESCE(S.name, '')
                  ) ";
        
        $result = $this->db->query($query);
        $data = $this->db->fetchAll($result);
        
        return [
            'data' => $data,
            'semester' => $params['semester']
        ];
    }

    public function createBills()
    {
        try {
            $this->db->beginTransaction();
    
            $logs = FormatHelper::formatSystemLog(LOG_CREATE_BILLS);
    
            $log_check = "SELECT * FROM logs WHERE `log_name`= ?";
            $log_check_result = $this->db->query($log_check, [$logs['log_name']]);
    
            if ($log_check_result && $this->db->fetchAssoc($log_check_result)) {
                $this->db->rollback();
                return Response::error('Bills have been created before', 404);
            }
    
            $months = Call::monthSemester();
            $date = Call::splitDate();
            $role = USER_ROLE_STUDENT;
    
            $students_query = "
                SELECT 
                    u.id, u.name, c.monthly_fee, 
                    c.late_fee, c.virtual_account, c.date_joined, 
                    c.date_left, l.name AS level_name,
                    CONCAT(
                        COALESCE(l.name, ''),
                        ' ', 
                        COALESCE(g.name, ''), 
                        ' ', 
                        COALESCE(S.name, '')
                    ) AS class_name
                FROM 
                    users u 
                    INNER JOIN user_class c ON u.id = c.user_id
                    INNER JOIN levels l ON c.level_id = l.id
                    INNER JOIN grades g ON c.grade_id = g.id
                    INNER JOIN sections S ON c.section_id = S.id
                WHERE u.role = ? AND c.date_left IS NULL
            ";
            $student_result = $this->db->query($students_query, [$role]);
            $students = $this->db->fetchAll($student_result);
    
            $bills = [];
    
            foreach ($students as $student) {
                foreach ($months as $month) {
                    $due_date = date(TIMESTAMP_FORMAT, strtotime("$month/10/{$date['year']}"));
    
                    $details = [
                        'name' => $student['name'],
                        'class' => $student['class_name'],
                        'virtual_account' => $student['virtual_account'],
                        'billing_month' => "$month/{$date['year']}",
                        'due_date' => $due_date,
                        'payment_date' => null,
                        'status' => $months[0] == $month ? $this->status['active'] : $this->status['inactive'],
                        'items' => [
                            [
                                'item_name' => MONTHLY_FEE,
                                'amount' => (int) $student['monthly_fee']
                            ],
                            [
                                'item_name' => LATE_FEE,
                                'amount' => 0
                            ]
                        ],
                        'notes' => '',
                        'total' => (int) $student['monthly_fee']
                    ];
    
                    $bills[] = [
                        'user_id' => $student['id'],
                        'virtual_account' => $student['virtual_account'],
                        'trx_id' => FormatHelper::FormatTransactionCode($student['level_name'], $student['virtual_account'], $month),
                        'trx_amount' => (int) $student['monthly_fee'],
                        'trx_detail' => $details, 
                        'trx_status' => $months[0] == $month ? $this->status['active'] : $this->status['inactive'],
                        'late_fee' => 0,
                        'payment_due' => $due_date
                    ];
                }
            }
    
            $bill_result = $this->db->insert('bills', $bills);
    
            $this->db->insert('logs', $logs);
    
            $this->db->commit();
            return Response::success($bill_result, 'Bills created successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to create bills: ' . $e->getMessage(), 500);
        }
    }

    public function checkBills()
    {
        try {
            $this->db->beginTransaction();
            
            $status = $this->status;

            // ! INISIASI KALAU ENGGA ADA BILLS SAMA SEKALI SEBELUMNYA
            $check_bills = [
                "SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[paid]') AND payment_due <= NOW()",
                "SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[active]') AND payment_due <= NOW()"
            ];
            $log_attr = '';

            foreach ($check_bills as $check){
                $result = $this->db->query($check);
                $data = $this->db->fetchAssoc($result);

                
                if ($data && $data['payment_due']) {
                    $log_attr = $data['payment_due'];
                    continue;
                }
            }

            if($log_attr == ''){
                $this->db->rollback();
                return Response::error('No bills to check', 404);
            }

            $semester = Call::semester($log_attr);
            $date = Call::splitDate($log_attr);
            $logs = FormatHelper::formatSystemLog(LOG_CHECK_BILLS, [
                'semester' => $semester,
                'year' => $date['year'],
                'month' => $date['month'],
            ]);

            $log_check = "SELECT * FROM logs WHERE `log_name`= ?";
            $log_check_result = $this->db->query($log_check, [$logs['log_name']]);

            if ($log_check_result && $this->db->fetchAssoc($log_check_result)) {
                $this->db->rollback();
                return Response::error('Bills have been checked before', 404);
            }

            $temp_check_table = "CREATE TEMPORARY TABLE temp_bills AS
                SELECT b.id, next_b.id AS next_b_id, b.payment_due, b.virtual_account
                FROM bills b
                LEFT JOIN bills next_b ON next_b.virtual_account = b.virtual_account
                AND next_b.payment_due = (
                    SELECT MIN(nb.payment_due)
                    FROM bills nb
                    WHERE nb.virtual_account = b.virtual_account
                    AND nb.payment_due > b.payment_due
                    AND nb.trx_status != '$status[active]'
                )
                WHERE b.trx_status IN ('$status[active]', '$status[paid]', '$status[unpaid]')
                AND b.payment_due < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ";

            $this->db->query($temp_check_table);

            $updateBills = "UPDATE bills b
                LEFT JOIN temp_bills t ON b.id = t.id
                LEFT JOIN bills next_b ON next_b.id = t.next_b_id
                LEFT JOIN user_class c ON c.virtual_account = b.virtual_account AND c.date_left IS NULL
                SET
                    b.trx_status = CASE
                        WHEN b.trx_status = '$status[active]' THEN '$status[unpaid]'
                        WHEN b.trx_status = '$status[disabled]' THEN '$status[disabled]'
                        ELSE b.trx_status
                    END,
                    b.late_fee = CASE
                        WHEN b.trx_status = '$status[active]' THEN COALESCE(c.late_fee, 0)
                        WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee + COALESCE(c.late_fee, 0)
                        WHEN b.trx_status = '$status[disabled]' THEN 0
                        ELSE b.late_fee
                    END,
                    next_b.trx_status = CASE
                        WHEN b.trx_status IN ('$status[late]', '$status[unpaid]')
                            AND next_b.trx_status = '$status[inactive]'
                            AND next_b.payment_due <= DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        THEN '$status[active]'
                        WHEN b.trx_status IN ('$status[active]', '$status[paid]')
                            AND next_b.trx_status = '$status[inactive]'
                            AND next_b.payment_due <= DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        THEN '$status[active]'
                        ELSE next_b.trx_status
                    END,
                    b.trx_detail = CASE
                        WHEN b.trx_status IN ('$status[active]', '$status[unpaid]') THEN
                            JSON_SET(
                                b.trx_detail,
                                '$.status',
                                    CASE
                                        WHEN b.trx_status = '$status[active]' THEN '$status[unpaid]'
                                        ELSE JSON_EXTRACT(b.trx_detail, '$.status')
                                    END,
                                '$.total',
                                    CAST(JSON_EXTRACT(b.trx_detail, '$.total') AS DECIMAL(14,2)) + COALESCE(c.late_fee, 0),
                                '$.items[1].amount',
                                    CASE
                                        WHEN b.trx_status = '$status[active]' THEN COALESCE(c.late_fee, 0)
                                        WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee + COALESCE(c.late_fee, 0)
                                        ELSE JSON_EXTRACT(b.trx_detail, '$.items[1].amount')
                                    END
                            )
                        ELSE
                            b.trx_detail
                    END,
                    next_b.trx_detail = CASE
                        WHEN b.trx_status IN ('$status[active]', '$status[unpaid]')
                            AND next_b.trx_status = '$status[inactive]'
                            AND next_b.payment_due <= DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        THEN JSON_SET(next_b.trx_detail, '$.status', '$status[active]')

                        WHEN b.trx_status IN ('$status[active]', '$status[paid]')
                            AND next_b.trx_status = '$status[inactive]'
                            AND next_b.payment_due <= DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        THEN JSON_SET(next_b.trx_detail, '$.status', '$status[active]') 
                        ELSE next_b.trx_detail 
                    END
                WHERE b.trx_status IN ('$status[active]', '$status[paid]', '$status[unpaid]')
                AND b.payment_due < DATE_SUB(NOW(), INTERVAL 24 HOUR);";

            $this->db->query($updateBills);
            $this->db->query("DROP TEMPORARY TABLE IF EXISTS temp_bills");

            $this->db->insert('logs', $logs);
            $this->db->commit();
            return Response::success(true, 'Bills checked successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to check bills: ' . $e->getMessage(), 500);
        }

    }

    
}