<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\FormatHelper;
use DateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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

        if ($params['search'] != NULL_VALUE) {
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
        }

        if ($params['level'] != NULL_VALUE) {
            $paramQuery .= " AND l.id = $params[level]";
        }
        if ($params['grade'] != NULL_VALUE) {
            $paramQuery .= " AND g.id = $params[grade]";
        }
        if ($params['section'] != NULL_VALUE) {
            $paramQuery .= " AND s.id = $params[section]";
        }

        if (($params['start_date'] != NULL_VALUE) & ($params['end_date'] != NULL_VALUE)) {
            $startDateStr = $params['start_date'];
            $startDate = new DateTime($startDateStr);
            $start_date_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end_date'];
            $endDate = new DateTime($endDateStr);
            $end_date_plus_one_month = $endDate->format('Y-m-d');

            $paramQuery .= " AND b.payment_due >= '$start_date_plus_one_month' AND b.payment_due <= '$end_date_plus_one_month'";
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
    protected function getTransaction(bool $for_akt, $params = [] )
    {
        $paramQuery = NULL_VALUE;

        if ($params['search'] != NULL_VALUE) {
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
        }

        if ($params['level'] != NULL_VALUE) {
            $paramQuery .= " AND l.id = $params[level]";
        }
        if ($params['grade'] != NULL_VALUE) {
            $paramQuery .= " AND g.id = $params[grade]";
        }
        if ($params['section'] != NULL_VALUE) {
            $paramQuery .= " AND s.id = $params[section]";
        }

        if (($params['start_date'] != NULL_VALUE) & ($params['end_date'] != NULL_VALUE)) {
            $startDateStr = $params['start_date'];
            $startDate = new DateTime($startDateStr);
            if($for_akt){
                $startDate->modify('-1 month');
            }
            $start_date_minus_one_month = $startDate->format('Y-m-d');

            $endDateStr = $params['end_date'];
            $endDate = new DateTime($endDateStr);
            if($for_akt){
                $endDate->modify('-1 month');
            }
            $end_date_minus_one_month = $endDate->format('Y-m-d');

            $paramQuery .= " AND b.payment_due >= '$start_date_minus_one_month' AND b.payment_due <= '$end_date_minus_one_month'";
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

    protected function getLateFee($params = [])
    {
        $status = $this->status;

        $paramQuery = NULL_VALUE;

        if ($params['search'] != NULL_VALUE) {
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
        }

        if ($params['level'] != NULL_VALUE) {
            $paramQuery .= " AND l.id = $params[level]";
        }
        if ($params['grade'] != NULL_VALUE) {
            $paramQuery .= " AND g.id = $params[grade]";
        }
        if ($params['section'] != NULL_VALUE) {
            $paramQuery .= " AND s.id = $params[section]";
        }

        if (($params['start_date'] != NULL_VALUE) & ($params['end_date'] != NULL_VALUE)) {
            $start_date = $params['start_date'];
            $end_date = $params['end_date'];

            $paramQuery .= " AND b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        }

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
            TRUE $paramQuery";

        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }
    protected function getPaidLateFee($params = [])
    {
        $status = $this->status;
        $paramQuery = NULL_VALUE;

        if ($params['search'] != NULL_VALUE) {
            $paramQuery .= " AND (u.name LIKE '%$params[search]%' OR c.virtual_account LIKE '%$params[search]%')";
        }

        if ($params['level'] != NULL_VALUE) {
            $paramQuery .= " AND l.id = $params[level]";
        }
        if ($params['grade'] != NULL_VALUE) {
            $paramQuery .= " AND g.id = $params[grade]";
        }
        if ($params['section'] != NULL_VALUE) {
            $paramQuery .= " AND s.id = $params[section]";
        }

        if (($params['start_date'] != NULL_VALUE) & ($params['end_date'] != NULL_VALUE)) {
            $start_date = $params['start_date'];
            $end_date = $params['end_date'];

            $paramQuery .= " AND b.payment_due >= '$start_date' AND b.payment_due <= '$end_date'";
        }

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
            TRUE $paramQuery";

        $result = $this->db->fetchAssoc($this->db->query($query));

        return $result;
    }

    public function getJournals($level = null, $for_akt = false, $for_export = false)
    {
        $journalDate = $this->getDate();
        $monthInt = $journalDate['month'];

        if($monthInt === 0){
            $journal_details =  [
                                    'piutang' => 0,
                                    'per_tenth_day' => 0,
                                    'late_fee_amount' => 0,
                                    'paid_late_fee' => 0,
                                ];
            return $journal_details;
        }


        $listedDetails = $journalDate['details'];

        $semester       = $listedDetails[1];
        $year           = $listedDetails[2];
        $month          = isset($listedDetails[3]) ? $listedDetails[3] : null;


        $month = sprintf('%02d', $monthInt);

        $academicYear = Call::academicYear(ACADEMIC_YEAR_EIGHT_SLASH_FORMAT, [
            'semester'  => $semester == 1 ? FIRST_SEMESTER : SECOND_SEMESTER,
            'date'      => Call::splitDate("01-$month-$year")
        ]);
        $splitDate = Call::splitDate();

        $params = [
            'search' => $_GET['search'] ?? NULL_VALUE,
            'academic_year' => $_GET['year-filter'] ?? $academicYear,
            'semester' => $_GET['semester-filter'] ?? $semester,
            'month' => $_GET['month-filter'] ?? $month,
            'level' => $_GET['level-filter'] ?? $level,
            'grade' => $_GET['grade-filter'] ?? NULL_VALUE,
            'section' => $_GET['section-filter'] ?? NULL_VALUE,
        ];

        $semesterInt = $params['semester'] == FIRST_SEMESTER ? 1 : 2;

        $startSemester = Call::getFirstDay(
            [
                'year' => $params['academic_year'],
                'semester' => $semesterInt,
                'month' => $params['semester'] == FIRST_SEMESTER ? '07' : '01',
            ],
            FIRST_DAY_FROM_ACADEMIC_YEAR_DETAILS,
        );

        $years = explode("/", $params['academic_year']);
        $yearInt = $years[intval($semesterInt) - 1];

        $modifyDate = new DateTime("11-$params[month]-$yearInt");
        if ((int) $splitDate['day'] <= 10 && $params['month'] != $month) {
            $modifyDate->modify('last day of this month');
        }
        $endRange = $modifyDate->format(DATE_FORMAT);
        $modifyDate->modify('last day of this month');
        $dueDateParamMonth = $modifyDate->format(DATE_FORMAT);
        $firstDayMonth = $modifyDate->modify('first day of this month');
        $firstDayOfTheMonth = $firstDayMonth->format(DATE_FORMAT);

        if(isset($_GET['month-filter'])){
            $startSemester = "$yearInt-$params[month]-01";
        }

        $startDateParams = array_merge($params, ['start_date' => $for_akt ? $firstDayOfTheMonth : $startSemester, 'end_date' => $endRange]);
        $dueDateParams = array_merge($params, ['start_date' => $for_akt ? $firstDayOfTheMonth : $startSemester, 'end_date' => $dueDateParamMonth]);
        $startDateResult = $this->getUnpaidTransaction($startDateParams);
        $dueDateResult = $this->getTransaction($for_akt, $dueDateParams);
        $lateFeeResult = $this->getLateFee($startDateParams);
        $paidLateResult = $this->getPaidLateFee($startDateParams);

        $journal_details =  [
                                'piutang' => $startDateResult['bank'],
                                'per_tenth_day' => $dueDateResult['amount'],
                                'late_fee_amount' => $lateFeeResult['late_fee'],
                                'paid_late_fee' => $paidLateResult['late_fee'],
                            ];
        if ($for_export){
            $this->exportJournals($journal_details, $params);
        }
        return $journal_details;
    }

    protected function getDate() : array
    {
        $checkLatestStatus = "SELECT log_name 
                              FROM logs 
                              WHERE log_name LIKE 'BCHECK-%' OR log_name LIKE 'BCREATE-%'
                              ORDER BY created_at DESC 
                              LIMIT 1;";
        
        $checkLatestLog = $this->db->fetchAssoc($this->db->query($checkLatestStatus));
        if(isset($checkLatestLog)){
            $checkLog       = explode('-', $checkLatestLog['log_name']);
        } else {
            return ['month' => 0];
        }

        if($checkLog[0] === LOG_CREATE_BILLS){
            $log = "SELECT log_name, created_at FROM logs WHERE log_name LIKE 'BCREATE-%' ORDER BY created_at DESC LIMIT 1";
            $logDetail = $this->db->fetchAssoc($this->db->query($log));
        } else if($checkLog[0] == LOG_CHECK_BILLS) {
            $log = "SELECT log_name, created_at FROM logs WHERE log_name LIKE 'BCHECK-%' ORDER BY created_at DESC LIMIT 1";
            $logDetail = $this->db->fetchAssoc($this->db->query($log));
        } else {
            return ['month' => 0];
        }

        $logName = $logDetail['log_name'];
        $logTime = $logDetail['created_at'];

        $listedDetails  = explode('-', $logName);
        $semester       = $listedDetails[1];
        $year           = $listedDetails[2];
        $actMonth       = isset($listedDetails[3]) ? $listedDetails[3] : null;

        $logTime = new DateTime($logTime);
        
        $logMonth = (int)$logTime->format('m');

        if(!isset($actMonth)){
            $monthInt = $semester === FIRST_SEMESTER ? 7 : 1;
            return ['month' => $monthInt, 'details' => $listedDetails, 'type' => 'create'];
        }

        $actTime = new DateTime("10-$actMonth-$year 23:59:59");
        $diff = $logTime->diff($actTime);

        if($diff->m === 0) { // in range of current bills
            if($logMonth === $actMonth) { // on the same month
                return ['month' => $actMonth+1, 'details' => $listedDetails, 'type' => 'check'];
            }
            if($diff->invert === 1){ // before the month
                return ['month' => $actMonth+1, 'details' => $listedDetails, 'type' => 'check'];
            }
            return ['month' => $actMonth+1, 'details' => $listedDetails, 'type' => 'check'];
        }

        return ['month' => $actMonth+1, 'details' => $listedDetails, 'type' => 'check'];
    }

    public function exportJournals($data, $filter)
    {        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4);

        $margin = 0.50;
        $sheet->getPageMargins()->setTop($margin);
        $sheet->getPageMargins()->setRight($margin);
        $sheet->getPageMargins()->setLeft($margin);
        $sheet->getPageMargins()->setBottom($margin);

        $formatCurrency = function ($amount) {
            return FormatHelper::formatRupiah($amount);
        };

        $sheet->getColumnDimension('A')->setWidth(30); 
        $sheet->getColumnDimension('B')->setWidth(25); 
        $sheet->getColumnDimension('C')->setWidth(2); 

        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(25);

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Data Penjurnalan');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Sekolah Nusaputera Semarang');
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3:E3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
        $sheet->getStyle('A3:E3')->getBorders()->getBottom()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('000000'));

        $sheet->getRowDimension(3)->setRowHeight(5);

        $currentRow = 4;

        $headerStyleArray = [
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [ 
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $cellStyleArray = [
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $rowHeightHeaderTables = 18;

        $sheet->mergeCells('A'.$currentRow.':A'.$currentRow)->setCellValue('A'.$currentRow, 'Semester');
        $sheet->mergeCells('B'.$currentRow.':C'.$currentRow)->setCellValue('B'.$currentRow, 'Tahun Ajaran');
        $sheet->mergeCells('D'.$currentRow.':E'.$currentRow)->setCellValue('D'.$currentRow, 'Kelas');
        $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray($headerStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;

        $semesterText = '';
        if (($filter['semester'] ?? null) == FIRST_SEMESTER) $semesterText = 'Ganjil';
        elseif (($filter['semester'] ?? null) == SECOND_SEMESTER) $semesterText = 'Genap';
        else $semesterText = (string)($filter['semester'] ?? '-');
        $tahunAjaranText = $filter['academic_year'] ?? '-';
        $class_list = [];

        $query = "SELECT
                    l.id AS level_id, l.name AS level_name, l.va_code AS va_prefix,
                    g.id AS grade_id, g.level_id AS grade_level_id, g.name AS grade_name, g.base_monthly_fee AS grade_monthly, g.base_late_fee AS grade_late,
                    s.id AS section_id, s.grade_id AS section_level_id, s.name AS section_name, s.base_monthly_fee AS section_monthly, s.base_late_fee AS section_late
                  FROM
                    levels l LEFT JOIN
                    grades g ON l.id = g.level_id LEFT JOIN
                    sections s ON g.id = s.grade_id";
        $classResult = $this->db->fetchAll($this->db->query($query));

        foreach ($classResult as $data) {
            $class_list[$data['level_id']][$data['grade_id']][$data['section_id']] = [
                'va_prefix' => $data['va_prefix'],
                'level_name' => $data['level_name'],
                'grade_name' => $data['grade_name'],
                'section_name' => $data['section_name'],
                'monthly_fee' => $data['grade_monthly'] != 0 ? $data['grade_monthly'] : $data['section_monthly'],
                'late_fee' => $data['grade_late'] != 0 ? $data['grade_late'] : $data['section_monthly'],
            ];
        }  

        $class = $class_list[$filter['level']][$filter['grade']][$filter['section']];
        $kelasText = $class['level_name']." ". ($class['grade_name'] ?? null)." ". ($class['section_name'] ?? null);

        $sheet->mergeCells('A'.$currentRow.':A'.$currentRow)->setCellValue('A'.$currentRow, $semesterText);
        $sheet->mergeCells('B'.$currentRow.':C'.$currentRow)->setCellValue('B'.$currentRow, $tahunAjaranText);
        $sheet->mergeCells('D'.$currentRow.':E'.$currentRow)->setCellValue('D'.$currentRow, $kelasText);
        $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray($cellStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;
        $currentRow++;

        $sheet->mergeCells('A'.$currentRow.':E'.$currentRow)->setCellValue('A'.$currentRow, 'Nama');
        $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray($headerStyleArray); 
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;

        $sheet->mergeCells('A'.$currentRow.':E'.$currentRow)->setCellValue('A'.$currentRow, $filter['search'] ?? '-');
        $sheet->getStyle('A'.$currentRow.':E'.$currentRow)->applyFromArray($cellStyleArray); 
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;
        $currentRow++;

        $drawJournalSubTable = function (
            $sheet,
            $labelCol, $valueCol, $startRow,
            $title,
            $label1, $value1, $isLabel1Bold, $label1Align,
            $label2, $value2, $isLabel2Bold, $label2Align,
            $formatCurrencyFunc
        ) use ($rowHeightHeaderTables) {

            $titleCellsRange = $labelCol.$startRow.':'.$valueCol.$startRow;
            $sheet->mergeCells($titleCellsRange);
            $sheet->setCellValue($labelCol.$startRow, $title);
            $styleTitle = $sheet->getStyle($titleCellsRange);
            $styleTitle->getFont()->setBold(true)->setSize(9);
            $styleTitle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $styleTitle->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $styleTitle->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleTitle->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($startRow)->setRowHeight($rowHeightHeaderTables - 2);
            $row = $startRow + 1;

            // Row 1
            $sheet->setCellValue($labelCol.$row, $label1);
            $styleL1 = $sheet->getStyle($labelCol.$row);
            $styleL1->getFont()->setBold($isLabel1Bold)->setSize(9);
            $styleL1->getAlignment()->setHorizontal($label1Align)->setVertical(Alignment::VERTICAL_CENTER);
            $styleL1->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleL1->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
            $styleL1->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


            $sheet->setCellValue($valueCol.$row, $formatCurrencyFunc($value1));
            $styleV1 = $sheet->getStyle($valueCol.$row);
            $styleV1->getFont()->setBold(false)->setSize(9);
            $styleV1->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $styleV1->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $styleV1->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
            $styleV1->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


            $sheet->getRowDimension($row)->setRowHeight($rowHeightHeaderTables - 2);
            $row++;

            // Row 2
            $sheet->setCellValue($labelCol.$row, $label2);
            $styleL2 = $sheet->getStyle($labelCol.$row);
            $styleL2->getFont()->setBold($isLabel2Bold)->setSize(9);
            $styleL2->getAlignment()->setHorizontal($label2Align)->setVertical(Alignment::VERTICAL_CENTER);
            $styleL2->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleL2->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


            $sheet->setCellValue($valueCol.$row, $formatCurrencyFunc($value2));
            $styleV2 = $sheet->getStyle($valueCol.$row);
            $styleV2->getFont()->setBold(false)->setSize(9);
            $styleV2->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $styleV2->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $styleV2->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($row)->setRowHeight($rowHeightHeaderTables - 2);

            return $row + 1;
        };

        $dataStartRow = $currentRow;

        $nextRowLeft = $drawJournalSubTable(
            $sheet, 'A', 'B', $dataStartRow,
            'PENERBITAN UANG SEKOLAH',
            'PIUT UANG SEKOLAH', $data['per_first_day'] ?? 0, true, Alignment::HORIZONTAL_LEFT,
            'PENERIMAAN - UANG SEKOLAH', $data['per_first_day'] ?? 0, false, Alignment::HORIZONTAL_RIGHT,
            $formatCurrency
        );

        $nextRowRight = $drawJournalSubTable(
            $sheet, 'D', 'E', $dataStartRow,
            'PELUNASAN UANG SEKOLAH',
            'BANK BNI', $data['per_tenth_day'] ?? 0, true, Alignment::HORIZONTAL_LEFT,
            'PIUT. UANG SEKOLAH', $data['per_tenth_day'] ?? 0, false, Alignment::HORIZONTAL_RIGHT,
            $formatCurrency
        );

        $currentRow = max($nextRowLeft, $nextRowRight);
        $currentRow++;

        $nextRowLeft = $drawJournalSubTable(
            $sheet, 'A', 'B', $currentRow,
            'PENERBITAN DENDA',
            'PIUT. DENDA', $data['late_fee_amount'] ?? 0, true, Alignment::HORIZONTAL_LEFT,
            'PENERIMAAN - UANG DENDA', $data['late_fee_amount'] ?? 0, false, Alignment::HORIZONTAL_RIGHT,
            $formatCurrency
        );

        $nextRowRight = $drawJournalSubTable(
            $sheet, 'D', 'E', $currentRow,
            'PEMBAYARAN DENDA LUNAS',
            'BANK BNI', $data['paid_late_fee'] ?? 0, true, Alignment::HORIZONTAL_LEFT,
            'PIUT. DENDA', $data['paid_late_fee'] ?? 0, false, Alignment::HORIZONTAL_RIGHT,
            $formatCurrency
        );


        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Mpdf::class);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');

        $pdfFilename = 'Data_Penjurnalan_Lengkap_' . str_replace('/', '-', $tahunAjaranText) . '_' . $semesterText . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $pdfFilename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
