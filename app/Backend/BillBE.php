<?php

namespace app\Backend;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';
use App\Backend\JournalBE;
use App\Helpers\ApiResponse as Response;
use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BillBE
{
    private $db;
    private $journal;
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
        $this->journal = new JournalBE($database);
    }

    public function getBills()
    {
        $status = $this->status;

        $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCREATE-%' ORDER BY created_at DESC LIMIT 1";
        $logName = $this->db->fetchAssoc($this->db->query($log));

        if(!empty($logName)){
            [$title, $semester, $year] = explode('-', $logName['log_name']);
        } else {
            $semester = Call::semester();
            $year = Call::year();
        }

        if ($semester == SECOND_SEMESTER) {
            $periodDate = "01-07-$year";
        } else {
            $periodDate = "01-01-$year";
        }

        $date = Call::splitDate($periodDate);
        $academicYear = Call::academicYear(ACADEMIC_YEAR_EIGHT_SLASH_FORMAT, [
            'semester' => $semester,
            'date' => $date,
        ]);

        $params['semester'] = $_GET['semester-filter'] ?? $semester;
        $params['academic_year'] = $_GET['year-filter'] ?? $academicYear;

        $params['semester'] = $params['semester'] == 1 || $params['semester'] == FIRST_SEMESTER ? FIRST_SEMESTER : SECOND_SEMESTER;

        $filterYear = $params['semester'] == SECOND_SEMESTER ? substr($params['academic_year'], -4) : substr($params['academic_year'], -4) - 1;
        $finalMonth = $params['semester'] == SECOND_SEMESTER ? 6 : 12;

        $query = "SELECT
                    MAX(suc.virtual_account) AS virtual_account, u.nis, u.name,
                    CONCAT(
                    COALESCE(l.name, ''),
                    ' ',
                    COALESCE(g.name, ''),
                    ' ',
                    COALESCE(s.name, '')
                    ) AS class_name,
                    SUM(CASE WHEN b.trx_status = '{$status['late']}' THEN suc.late_fee ELSE 0 END) + SUM(CASE WHEN b.trx_status = '{$status['paid']}' OR b.trx_status = '{$status['late']}' THEN b.trx_amount ELSE 0 END) AS penerimaan,
                    SUM(CASE WHEN b.trx_status = '{$status['unpaid']}' THEN b.late_fee ELSE 0 END) + SUM(CASE WHEN b.trx_status IN ('{$status['active']}', '{$status['unpaid']}') THEN b.trx_amount ELSE 0 END) AS tagihan,
                    (SELECT SUM(nb.late_fee) FROM bills nb JOIN users nu ON nu.id = nb.user_id LEFT JOIN user_class nuc ON nu.id = nuc.user_id WHERE nuc.virtual_account = suc.virtual_account AND MONTH(nb.payment_due) <= {$finalMonth} AND YEAR(nb.payment_due)<={$filterYear} AND nb.trx_status = '{$status['unpaid']}') AS tunggakan";

        $months = Call::monthNameSemester($params['semester']);

        $monthQuery = [];

        foreach ($months as $num => $month) {
            $monthQuery[] = "
                SUM(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_amount + b.late_fee ELSE 0 END) AS `$month`,
                SUM(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.late_fee ELSE 0 END) AS `Late$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_status ELSE '' END) AS `Status$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.trx_detail ELSE '' END) AS `Detail$month`,
                MAX(CASE WHEN MONTH(b.payment_due) = $num AND YEAR(b.payment_due) = $filterYear THEN b.id ELSE 0 END) AS `BillId$month`
            ";
        }

        $filterQuery = '';

        if (!empty($params['search'])) {
            $search = $params['search'];
            $filterQuery .= " AND (
                        u.nis LIKE '%$search%' OR
                        u.name LIKE '%$search%' OR
                        suc.virtual_account LIKE '%$search%'
                    )";
        }

        if ($params['semester'] == SECOND_SEMESTER) {
            $min = "$filterYear-01-01";
            $max = "$filterYear-06-30";
        } else {
            $min = "$filterYear-07-01";
            $max = "$filterYear-12-31";
        }

        if (!empty($monthQuery)) {
            $query .= ", " . implode(', ', $monthQuery);
        }

        $query .= "FROM
                    bills b
                    INNER JOIN users u ON b.user_id = u.id
                    LEFT JOIN (
                        SELECT uc1.*
                        FROM user_class uc1
                        LEFT JOIN user_class uc2
                            ON uc1.user_id = uc2.user_id
                            AND (
                                (uc2.date_left IS NULL AND uc1.date_left IS NOT NULL)
                            OR
                                (uc2.date_left > uc1.date_left AND uc2.date_left IS NOT NULL)
                            OR
                                (uc1.date_left <=> uc2.date_left AND uc2.id > uc1.id)
                            )
                        WHERE uc2.user_id IS NULL
                    ) AS suc ON u.id = suc.user_id
                    LEFT JOIN levels l ON suc.level_id = l.id
                    LEFT JOIN grades g ON suc.grade_id = g.id
                    LEFT JOIN sections s ON suc.section_id = s.id
                WHERE
                    TRUE $filterQuery";

        $query .= " GROUP BY
                  suc.virtual_account, u.nis, u.name,
                  CONCAT(
                    COALESCE(l.name, ''),
                    ' ',
                    COALESCE(g.name, ''),
                    ' ',
                    COALESCE(s.name, '')
                  )";

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
                        COALESCE(s.name, '')
                    ) AS class_name
                FROM
                    users u
                    LEFT JOIN user_class c ON u.id = c.user_id
                    LEFT JOIN levels l ON c.level_id = l.id
                    LEFT JOIN grades g ON c.grade_id = g.id
                    LEFT JOIN sections s ON c.section_id = s.id
                WHERE u.role = ? AND c.date_left IS NULL
            ";
            $student_result = $this->db->query($students_query, [$role]);
            $students = $this->db->fetchAll($student_result);

            $bills = [];
            $academicYear = Call::academicYear();

            foreach ($students as $student) {
                foreach ($months as $month) {
                    $due_date = date(TIMESTAMP_FORMAT, strtotime("$month/10/{$date['year']} 23:59:59"));

                    $details = [
                        'name' => $student['name'],
                        'class' => $student['class_name'],
                        'virtual_account' => $student['virtual_account'],
                        'academic_year' => $academicYear,
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
            $this->sendToAKTSystem($months[0], $date['year']);
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
            $check_bills = ["SELECT MAX(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[paid]') AND payment_due <= NOW()", "SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[active]') AND payment_due <= NOW()"];
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

            $this->sendToAKTSystem($date['month'] + 1, $date['year']);

            return Response::success(true, 'Bills checked successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to check bills: ' . $e->getMessage(), 500);
        }
    }

    protected function sendToAKTSystem($month, $year)
    {
        // ! Kirim ke Sistem AKT
        $levels = $this->db->fetchAll($this->db->query('SELECT * FROM levels'));
        $base_url = rtrim($_ENV['ACCOUNTING_SYSTEM_URL'], '/');
        $url = "$base_url/page/transaksi/backend/create.php";
        // TODO: Store the data that are being sent
        $journalData = [];

        $academic_year = Call::academicYear(ACADEMIC_YEAR_AKT_FORMAT);

        $smk1 = ['SMK TKJ', 'SMK MM'];

        $import_akt_data = [
            'tahun_ajaran' => $academic_year,
            'sumber_dana' => 'Rutin',
        ];

        foreach ($levels as $level) {
            $journals = $this->journal->getJournals($level['id'], true);
            if (in_array($level['name'], $smk1)) {
                if (!isset($journalData['SMK1'])) {
                    $journalData['SMK1'] = [
                        'PTUS' => 0.0,
                        'PLUS' => 0.0,
                        'PNBD' => 0.0,
                        'PBDL' => 0.0,
                    ];
                }
                $journalData['SMK1']['PTUS'] += $journals['per_first_day'];
                $journalData['SMK1']['PLUS'] += $journals['per_tenth_day'];
                $journalData['SMK1']['PNBD'] += $journals['late_fee_amount'];
                $journalData['SMK1']['PBDL'] += $journals['late_fee_amount'];
            } else {
                $journalData[$level['name']] = [
                    'PTUS' => $journals['per_first_day'] ?? 0.0,
                    'PLUS' => $journals['per_tenth_day'] ?? 0.0,
                    'PNBD' => $journals['late_fee_amount'] ?? 0.0,
                    'PBDL' => $journals['paid_late_fee'] ?? 0.0,
                ];
            }
        }

        $bulan = FormatHelper::formatMonthNameInBahasa($month);
        $bulan_sebelum = FormatHelper::formatMonthNameInBahasa($month - 1);
        $postValue = [];
        foreach ($journalData as $level => $journalLevel) {
            foreach ($journalLevel as $code => $amount) {
                if ($amount != 0) {
                    $postValue[] = [
                        'kode_transaksi' => $code,
                        'tahun_ajaran' => $import_akt_data['tahun_ajaran'],
                        'sumber_dana' => $import_akt_data['sumber_dana'],
                        'nama_jenjang' => $level,
                        'saldo' => $amount,
                        'bulan' => $code == 'PLUS' ? $bulan_sebelum : $bulan,
                    ];
                }
            }
        }

        $json_data = json_encode($postValue);
        $headers = ['Content-Type: application/json'];

        // TODO: Setup cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
        exit();
    }

    public function notifyBills()
    {
        $type = $_GET['type'] ?? '';
        $status = $this->status;

        $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCHECK-%' ORDER BY created_at DESC LIMIT 1";
        $logName = $this->db->fetchAssoc($this->db->query($log));

        if ($logName == null) {
            $this->db->commit();
            return [
                'status' => true,
            ];
        }
        [$title, $semester, $year, $monthInt] = explode('-', $logName['log_name']);

        $month = sprintf('%02d', ((int) $monthInt) + 1);

        $modifymonth = new DateTime("1-$month-$year");
        $now_formatted = $modifymonth;
        $now = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('first day of this month');
        $first_day = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('+8 days');
        $day_before = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('+2 days');
        $day_after = $modifymonth->format(DATE_FORMAT);

        $modifymonth->modify('-1 days');
        $last_day = $modifymonth->format(DATE_FORMAT);

        $day_int = intval($now_formatted->format('d'));
        $month_int = intval($now_formatted->format('m'));

        $current_month = FormatHelper::formatMonthNameInBahasa($month_int);

        $message = [
            BILL_STATUS_UNPAID => "Pembayaran SPP Bulan $current_month ",
            BILL_STATUS_PAID => "Pembayaran SPP Bulan $current_month ",
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

        switch ($notification_type) {
            case FIRST_DAY:
                $message[BILL_STATUS_UNPAID] .= "telah dibuka dan akan berakhir di tanggal *$last_day*. ";
                $msg_valid = true;
                break;
            case DAY_BEFORE:
                $message[BILL_STATUS_UNPAID] .= "akan berakhir besok, *$last_day*. ";
                $msg_valid = true;
                break;
            case DAY_AFTER:
                $message[BILL_STATUS_UNPAID] .= 'belum dibayarkan. ';
                $message[BILL_STATUS_PAID] .= 'telah dibayarkan. ';
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
                    SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) AS late_fee,
                    SUM(CASE WHEN b.trx_status IN ('$status[unpaid]','$status[active]') THEN b.trx_amount ELSE 0 END) AS monthly_fee
                  FROM
                    bills b JOIN
                    users u ON b.user_id = u.id JOIN
                    user_class c ON u.id = c.user_id JOIN
                    levels l ON c.level_id = l.id
                  WHERE
                    b.payment_due <= '$payment_due' AND
                    c.date_left IS NULL
                  GROUP BY
                    b.virtual_account, l.name, u.name, u.parent_phone
                  HAVING
                    SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) +
                    SUM(CASE WHEN b.trx_status IN ('$status[unpaid]','$status[active]') THEN b.trx_amount ELSE 0 END) > 0";

        $data = $this->db->fetchAll($this->db->query($query));

        $msg_data = [];

        foreach ($data as $student) {
            $monthly_fee = FormatHelper::formatRupiah($student['monthly_fee']);
            $late_fee = FormatHelper::formatRupiah($student['late_fee']);
            $trx_amount = FormatHelper::formatRupiah($student['monthly_fee'] + $student['late_fee']);
            $va = $student['va'];
            $va_name = $student['prefix'] . '_' . $student['name'];
            $user_msg = $message[BILL_STATUS_UNPAID] . "SPP: $monthly_fee\nDenda: $late_fee\n*Total Pembayaran: $trx_amount*\n\nVirtual Account: BNI *$va* atas nama *$va_name*";
            $msg_data[] = [
                'target' => $student['parent_phone'],
                'message' => $user_msg,
                'delay' => '1',
            ];
        }

        if ($notification_type == DAY_AFTER) {
            $query = "SELECT
                        u.name, u.parent_phone, p.trx_timestamp, p.details,
                        p.user_id, p.bill_id
                      FROM
                        payments p JOIN
                        bills b ON b.id = p.bill_id LEFT JOIN
                        users u ON u.id = b.user_id
                      WHERE
                        b.payment_due = '$payment_due'";

            $data = $this->db->fetchAll($this->db->query($query));
            $url = $_SERVER['HTTP_HOST'];

            foreach ($data as $student) {
                $cyper = $this->generateInvoiceURL($student['user_id'], $student['bill_id']);
                $name = $student['name'];
                $timestamp = Call::date($student['trx_timestamp']);
                $user_msg = $message[BILL_STATUS_PAID] . "Terima kasih kepada orang tua $name yang telah melakukan pembayaran pada tanggal *$timestamp*. Untuk bukti pembayaran bisa dilihat di http://$url/invoice/$cyper";
                $msg_data[] = [
                    'target' => $student['parent_phone'],
                    'message' => $user_msg,
                    'delay' => '1',
                ];
            }
        }
        if (empty($msg_data)) {
            return Response::error('Tidak ada pembayaran yang harus dibayar');
        }

        $messages = json_encode($msg_data);
        $fonnte = Fonnte::sendMessage(['data' => $messages]);

        return Response::success(
            [
                'fonnte_response' => $fonnte,
                'messages' => $msg_data,
            ],
            "Notifikasi pembayaran SPP bulan $current_month berhasil dikirim",
        );
    }

    protected function getFeeCategories()
    {
        $query = 'SELECT * FROM fee_categories';

        return $this->db->fetchAll($this->db->query($query));
    }

    protected function generateInvoiceURL($user, $bill)
    {
        $key = $_ENV['ENCRYPTION_KEY'];
        $method = $_ENV['ENCRYPTION_METHOD'];

        $string = "$user|-|$bill";

        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);
        $encrypted_with_iv = base64_encode($iv . $encrypted);

        return $encrypted_with_iv;
    }

    public function getPublicFeeCategories()
    {
        $query = 'SELECT * FROM fee_categories';

        return ApiResponse::success($this->db->fetchAll($this->db->query($query)), 'Success get Additional Fee Categories');
    }

    protected function getBillFormat(int $late = 0)
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'VA', 'NIS', 'Nama', 'Jenjang', 'Tingkat', 'Kelas', 'SPP'];

        $fee_categories = $this->getFeeCategories();

        foreach ($fee_categories as $fee) {
            $headers[] = $fee['name'];
        }

        $headers[] = 'Periode Sekarang';
        $headers[] = 'Jumlah Tunggakan (bulan)';
        for ($i = 0; $i <= $late; $i++) {
            $headers[] = $i + 1;
            $headers[] = 'Besar Tagihan ' . $i + 1;
        }
        $headers[] = 'Total Piutang';
        $headers[] = 'HER (DPP/UP)';
        $headers[] = 'Jumlah Total';

        $sheet->fromArray([$headers], null, 'A1');

        $highestColumn = $sheet->getHighestColumn();

        foreach (range('A', $highestColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray($headerStyle);

        return $spreadsheet;
    }

    public function exportBillXLSX()
    {
        $status = $this->status;

        $fee_categories = $this->getFeeCategories();

        $fee_category_select_list = [];
        $fee_category_subquery_list = [];
        $fee_category_aliases_list = [];

        if ($fee_categories) {
            foreach ($fee_categories as $category) {
                $categoryId = (int) $category['id'];
                $alias = 'Category' . $categoryId;

                $fee_category_select_list[] = "COALESCE(uaf_agg.`$alias`, 0) AS $alias";
                $fee_category_subquery_list[] = "SUM(CASE WHEN uaf_sub.fee_id = $categoryId THEN uaf_sub.amount ELSE 0 END) AS $alias";
                $fee_category_aliases_list[] = "$alias";
            }
        }

        $fee_category_select_query = !empty($fee_category_select_list) ? ', ' . implode(', ', $fee_category_select_list) : '';
        $fee_category_subquery_query = !empty($fee_category_subquery_list) ? ', ' . implode(', ', $fee_category_subquery_list) : '';
        $fee_category_group_by_query = !empty($fee_category_aliases_list) ? ', ' . implode(', ', $fee_category_aliases_list) : '';

        $query = "SELECT
                    c.virtual_account AS va,
                    u.nis AS nis,
                    u.name AS nama,
                    COALESCE(l.name, '-') AS level,
                    COALESCE(g.name, '-') AS grade,
                    COALESCE(s.name, '-') AS section,
                    c.monthly_fee,
                    MAX(CONCAT(YEAR(b.payment_due), '/', LPAD(MONTH(b.payment_due), 2, '0'))) AS payment_due,
                    COUNT(CASE WHEN b.trx_status = '$status[unpaid]' THEN 1 ELSE NULL END) AS late_count,
                    SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) AS late_fee,
                    SUM(CASE WHEN b.trx_status IN ('$status[unpaid]', '$status[active]') THEN b.trx_amount ELSE 0 END) + SUM(CASE WHEN b.trx_status = '$status[unpaid]' THEN b.late_fee ELSE 0 END) AS payable,
                    ub.unpaid_bill_details
                    $fee_category_select_query
                FROM
                    bills b JOIN
                    users u ON b.user_id = u.id JOIN
                    (
                        SELECT
                            uc_inner.user_id, MAX(uc_inner.section_id) AS max_section_id
                        FROM
                            user_class uc_inner
                        GROUP BY uc_inner.user_id
                    ) mc ON u.id = mc.user_id JOIN
                    user_class c ON u.id = c.user_id AND mc.max_section_id = c.section_id JOIN
                    levels l ON c.level_id = l.id JOIN
                    grades g ON c.grade_id = g.id JOIN
                    sections s ON c.section_id = s.id LEFT JOIN
                    (
                        SELECT
                            uaf_sub.user_id
                            $fee_category_subquery_query
                        FROM
                            user_additional_fee uaf_sub
                        GROUP BY uaf_sub.user_id
                    ) uaf_agg ON u.id = uaf_agg.user_id LEFT JOIN
                    (
                        SELECT
                            b2.user_id,
                            JSON_ARRAYAGG(
                                JSON_OBJECT(
                                    'trx_amount', b2.trx_amount,
                                    'trx_detail', IFNULL(b2.trx_detail, JSON_OBJECT()),
                                    'late_fee', b2.late_fee,
                                    'payment_due', DATE_FORMAT(b2.payment_due, '%Y-%m-%d')
                                )
                            ) AS unpaid_bill_details
                        FROM bills b2
                        WHERE b2.trx_status IN ('$status[unpaid]', '$status[active]')
                        GROUP BY b2.user_id
                    ) ub ON ub.user_id = u.id
                WHERE
                    b.trx_status IN ('$status[unpaid]', '$status[active]')
                    AND b.user_id = u.id
                GROUP BY
                    c.virtual_account, u.nis, u.name,
                    l.name,
                    g.name,
                    s.name,
                    c.monthly_fee,
                    l.id,
                    g.id,
                    s.id,
                    ub.unpaid_bill_details
                    $fee_category_group_by_query
                ORDER BY
                    s.id, c.virtual_account";

        $result = $this->db->fetchAll($this->db->query($query));
        $startRow = 2;
        $max_late = 0;
        $data = [];

        foreach ($result as $index => $row) {
            $max_late = $max_late = max($max_late, $row['late_count']);
            $fee_json = isset($row['unpaid_bill_details']) ? json_decode($row['unpaid_bill_details'] ?? '[]', true) : [];
            $additional_fee = [];
            $unpaid_fee = [];
            foreach ($fee_json as $idx => $fee_data) {
                $details = $fee_data['trx_detail']['items'];
                foreach ($details as $detail) {
                    if ($detail['item_name'] == 'monthly_fee' || $detail['item_name'] == 'late_fee') {
                        break;
                    }
                    $additional_fee[$detail['item_name']] = $additional_fee['amount'];
                }
                $unpaid_fee[$idx][] = [
                    'periode' => substr($fee_data['payment_due'], 0, 7),
                    'amount' => $fee_data['late_fee'] + $fee_data['trx_amount'],
                ];
            }

            $data[$index] = [$index + 1, $row['va'], $row['nis'], $row['nama'], $row['level'], $row['grade'], $row['section'], FormatHelper::formatRupiah($row['monthly_fee'])];

            foreach ($fee_categories as $category) {
                $data[$index][] = FormatHelper::formatRupiah(isset($additional_fee[$category['name']]) ? $additional_fee[$category['name']] : 0);
            }

            $data[$index][] = $row['payment_due'];
            $data[$index][] = $row['late_count'] + 1;

            foreach ($unpaid_fee as $i => $uf) {
                $data[$index][] = $uf[0]['periode'];
                $data[$index][] = FormatHelper::formatRupiah($uf[0]['amount']);
            }
            $data[$index][] = FormatHelper::formatRupiah($row['payable']);
            $data[$index][] = '-';
            $data[$index][] = FormatHelper::formatRupiah($row['payable']);
        }

        $spreadsheet = $this->getBillFormat($max_late);

        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        foreach ($data as $index => $d) {
            $sheet->fromArray($d, null, 'A' . ($startRow + $index));
        }
        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export_bills.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function updateLateFee()
    {
        $billId = $_POST['bill_id'];
        $lateFee = $_POST['late_fee'];

        try {
            $this->db->beginTransaction();

            $getBill = 'SELECT * FROM bills WHERE id = ?';
            $bill = $this->db->fetchAssoc($this->db->query($getBill, [$billId]));

            $billDetail = json_decode($bill['trx_detail'], true);
            $oldLateFee = FormatHelper::formatRupiah($bill['late_fee']);
            $newLateFee = FormatHelper::formatRupiah($lateFee);

            $billDetail['total'] = 0;
            foreach ($billDetail['items'] as $id => $item) {
                if ($item['item_name'] == LATE_FEE) {
                    $billDetail['items'][$id]['amount'] = (float) $lateFee;
                    $item['amount'] = (float) $lateFee;
                }
                $billDetail['total'] += $item['amount'];
            }

            $billDetailJSON = json_encode($billDetail);
            $bill['trx_detail'] = $billDetailJSON;
            $bill['late_fee'] = (float) $lateFee;

            $updateBill = $this->db->update('bills', $bill, ['id' => $billId]);
            if (!$updateBill) {
                throw new Exception('Failed to update bills');
            }

            $userDetailQuery = "SELECT
                                    u.name, u.parent_phone, MONTH(b.payment_due) AS month, YEAR(b.payment_due) AS year
                                FROM
                                    bills b JOIN
                                    users u ON b.user_id = u.id
                                WHERE
                                    b.id = ?";

            $userDetail = $this->db->fetchAssoc($this->db->query($userDetailQuery, [$billId]));
            $name = $userDetail['name'];
            $contact = $userDetail['parent_phone'];
            $month = FormatHelper::formatMonthNameInBahasa($userDetail['month']);
            $year = $userDetail['year'];

            $msg = [
                [
                    'target' => $contact,
                    'message' => "Biaya keterlambatan SPP *$name* pada bulan *$month $year* telah diubah dari *$oldLateFee* menjadi *$newLateFee*",
                    'delay' => '1',
                ],
            ];

            Fonnte::sendMessage(['data' => json_encode($msg)]);

            $this->db->commit();
            $_SESSION['msg'] = 'Biaya keterlambatan berhasil diperbarui.';
        } catch (\Exception $e) {
            $_SESSION['msg'] = 'Terjadi kesalahan saat mengubah biaya keterlambatan: ' . $e->getMessage();
        } finally {
            header('Location: /tagihan');
        }
    }

    public function manualCreateBills()
    {
        try {
            $this->db->beginTransaction();
            $status = $this->status;

            $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCHECK-%' ORDER BY created_at DESC LIMIT 1";
            $logName = $this->db->fetchAssoc($this->db->query($log));
            [$title, $semester, $year, $month] = explode('-', $logName['log_name']);

            if (!in_array((int)$month, [6, 12])){
                $this->db->rollback();
                return Response::error('Tagihan periode sebelumnya belum diselesaikan, harap lakukan CHECK-BILLS terlebih dahulu', 400);
            }

            $log = "SELECT log_name FROM logs WHERE log_name LIKE 'BCREATE-%' ORDER BY created_at DESC LIMIT 1";
            $logName = $this->db->fetchAssoc($this->db->query($log));

            [$title, $semester, $year] = explode('-', $logName['log_name']);

            $nextPeriod = [
                'year' => (int) $year,
                'semester' => $semester,
            ];

            $periodDate = '';

            if ($semester == SECOND_SEMESTER) {
                $nextPeriod['semester'] = FIRST_SEMESTER;
                $periodDate = "01-07-$year";
            } else {
                $nextPeriod['year'] = $nextPeriod['year'] + 1;
                $nextPeriod['semester'] = SECOND_SEMESTER;
                $periodDate = '01-01-' . $nextPeriod['year'];
            }

            $logs = FormatHelper::formatSystemLog(LOG_CREATE_BILLS, $nextPeriod);
            $logs['description'] .= ' (FORCE)';
            $log_check = 'SELECT * FROM logs WHERE `log_name`= ?';
            $log_check_result = $this->db->query($log_check, [$logs['log_name']]);

            if ($log_check_result && $this->db->fetchAssoc($log_check_result)) {
                $this->db->rollback();
                return Response::error('Bills have been created before', 404);
            }

            $months = Call::monthSemester($periodDate);
            $date = Call::splitDate($periodDate);
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
                        COALESCE(s.name, '')
                    ) AS class_name
                FROM
                    users u
                    LEFT JOIN user_class c ON u.id = c.user_id
                    LEFT JOIN levels l ON c.level_id = l.id
                    LEFT JOIN grades g ON c.grade_id = g.id
                    LEFT JOIN sections s ON c.section_id = s.id
                WHERE u.role = ? AND c.date_left IS NULL
            ";
            $student_result = $this->db->query($students_query, [$role]);
            $students = $this->db->fetchAll($student_result);

            $bills = [];
            $academicYear = Call::academicYear(ACADEMIC_YEAR_EIGHT_SLASH_FORMAT, [
                'semester' => $nextPeriod['semester'],
                'date' => $date,
            ]);

            foreach ($students as $student) {
                foreach ($months as $month) {
                    $due_date = date(TIMESTAMP_FORMAT, strtotime("$month/10/{$date['year']} 23:59:59"));

                    $details = [
                        'name' => $student['name'],
                        'class' => $student['class_name'],
                        'virtual_account' => $student['virtual_account'],
                        'academic_year' => $academicYear,
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
            $this->sendToAKTSystem($months[0], $date['year']);
            return Response::success($bill_result, 'Bills created successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to create bills: ' . $e->getMessage(), 500);
        }
    }
    
    public function manualCheckBills()
    {
        try {
            $this->db->beginTransaction();

            $status = $this->status;

            // ! INISIASI KALAU ENGGA ADA BILLS SAMA SEKALI SEBELUMNYA
            $check_bills = ["SELECT MAX(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[paid]')", "SELECT MIN(payment_due) AS payment_due FROM bills WHERE trx_status IN ('$status[active]')"];
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
            $logs['description'] .= ' (FORCE)';

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
                        THEN '$status[active]'
                        WHEN b.trx_status IN ('$status[active]', '$status[paid]')
                            AND next_b.trx_status = '$status[inactive]'
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
                        THEN JSON_SET(next_b.trx_detail, '$.status', '$status[active]')

                        WHEN b.trx_status IN ('$status[active]', '$status[paid]')
                            AND next_b.trx_status = '$status[inactive]'
                        THEN JSON_SET(next_b.trx_detail, '$.status', '$status[active]')
                        ELSE next_b.trx_detail
                    END
                WHERE b.trx_status IN ('$status[active]', '$status[paid]', '$status[unpaid]')";

            $this->db->query($updateBills);
            $this->db->query('DROP TEMPORARY TABLE IF EXISTS temp_bills');

            $this->db->insert('logs', $logs);

            $this->db->commit();

            $this->sendToAKTSystem($date['month'] + 1, $date['year']);

            return Response::success(true, 'Bills checked successfully');
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to check bills: ' . $e->getMessage(), 500);
        }
    }
}
