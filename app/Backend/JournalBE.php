<?php

namespace app\Backend;

use App\Helpers\Call;
use DateTime;

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

    protected function getUnpaidTransaction($params = [])
    {
        $status = $this->status;  

        $paramQuery = NULL_VALUE;

        if($params['search'] != NULL_VALUE){
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
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

        if($params['start_date'] != NULL_VALUE & $params['end_date'] != NULL_VALUE ){
            $start_date = $params['start_date'];
            $end_date   = $params['end_date'];
            
            $paramQuery .= " AND b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        }

        $query = "SELECT 
            SUM(CASE
                WHEN b.trx_status IN ('$status[active]', '$status[unpaid]') THEN b.trx_amount
                ELSE 0 
            END) AS bank
        FROM
            bills b JOIN
            users u ON b.user_id = u.id LEFT JOIN
            user_class c ON b.virtual_account = c.virtual_account LEFT JOIN
            levels l ON l.id = c.level_id LEFT JOIN
            grades g ON g.id = c.grade_id LEFT JOIN
            sections s ON s.id = c.section_id
        WHERE 
            TRUE $paramQuery";

        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }
    protected function getTransaction($params = [])
    {
        $paramQuery = NULL_VALUE;

        if($params['search'] != NULL_VALUE){
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
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

        if($params['start_date'] != NULL_VALUE & $params['end_date'] != NULL_VALUE ){
            $start_date = $params['start_date'];
            $end_date   = $params['end_date'];
            
            $paramQuery .= " AND b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        }

        $query = "SELECT 
            SUM(p.trx_amount) AS amount
        FROM
            payments p LEFT JOIN
            bills b ON p.bill_id = b.id LEFT JOIN
            users u ON b.user_id = u.id LEFT JOIN
            user_class c ON b.virtual_account = c.virtual_account LEFT JOIN
            levels l ON l.id = c.level_id LEFT JOIN
            grades g ON g.id = c.grade_id LEFT JOIN
            sections s ON s.id = c.section_id
        WHERE 
            TRUE $paramQuery";


        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }

    protected function getLateFee($params = []){
        $status = $this->status;  

        $start_date = $params['start_date'];
        $end_date   = $params['end_date'];

        $query = "SELECT 
            SUM(CASE
                WHEN b.trx_status IN ('$status[unpaid]') THEN b.late_fee
                ELSE 0 
            END) AS late_fee
        FROM
            bills b JOIN
            users u ON b.user_id = u.id LEFT JOIN
            user_class c ON b.virtual_account = c.virtual_account LEFT JOIN
            levels l ON l.id = c.level_id LEFT JOIN
            grades g ON g.id = c.grade_id LEFT JOIN
            sections s ON s.id = c.section_id
        WHERE 
            b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        
        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }
    protected function getPaidLateFee($params = []){
        $status = $this->status;  

        $start_date = $params['start_date'];
        $end_date   = $params['end_date'];

        $query = "SELECT 
            SUM(CASE
                WHEN b.trx_status IN ('$status[late]') THEN b.late_fee
                ELSE 0 
            END) AS late_fee
        FROM
            bills b JOIN
            users u ON b.user_id = u.id LEFT JOIN
            user_class c ON b.virtual_account = c.virtual_account LEFT JOIN
            levels l ON l.id = c.level_id LEFT JOIN
            grades g ON g.id = c.grade_id LEFT JOIN
            sections s ON s.id = c.section_id
        WHERE 
            b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        
        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }

    public function getJournals()
    {   
        $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCHECK-%' ORDER BY created_at DESC LIMIT 1";
        $logName = $this->db->fetchAssoc($this->db->query($log));

        $academicYear = Call::academicYear();
        $splitDate = Call::splitDate();

        if($logName == null){
            $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCREATE-%' ORDER BY created_at DESC LIMIT 1";
            $logName = $this->db->fetchAssoc($this->db->query($log));
            
            if($logName == null){
                return [
                    'per_first_day'   => 0,
                    'per_tenth_day'   => 0,
                    'late_fee_amount' => 0,
                    'paid_late_fee'   => 0
                ];
            } 

            list($title, $semester, $year) = explode('-', $logName['log_name']);
            $semester = $semester === FIRST_SEMESTER ? 1 : 2;
            $monthInt = $semester === 1 ? 7 : 1;
            $monthInt = $splitDate['day'] < 10 ? $monthInt + 1 : $monthInt;
        } else {
            list($title, $semester, $year, $monthInt) = explode('-', $logName['log_name']);
            $semester = Call::semester() == FIRST_SEMESTER ? 1 : 2;
            $monthInt = $splitDate['day'] < 10 ? $monthInt + 1 : $monthInt;
        }
    
        $month = sprintf('%02d', $monthInt);

        $params = [
            'search' => $_GET['search'] ??  NULL_VALUE,
            'academic_year' => $_GET['year-filter'] ??  $academicYear,
            'semester' => $_GET['semester-filter'] ??  $semester,
            'month' => $_GET['month-filter'] ??  $month,
            'level' => $_GET['level-filter'] ??  NULL_VALUE,
            'grade' => $_GET['grade-filter'] ??  NULL_VALUE,
            'section' => $_GET['section-filter'] ??  NULL_VALUE,
        ];        

        $startSemester = Call::getFirstDay([
            'year' => $params['academic_year'],
            'semester' => $params['semester'],
            'month' => $params['semester'] == 1 ? '07' : '01'
        ], FIRST_DAY_FROM_ACADEMIC_YEAR_DETAILS);

        $modifyDate = new DateTime("01-$month-$year");
        $startMonth = $modifyDate->format(DATE_FORMAT);


        if((int)$splitDate['day'] < 10){
            $modifyDate = new DateTime("10-$params[month]-$year");
            if($params['month'] != $month){
                $modifyDate->modify('last day of this month');
            }
            $endRange = $modifyDate->format(DATE_FORMAT);
            $modifyDate->modify('last day of this month');
            $dueDateParamMonth = $modifyDate->format(DATE_FORMAT);
        }

        $startDateParams = array_merge($params, ['start_date' => $startSemester, 'end_date' => $endRange]);
        $dueDateParams = array_merge($params, ['start_date' => $startMonth, 'end_date' => $dueDateParamMonth]);
        $startDateResult = $this->getUnpaidTransaction($startDateParams);
        $dueDateResult   = $this->getTransaction($dueDateParams);
        $lateFeeResult   = $this->getLateFee($startDateParams);
        $paidLateResult  = $this->getPaidLateFee($startDateParams);
        return [
            'per_first_day'   => $startDateResult['bank'],
            'per_tenth_day'   => $dueDateResult['amount'],
            'late_fee_amount' => $lateFeeResult['late_fee'],
            'paid_late_fee'   => $paidLateResult['late_fee']
        ];
    }
}