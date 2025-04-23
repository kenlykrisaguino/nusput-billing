<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\FormatHelper;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentBE
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

    public function getStudents($params = [])
    {
        $query = "SELECT
                    u.id, u.nis, u.name, l.name AS level, g.name AS grade, s.name AS section,
                    u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee,
                    MAX(p.trx_timestamp) AS latest_payment
                  FROM
                    users u
                    LEFT JOIN user_class c ON u.id = c.user_id
                    INNER JOIN levels l ON c.level_id = l.id
                    INNER JOIN grades g ON c.grade_id = g.id
                    LEFT JOIN sections s ON c.section_id = s.id
                    LEFT JOIN payments p ON u.id = p.user_id
                  WHERE
                    u.role = 'ST' AND
                    c.date_left IS NULL AND
                    u.deleted_at IS NULL
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
                u.id, u.nis, u.name, l.name, g.name, s.name,
                u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee";

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }

    protected function getStudentFormat()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NIS', 'Nama', 'Jenjang', 'Tingkat', 'Kelas', 'Alamat', 'Tanggal Lahir', 'Nomor Telepon', 'Alamat Email', 'Nomor Orang Tua'];

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

    public function getStudentFormatXLSX()
    {
        $spreadsheet = $this->getStudentFormat();
        $writer = new Xlsx($spreadsheet);

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="import_student_format.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function exportStudentXLSX()
    {
        $spreadsheet = $this->getStudentFormat();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        $query = "SELECT
                    u.nis, u.name, l.name AS level, g.name AS grade, s.name AS section,
                    u.address, u.dob, u.phone, u.email, u.parent_phone
                  FROM
                    users u
                    LEFT JOIN user_class c ON u.id = c.user_id
                    LEFT JOIN levels l ON c.level_id = l.id
                    LEFT JOIN grades g ON c.grade_id = g.id
                    LEFT JOIN sections s ON c.section_id = s.id
                  WHERE
                    u.role = 'ST' AND c.date_left IS NULL";

        $students = $this->db->fetchAll($this->db->query($query));

        $startRow = 2;
        foreach ($students as $index => $student) {
            $cleaned = array_map(fn($v) => $v ?? '', array_values($student));
            $sheet->fromArray($cleaned, null, 'A' . ($startRow + $index));
        }

        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export_student.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function deleteStudent($id)
    {
        $now = new Datetime();
        $query = "UPDATE users SET deleted_at='$now' WHERE id=$id";
        return $this->db->query($query);
    }

    public function updateStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API method', 405);
        }

        $rawData = file_get_contents('php://input');

        $data = json_decode($rawData, true);

        $userId = $data['user_id'];
        $nis = trim($data['edit-nis'] ?? '');
        $name = trim($data['edit-name'] ?? '');
        $dob = trim($data['edit-dob'] ?? '');
        $phone = trim($data['edit-phone-number'] ?? '');
        $email = trim($data['edit-email-address'] ?? '');
        $parentPhone = trim($data['edit-parent-phone'] ?? '');
        $address = trim($data['edit-address'] ?? '');
        $levelId = trim($data['edit-level'] ?? '');
        $gradeId = trim($data['edit-grade'] ?? '');
        $sectionIdInput = $data['edit-section'] ?? '';
        $monthlyFee = trim($data['edit-monthly-fee'] ?? '');

        $year = trim($data['edit-academic-year'] ?? '');
        $semester = trim($data['edit-semester'] ?? '');
        $month = trim($data['edit-month'] ?? '');

        $existingFees = $data['edit_additional_fee'] ?? [];
        $newFees = $data['new_fee'] ?? [];

        $errors = [];
        if (!$userId) {
            $errors[] = 'Invalid User ID.';
        }
        if (empty($nis)) {
            $errors[] = 'NIS is required.';
        }
        if (empty($name)) {
            $errors[] = 'Nama is required.';
        }
        if (empty($parentPhone)) {
            $errors[] = 'Telepon Orang Tua is required.';
        }
        if (!$levelId) {
            $errors[] = 'Jenjang is required.';
        }
        if (!$gradeId) {
            $errors[] = 'Tingkat is required.';
        }
        if ($monthlyFee === false || $monthlyFee < 0) {
            $errors[] = 'SPP Bulanan is invalid or missing.';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format Alamat Email invalid.';
        }
        if (!empty($dob)) {
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                $errors[] = 'Format Tanggal Lahir invalid (YYYY-MM-DD).';
            }
        } else {
            $dob = null;
        }

        $sectionId = $sectionIdInput === '' || !filter_var($sectionIdInput, FILTER_VALIDATE_INT) ? null : (int) $sectionIdInput;

        $periodDate = null;
        $processFees = false;
        if ($year && $semester && $month) {
            $processFees = true;
            try {
                $periodDate = Call::getFirstDay(['year' => $year, 'semester' => $semester, 'month' => $month]);
                if (!$periodDate) {
                    throw new Exception('Could not calculate period date.');
                }

                foreach ($existingFees as $feeId => $amount) {
                    if (!is_numeric($feeId) || !is_numeric($amount) || $amount < 0) {
                        $errors[] = 'Invalid amount for existing fee.';
                        break;
                    }
                }
                foreach ($newFees as $uniqueId => $catId) {
                    if (!$catId['amount'] || !is_numeric($catId['amount']) || $catId['amount'] < 0) {
                        $errors[] = 'Invalid amount for new fee.';
                        break;
                    }
                    if (!filter_var($catId['category'], FILTER_VALIDATE_INT)) {
                        $errors[] = 'Invalid category for new fee.';
                        break;
                    }
                }
            } catch (Exception $e) {
                $processFees = false;
                $errors[] = 'Invalid period: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return ApiResponse::error('Invalid input data.', 400, $errors);
        }

        $this->db->beginTransaction();
        try {
            $currentClassStmt = $this->db->query('SELECT id, level_id, grade_id, section_id FROM user_class WHERE user_id = ? AND date_left IS NULL LIMIT 1', [$userId]);
            $currentClass = $currentClassStmt ? $this->db->fetchAssoc($currentClassStmt) : null;

            $userData = [
                'nis' => $nis,
                'name' => $name,
                'dob' => $dob,
                'phone' => empty($phone) ? null : $phone,
                'email' => empty($email) ? null : $email,
                'parent_phone' => $parentPhone,
                'address' => empty($address) ? null : $address,
                'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ];
            $userUpdateResult = $this->db->update('users', $userData, ['id' => $userId]);
            if ($userUpdateResult === false) {
                throw new Exception('Failed to update user information.');
            }

            $classChanged = true;
            $fee_total = $monthlyFee;
            if ($currentClass) {
                if ($currentClass['level_id'] == $levelId && $currentClass['grade_id'] == $gradeId && $currentClass['section_id'] == $sectionId) {
                    $classChanged = false;
                }
            }

            if ($classChanged) {
                $now = (new DateTime())->format('Y-m-d H:i:s');
                if ($currentClass) {
                    $endClassResult = $this->db->update('user_class', ['date_left' => $now], ['id' => $currentClass['id']]);
                    if ($endClassResult === false) {
                        throw new Exception('Failed to end previous class enrollment.');
                    }
                }

                $classList = $this->getClassList();
                $sectionIdKey = $sectionId ?? 'none';
                $lookup = $classList[$levelId][$gradeId][$sectionIdKey] ?? null;
                if (!$lookup) {
                    throw new Exception('Could not find details for the selected new class.');
                }

                $newMonthlyFee = $monthlyFee;
                $newLateFee = $lookup['late_fee'] ?? 0;
                $va = FormatHelper::formatVA($lookup['va_prefix'], $nis);
                $password = md5($va);

                $newClassData = [
                    'user_id' => $userId,
                    'level_id' => $levelId,
                    'grade_id' => $gradeId,
                    'section_id' => $sectionId,
                    'monthly_fee' => $newMonthlyFee,
                    'late_fee' => $newLateFee,
                    'virtual_account' => $va,
                    'password' => $password,
                    'date_joined' => $now,
                    'date_left' => null,
                ];
                $insertResult = $this->db->insert('user_class', $newClassData);
                if (!$insertResult) {
                    throw new Exception('Failed to insert new class enrollment.');
                }
            } else {
                if ($currentClass) {
                    $updateFeeResult = $this->db->update('user_class', ['monthly_fee' => $monthlyFee], ['id' => $currentClass['id']]);
                    if ($updateFeeResult === false) {
                        throw new Exception('Failed to update monthly fee for existing class.');
                    }
                } else {
                    throw new Exception('Internal error: Class not changed but no current class found.');
                }
            }

            $additionalFeeChanged = false;

            if ($processFees && $periodDate) {
                $existingFeesStmt = $this->db->query('SELECT id, fee_id, amount FROM user_additional_fee WHERE user_id = ? AND period = ?', [$userId, $periodDate]);
                if (!$existingFeesStmt) {
                    throw new Exception('Failed to query existing fees.');
                }
                $currentFeesMap = [];
                while ($row = $this->db->fetchAssoc($existingFeesStmt)) {
                    $currentFeesMap[$row['fee_id']] = ['id' => $row['id'], 'amount' => $row['amount']];
                }
                $processedFeeIds = [];

                foreach ($existingFees as $feeId => $newAmount) {
                    $feeId = (int) $feeId;
                    $newAmount = (float) $newAmount;
                    $processedFeeIds[] = $feeId;
                    if (isset($currentFeesMap[$feeId])) {
                        if ($currentFeesMap[$feeId]['amount'] != $newAmount) {
                            $fee_total += $newAmount;
                            $additionalFeeChanged = true;
                            $updateFeeResult = $this->db->update('user_additional_fee', ['amount' => $newAmount], ['id' => $currentFeesMap[$feeId]['id']]);
                            if ($updateFeeResult === false) {
                                throw new Exception("Failed to update additional fee (ID: $feeId).");
                            }
                        } else {
                            $fee_total += $currentFeesMap[$feeId]['amount'];
                        }
                    } else {
                        $additionalFeeChanged = true;
                        $fee_total += $newAmount;
                        $feeData = ['user_id' => $userId, 'fee_id' => $feeId, 'amount' => $newAmount, 'period' => $periodDate];
                        $insertResult = $this->db->insert('user_additional_fee', $feeData);
                        if (!$insertResult) {
                            throw new Exception("Failed to insert submitted additional fee (ID: $feeId).");
                        }
                    }
                }

                foreach ($newFees as $catId) {
                    $category = (int) $catId['category'];
                    if (isset($catId['amount']) && is_numeric($catId['amount']) && $catId['amount'] >= 0) {
                        $amount = (float) $catId['amount'];
                        if (!isset($currentFeesMap[$category]) || !in_array($category, $processedFeeIds)) {
                            $additionalFeeChanged = true;
                            $processedFeeIds[] = $category;
                            $fee_total += (int) $amount;
                            $newFeeData = ['user_id' => $userId, 'fee_id' => $category, 'amount' => $amount, 'period' => $periodDate];
                            $insertResult = $this->db->insert('user_additional_fee', $newFeeData);
                            if (!$insertResult) {
                                throw new Exception("Failed to insert new additional fee (Category ID: $category).");
                            }
                        }
                    }
                }

                foreach ($currentFeesMap as $feeId => $feeDetails) {
                    if (!in_array($feeId, $processedFeeIds)) {
                        $additionalFeeChanged = true;
                        $deleteResult = $this->db->delete('user_additional_fee', ['id' => $feeDetails['id']]);
                        if ($deleteResult === false) {
                            throw new Exception("Failed to delete additional fee (DB ID: {$feeDetails['id']}).");
                        }
                    }
                }

                if ($additionalFeeChanged) {
                    $status = $this->status;
                    $splitDate = Call::splitDate($periodDate);
                    $billStmt = $this->db->query("SELECT
                                                    id, trx_detail
                                                FROM bills
                                                WHERE
                                                    user_id = ? AND
                                                    MONTH(payment_due) = ? AND
                                                    YEAR(payment_due) = ? AND
                                                    trx_status IN (?, ?)",
                        [$userId, $splitDate['month'], $splitDate['year'], $status['active'], $status['inactive']],
                    );
                    if (!$billStmt) {
                        throw new Exception('Failed to query existing fees.');
                    }
                    $billData = $this->db->fetchAssoc($billStmt);
                    if (!$billData) {
                        throw new Exception('No matching bill found to update for user ' . $userId . ' period ' . $periodDate);
                    }

                    $detail = json_decode($billData['trx_detail'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Failed to decode existing trx_detail JSON for bill ID ' . $billData['id'] . '. Error: ' . json_last_error_msg());
                    }

                    $items = [
                        [
                            'amount' => (float) $monthlyFee,
                            'item_name' => 'monthly_fee',
                        ],
                        [
                            'amount' => (float) $detail['items'][1]['amount'],
                            'item_name' => $detail['items'][1]['item_name'],
                        ],
                    ];

                    if (!isset($detail['items']) || !is_array($detail['items']) || !isset($detail['items'][1])) {
                        throw new Exception('Invalid structure in existing trx_detail items for bill ID ' . $billData['id']);
                    }

                    $items = [
                        [
                            'amount' => (float) $monthlyFee,
                            'item_name' => 'monthly_fee',
                        ],
                        [
                            'amount' => (float) $detail['items'][1]['amount'],
                            'item_name' => $detail['items'][1]['item_name'],
                        ],
                    ];

                    $finalAdditionalFeesStmt = $this->db->query("SELECT
                                                                    uaf.fee_id, f.name AS fee_name, uaf.amount
                                                                FROM
                                                                    user_additional_fee uaf LEFT JOIN
                                                                    fee_categories f ON uaf.fee_id = f.id
                                                                WHERE uaf.user_id = ? AND uaf.period = ?",
                        [$userId, $periodDate],
                    );
                    if (!$finalAdditionalFeesStmt) {
                        throw new Exception('Failed to re-query final additional fees.');
                    }

                    foreach ($this->db->fetchAll($finalAdditionalFeesStmt) as $f) {
                        $items[] = [
                            'amount' => (float) $f['amount'],
                            'item_name' => $f['fee_name'],
                        ];
                    }
                    $detail['items'] = $items;
                    $detail['total'] = $fee_total;
                    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
                    $detailsJSON = json_encode($detail, $jsonFlags);

                    if ($detailsJSON === false) {
                        throw new Exception('Failed to encode bill items to JSON. Error: ' . json_last_error_msg());
                    }
                    $query = "UPDATE bills SET trx_detail = '$detailsJSON', trx_amount = '$fee_total' WHERE trx_status IN ('$status[active]', '$status[inactive]') AND MONTH(payment_due) = '$splitDate[month]' AND YEAR(payment_due) = '$splitDate[year]' AND user_id = $userId";

                    $this->db->query($query);
                }
            }
            if ($classChanged) {
                $updateBillTotal = $this->db->update('bills', ['trx_amount' => $fee_total], ['user_id' => $userId, 'trx_status' => [$status['active'], $status['inactive']]]);
                if ($updateBillTotal === false) {
                    throw new Exception('Failed to update bill totals');
                }
            }
            $this->db->commit();
            return ApiResponse::success('Student data updated successfully.');
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating student ID $userId: " . $e->getMessage());
            return ApiResponse::error('Failed to update student data. ' . $e->getMessage(), 500);
        }
    }

    protected function studentDashboard($user_id)
    {
        $status = $this->status;

        $query = "SELECT
                    u.nis, u.name, u.parent_phone,
                    c.virtual_account, c.date_joined, c.monthly_fee,
                    CONCAT(
                        COALESCE(l.name, ''),
                        ' ',
                        COALESCE(g.name, ''),
                        ' ',
                        COALESCE(S.name, '')
                    ) AS class_name,
                    (
                        SELECT
                            DATE_FORMAT(MAX(p.trx_timestamp), '%d %M %Y')
                        FROM
                            payments p
                        WHERE
                            p.user_id = u.id
                    ) AS last_payment,
                    SUM(
                        CASE
                            WHEN b.trx_status = '$status[unpaid]'
                            THEN b.trx_amount + b.late_fee
                            WHEN b.trx_status = '$status[active]'
                            THEN b.trx_amount
                            ELSE 0
                        END
                    ) AS total_bills
                  FROM
                    users u JOIN
                    user_class c ON c.user_id = u.id JOIN
                    levels l ON c.level_id = l.id JOIN
                    grades g ON c.grade_id = g.id JOIN
                    sections s ON c.level_id = s.id JOIN
                    bills b on u.id = b.user_id
                  WHERE
                    b.user_id = '$user_id' AND
                    b.trx_status != '$status[disabled]' AND
                    c.date_left IS NULL
                  GROUP BY
                    u.nis, u.name, u.parent_phone,
                    c.virtual_account, c.date_joined, c.monthly_fee,
                    l.name, g.name, S.name
                  ";
        $result = $this->db->fetchAll($this->db->query($query));

        return $result;
    }

    protected function studentPayment($user_id)
    {
        $status = $this->status;

        $query = "SELECT
                    u.nis, u.name, u.virtual_account,
                    CONCAT(
                        COALESCE(l.name, ''),
                        ' ',
                        COALESCE(g.name, ''),
                        ' ',
                        COALESCE(S.name, '')
                    ) AS class_name,
                  FROM
                    users u JOIN
                    user_class c ON c.user_id = u.id JOIN
                    levels l ON c.level_id = l.id JOIN
                    grades g ON c.grade_id = g.id JOIN
                    sections s ON c.level_id = s.id
                  WHERE
                    u.id = '$user_id'
                  ";
        $result = $this->db->fetchAll($this->db->query($query));

        return $result;
    }

    public function studentPage()
    {
        $user_id = $_SESSION['user_id'];

        if ($user_id == '' || !isset($user_id)) {
            return [];
        }

        return [
            'dashboard' => $this->studentDashboard($user_id),
            'payments' => $this->studentPayment($user_id),
        ];
    }

    public function getStudentDetail()
    {
        $id = $_GET['user_id'] ?? '';
        if ($id == '') {
            return ApiResponse::error('No User ID Found');
        }
        $query = "SELECT
                    u.nis, u.name, COALESCE(u.dob, '') AS dob,
                    COALESCE(u.phone, '') AS phone, COALESCE(u.email, '') AS email, u.parent_phone,
                    COALESCE(u.address, '') AS address, COALESCE(l.name, '') AS level, COALESCE(l.id, '') AS level_id,
                    COALESCE(g.name, '') AS grade, COALESCE(g.id, '') AS grade_id, COALESCE(s.name, '') AS section,
                    COALESCE(s.id, '') AS section_id, c.monthly_fee
                  FROM
                    users u JOIN
                    user_class c ON u.id = c.user_id JOIN
                    levels l ON l.id = c.level_id JOIN
                    grades g ON g.id = c.grade_id JOIN
                    sections s ON s.id = c.section_id
                  WHERE
                    c.date_left IS NULL AND
                    u.id = $id";

        $result = $this->db->fetchAssoc($this->db->query($query));

        return ApiResponse::success($result, 'Success Get Student');
    }

    public function getStudentFees()
    {
        $id = $_GET['user_id'] ?? null;
        $year = $_GET['year'] ?? null;
        $semester = $_GET['semester'] ?? null;
        $month = $_GET['month'] ?? null;

        if (!$id || !$year || !$semester || !$month) {
            return ApiResponse::error('Error Missing Parameters', 400, [
                'id' => $id,
                'year' => $year,
                'semester' => $semester,
                'month' => $month,
            ]);
        }

        $period = Call::getFirstDay([
            'year' => $year,
            'semester' => $semester,
            'month' => $month,
        ]);

        $query = "SELECT
                    f.id, f.name, uaf.amount
                  FROM
                    user_additional_fee uaf JOIN
                    fee_categories f ON uaf.fee_id = f.id
                  WHERE
                    uaf.period = '$period' AND
                    uaf.user_id = $id";

        $result = $this->db->fetchAll($this->db->query($query));

        return ApiResponse::success($result, 'Success Get Student Additional Fees');
    }

    protected function getClassList()
    {
        $query = "SELECT
                    l.id AS level_id, l.name AS level_name, l.va_code AS va_prefix,
                    g.id AS grade_id, g.level_id AS grade_level_id, g.name AS grade_name, g.base_monthly_fee AS grade_monthly, g.base_late_fee AS grade_late,
                    s.id AS section_id, s.grade_id AS section_level_id, s.name AS section_name, s.base_monthly_fee AS section_monthly, s.base_late_fee AS section_late
                  FROM
                    levels l JOIN
                    grades g ON l.id = g.level_id LEFT JOIN
                    sections s ON g.id = s.grade_id";
        $result = $this->db->fetchAll($this->db->query($query));

        $class_list = [];
        foreach ($result as $data) {
            $class_list[$data['level_id']][$data['grade_id']][$data['section_id']] = [
                'va_prefix' => $data['va_prefix'],
                'level_name' => $data['level_name'],
                'grade_name' => $data['grade_name'],
                'section_name' => $data['section_name'],
                'monthly_fee' => $data['grade_monthly'] != 0 ? $data['grade_monthly'] : $data['section_monthly'],
                'late_fee' => $data['grade_late'] != 0 ? $data['grade_late'] : $data['section_monthly'],
            ];
        }

        return $class_list;
    }

    protected function createStudents($data, $details)
    {
        $class_list = self::getClassList();

        $students = !isset($data[0]) ? [$data] : $data;
        $classes = !isset($details[0]) ? [$details] : $details;

        $ids = $this->db->insert('users', $students);

        $class_details = [];
        foreach ($ids as $id) {
            foreach ($classes as $class) {
                if ($class['nis'] == $id['nis']) {
                    $l_id = $class['level_id'];
                    $g_id = $class['grade_id'];
                    $s_id = $class['section_id'];

                    $va = FormatHelper::formatVA($class_list[$l_id][$g_id][$s_id ?? '']['va_prefix'], $id['nis']);

                    $class_details[] = [
                        'user_id' => $id['id'],
                        'level_id' => $l_id,
                        'grade_id' => $g_id,
                        'section_id' => $s_id == '' ? null : $s_id,
                        'monthly_fee' => $class_list[$l_id][$g_id][$s_id ?? '']['monthly_fee'],
                        'late_fee' => $class_list[$l_id][$g_id][$s_id ?? '']['late_fee'],
                        'virtual_account' => $va,
                        'password' => md5($va),
                    ];
                }
            }
        }

        $ids = $this->db->insert('user_class', $class_details);
    }

    public function formCreateStudent()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 404);
        }

        $rawData = file_get_contents('php://input');

        $data = json_decode($rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Invalid JSON format', 400, json_last_error());
        }

        $nis = $data['nis'] ?? null;
        $name = $data['name'] ?? null;
        $dob = isset($data['dob']) && $data['dob'] !== '' ? $data['dob'] : null;
        $phone = isset($data['phone']) && $data['phone'] !== '' ? $data['phone'] : null;
        $email = isset($data['email']) && $data['email'] !== '' ? $data['email'] : null;
        $parent_phone = $data['parent_phone'] ?? null;
        $address = isset($data['address']) && $data['address'] !== '' ? $data['address'] : null;
        $level = $data['level'] ?? null;
        $grade = $data['grade'] ?? null;
        $section = isset($data['section']) && $data['section'] !== '' ? $data['section'] : null;

        $required_fields = ['nis', 'name', 'parent_phone', 'level', 'grade'];
        $errors = [];

        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "$field is required.";
            }
        }

        if (!empty($errors)) {
            return ApiResponse::error('Incomplete Form input', 400, $errors);
        }

        self::createStudents(
            [
                'nis' => $nis,
                'name' => $name,
                'dob' => $dob,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'parent_phone' => $parent_phone,
                'role' => USER_ROLE_STUDENT,
            ],
            [
                'nis' => $nis,
                'level_id' => $level,
                'grade_id' => $grade,
                'section_id' => $section,
            ],
        );

        return ApiResponse::success('Created Student Successful!');
    }

    public function importStudentsFromXLSX()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 405);
        }

        if (!isset($_FILES['bulk-students']) || $_FILES['bulk-students']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('File upload error occurred.', 400);
        }

        $filePath = $_FILES['bulk-students']['tmp_name'];
        $allowedMimes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $fileMime = mime_content_type($filePath);
        if (!in_array($fileMime, $allowedMimes)) {
            return ApiResponse::error('Invalid file type. Please upload an XLSX file.', 400);
        }

        $validStudentData = [];
        $validClassDetails = [];
        $errorRowsData = [];
        $processedRowCount = 0;
        $importedRowCount = 0;

        try {
            $classList = $this->getClassList();
            if (empty($classList)) {
                throw new Exception('Failed to load class list for validation.');
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $originalHeaders = $sheet->rangeToArray('A1:J1', null, true, false, true)[1] ?? [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $processedRowCount++;
                $originalRowValues = $sheet->rangeToArray('A' . $row . ':J' . $row, null, true, false, false)[0] ?? [];
                if (empty(array_filter($originalRowValues))) {
                    continue;
                }

                $rowData = [];
                $colIndex = 0;
                foreach ($originalHeaders as $colLetter => $headerName) {
                    $rowData[$colLetter] = $originalRowValues[$colIndex] ?? null;
                    $colIndex++;
                }

                $rowErrors = [];
                $nis = trim($rowData['A'] ?? '');
                $name = trim($rowData['B'] ?? '');
                $levelName = trim($rowData['C'] ?? '');
                $gradeName = trim($rowData['D'] ?? '');
                $sectionName = trim($rowData['E'] ?? '');
                $address = trim($rowData['F'] ?? '');
                $dobRaw = $rowData['G'] ?? null;
                $phone = trim($rowData['H'] ?? '');
                $email = trim($rowData['I'] ?? '');
                $parentPhone = trim($rowData['J'] ?? '');

                if ($nis === '') {
                    $rowErrors[] = 'NIS is required.';
                }
                if ($name === '') {
                    $rowErrors[] = 'Nama is required.';
                }
                if ($levelName === '') {
                    $rowErrors[] = 'Jenjang is required.';
                }
                if ($gradeName === '') {
                    $rowErrors[] = 'Tingkat is required.';
                }
                if ($parentPhone === '') {
                    $rowErrors[] = 'Nomor Orang Tua is required.';
                }

                $levelId = null;
                $gradeId = null;
                $sectionId = null;
                $foundClass = false;
                if ($levelName !== '' && $gradeName !== '') {
                    foreach ($classList as $l_id => $grades) {
                        foreach ($grades as $g_id => $sections) {
                            $currentLevelName = $sections[array_key_first($sections)]['level_name'] ?? '';
                            $currentGradeName = $sections[array_key_first($sections)]['grade_name'] ?? '';

                            if (strcasecmp($currentLevelName, $levelName) === 0 && strcasecmp($currentGradeName, $gradeName) === 0) {
                                $foundSectionMatch = false;
                                $trimmedSectionName = trim($sectionName ?? '');

                                foreach ($sections as $s_id_key => $details) {
                                    $currentSectionName = $details['section_name'];
                                    if ($trimmedSectionName === '' && $currentSectionName === null) {
                                        $levelId = $l_id;
                                        $gradeId = $g_id;
                                        $sectionId = null;
                                        $foundSectionMatch = true;
                                        break;
                                    } elseif ($trimmedSectionName !== '' && $currentSectionName !== null && strcasecmp($currentSectionName, $trimmedSectionName) === 0) {
                                        $levelId = $l_id;
                                        $gradeId = $g_id;
                                        $sectionId = $s_id_key === 'none' ? null : $s_id_key;
                                        $foundSectionMatch = true;
                                        break;
                                    }
                                }

                                if ($foundSectionMatch) {
                                    $foundClass = true;
                                    break; // Exit inner grade loop
                                } else {
                                    if ($trimmedSectionName !== '') {
                                        $rowErrors[] = "Kelas '$sectionName' not found for Tingkat '$gradeName' / Jenjang '$levelName'.";
                                    } else {
                                        $rowErrors[] = "No matching Kelas (including empty/no specific class) found for Tingkat '$gradeName' / Jenjang '$levelName'.";
                                    }
                                    break;
                                }
                            }
                        }
                        if ($foundClass || !empty($rowErrors)) {
                            break;
                        }
                    }

                    if (!$foundClass && empty($rowErrors)) {
                        $rowErrors[] = "Combination of Jenjang '$levelName' and Tingkat '$gradeName' not found.";
                    }
                }

                $dobFormatted = null;
                if (!empty($dobRaw)) {
                    try {
                        if (is_numeric($dobRaw)) {
                            $dobFormatted = ExcelDate::excelToDateTimeObject($dobRaw)->format('Y-m-d');
                        } else {
                            $dateTime = new DateTime(str_replace('/', '-', $dobRaw));
                            $dobFormatted = $dateTime->format('Y-m-d');
                        }
                    } catch (Exception $e) {
                        $rowErrors[] = 'Tanggal Lahir invalid.';
                    }
                }

                if (empty($rowErrors)) {
                    $importedRowCount++;
                    $validStudentData[] = [
                        'nis' => $nis,
                        'name' => $name,
                        'dob' => $dobFormatted,
                        'address' => $address === '' ? null : $address,
                        'phone' => $phone === '' ? null : $phone,
                        'email' => $email === '' ? null : $email,
                        'parent_phone' => $parentPhone,
                        'role' => USER_ROLE_STUDENT, // Ensure constant is defined
                    ];
                    $validClassDetails[] = [
                        'nis' => $nis,
                        'level_id' => $levelId,
                        'grade_id' => $gradeId,
                        'section_id' => $sectionId, // Use the found sectionId (can be null)
                    ];
                } else {
                    $errorRowsData[] = [
                        'original_data' => $originalRowValues,
                        'errors' => implode('; ', $rowErrors),
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('Error processing XLSX file or class list: ' . $e->getMessage());
            return ApiResponse::error('Error reading, processing the XLSX file, or loading class data.', 500);
        }

        if (!empty($errorRowsData)) {
            $errorSpreadsheet = new Spreadsheet();
            $errorSheet = $errorSpreadsheet->getActiveSheet();
            $outputHeaders = array_values($originalHeaders);
            $outputHeaders[] = 'Errors';
            $errorSheet->fromArray([$outputHeaders], null, 'A1');

            $errorRowIndex = 2;
            foreach ($errorRowsData as $errorDetail) {
                $outputRow = $errorDetail['original_data'];
                $outputRow[] = $errorDetail['errors'];
                $errorSheet->fromArray($outputRow, null, 'A' . $errorRowIndex, true);
                $errorRowIndex++;
            }

            $highestColumn = $errorSheet->getHighestColumn();
            foreach (range('A', $highestColumn) as $columnID) {
                $errorSheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $errorSheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray($headerStyle);

            $writer = new Xlsx($errorSpreadsheet);
            if (ob_get_length()) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="import_student_errors.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit();
        } elseif (!empty($validStudentData)) {
            try {
                $this->createStudents($validStudentData, $validClassDetails);
                return ApiResponse::success("Successfully imported $importedRowCount students.");
            } catch (Exception $e) {
                return ApiResponse::error('Validation successful, but failed to save students to database. Error: ' . $e->getMessage(), 500);
            }
        } elseif ($processedRowCount > 0) {
            return ApiResponse::success('File processed, but no valid student data found to import.', 200);
        } else {
            return ApiResponse::error('The uploaded file appears to be empty or has no data rows.', 400);
        }
    }
}
