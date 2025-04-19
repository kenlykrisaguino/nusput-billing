<?php

namespace app\Backend;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';
use App\Helpers\ApiResponse as Response;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use DateTime;

class BillBE
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

        foreach ($months as $num => $month) {
            $monthQuery[] = "
                SUM(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_amount + b.late_fee ELSE 0 END) AS `$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_status ELSE '' END) AS `Status$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_detail ELSE '' END) AS `Detail$month`
            ";
        }

        $filterQuery = '';

        if (!empty($params['search'])) {
            $search = $params['search'];
            $filterQuery .= " AND (
                        u.nis LIKE '%$search%' OR
                        u.name LIKE '%$search%' OR
                        c.virtual_account LIKE '%$search%'
                    )";
        }

        $query .= implode(', ', $monthQuery);

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
            'semester' => $params['semester'],
        ];
    }

    public function createBills()
    {
        try {
            $this->db->beginTransaction();

            $logs = FormatHelper::formatSystemLog(LOG_CREATE_BILLS);

            $log_check = 'SELECT * FROM logs WHERE `log_name`= ?';
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
                                'amount' => (int) $student['monthly_fee'],
                            ],
                            [
                                'item_name' => LATE_FEE,
                                'amount' => 0,
                            ],
                        ],
                        'notes' => '',
                        'total' => (int) $student['monthly_fee'],
                    ];

                    $bills[] = [
                        'user_id' => $student['id'],
                        'virtual_account' => $student['virtual_account'],
                        'trx_id' => FormatHelper::FormatTransactionCode($student['level_name'], $student['virtual_account'], $month),
                        'trx_amount' => (int) $student['monthly_fee'],
                        'trx_detail' => $details,
                        'trx_status' => $months[0] == $month ? $this->status['active'] : $this->status['inactive'],
                        'late_fee' => 0,
                        'payment_due' => $due_date,
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
            $check_bills = ["SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[paid]') AND payment_due <= NOW()", "SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[active]') AND payment_due <= NOW()"];
            $log_attr = '';

            foreach ($check_bills as $check) {
                $result = $this->db->query($check);
                $data = $this->db->fetchAssoc($result);

                if ($data && $data['payment_due']) {
                    $log_attr = $data['payment_due'];
                    continue;
                }
            }

            if ($log_attr == '') {
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

            $log_check = 'SELECT * FROM logs WHERE `log_name`= ?';
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
            $this->db->query('DROP TEMPORARY TABLE IF EXISTS temp_bills');

            $this->db->insert('logs', $logs);
            $this->db->commit();
            return Response::success(true, 'Bills checked successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to check bills: ' . $e->getMessage(), 500);
        }
    }

    public function notifyBills()
    {
        $type = $_GET['type'] ?? '';
        $status = $this->status;

        $modifymonth = new DateTime();
        $now_formatted = $modifymonth;
        $now = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('first day of this month');
        $first_day = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('+8 days');
        $day_before = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('+2 days');
        $day_after = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('1 days');
        $last_day = $modifymonth->format(DATE_FORMAT);

        $day_int = intval($now_formatted->format('d'));
        $month_int = intval($now_formatted->format('m'));

        $current_month = FormatHelper::formatMonthNameInBahasa($month_int);

        $message = [
            BILL_STATUS_UNPAID => "Pembayaran SPP Bulan $current_month ",
            BILL_STATUS_PAID   => "Pembayaran SPP Bulan $current_month "
        ];

        $msg_valid = false;

        $notification_type =
            $type != ''
                ? $type
                : match (true) {
                    $now == $first_day => FIRST_DAY,
                    $now == $day_before => DAY_BEFORE,
                    $now == $day_after => DAY_AFTER,
                    default => NULL_VALUE,
                };
    
        switch($notification_type){
            case FIRST_DAY:
                $message[BILL_STATUS_UNPAID] .= "telah dibuka dan akan berakhir di tanggal *$last_day*. ";
                $msg_valid = true;
                break;
            case DAY_BEFORE:
                $message[BILL_STATUS_UNPAID] .= "akan berakhir besok, *$last_day*. ";
                $msg_valid = true;
                break;
            case DAY_AFTER:
                $message[BILL_STATUS_UNPAID] .= "belum dibayarkan. ";
                $message[BILL_STATUS_PAID]   .= "telah dibayarkan. ";                
                $msg_valid = true;
                break;
        }

        $message[BILL_STATUS_UNPAID] .= "Diharapkan dapat melakukan pembayaran sebagai beikut: \n\n";

        if (!$msg_valid) {
            return Response::error('Tidak mengirimkan notifikasi pembayaran');
        }

        $payment_due = "$last_day 23:59:59";

        $query = "SELECT
                    b.virtual_account AS va, l.name AS prefix, u.name AS name, u.parent_phone,
                    SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) + 
                    SUM(CASE WHEN b.trx_status IN ('$status[unpaid]','$status[active]') THEN b.trx_amount ELSE 0 END) AS total_payment
                  FROM
                    bills b JOIN 
                    users u ON b.user_id = u.id JOIN
                    user_class c ON u.id = c.user_id JOIN
                    levels l ON c.level_id = l.id
                  WHERE 
                    b.payment_due <= '$payment_due' AND
                    c.date_left IS NULL
                  GROUP BY
                    b.virtual_account, l.name, u.name, u.parent_phone";

        $data = $this->db->fetchAll($this->db->query($query));

        $msg_data = [];

        foreach ($data as $student){
            $trx_amount = FormatHelper::formatRupiah($student['total_payment']);
            $va = $student['va'];
            $va_name = $student['prefix'].'_'.$student['name'];
            $user_msg = $message[BILL_STATUS_UNPAID] . "Total Pembayaran: $trx_amount\nVirtual Account: BNI *$va* atas nama *$va_name*";
            $msg_data[] = [
                'target' => $student['parent_phone'],
                'message' => $user_msg,
                'delay' => '1'
            ];
        }

        if($notification_type == DAY_AFTER){
            $query = "SELECT 
                        u.name, u.parent_phone, p.trx_timestamp, p.details
                      FROM
                        payments p JOIN
                        bills b ON b.id = p.bill_id JOIN
                        users u ON u.id = b.user_id
                      WHERE 
                        b.payment_due IN ('$payment_due')";
            
            $data = $this->db->fetchAll($this->db->query($query));

            foreach($data as $student){
                $name = $student['name'];
                $timestamp = $student['trx_timestamp'];
                $user_msg = $message[BILL_STATUS_PAID]."Terima kasih kepada orang tua $name yang telah melakukan pembayaran pada tanggal *$timestamp*.";
                $msg_data[] = [
                    'target' => $student['parent_phone'],
                    'message' => $user_msg,
                    'delay' => '1'
                ];
            }

        }
        if (empty($msg_data)) {
            return Response::error("Tidak ada pembayaran yang harus dibayar");
        }

        $messages = json_encode($msg_data);
        $fonnte = Fonnte::sendMessage(['data' => $messages]);

        return Response::success([
            'fonnte_response' => $fonnte,
            'messages' => $msg_data
        ], "Notifikasi pembayaran SPP bulan $current_month berhasil dikirim");
    }
}
