<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\FormatHelper;
use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class JournalBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    protected function schoolFeesIssuance(bool $for_akt, $params = [])
    {
        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan = ?";
            $p[] = $params['bulan'];
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $bulanAwal = $startDate->format('m');
            $tahunAwal = $startDate->format('Y');
            $bulanAkhir = $endDate->format('m');
            $tahunAkhir = $endDate->format('Y');

            if($for_akt){
                $q[] = "d.bulan >= ?";
                $p[] = $bulanAwal;
                $q[] = "d.tahun >= ?";
                $p[] = $tahunAwal;

                $q[] = "d.bulan <= ?";
                $p[] = $bulanAkhir;
                $q[] = "d.tahun <= ?";
                $p[] = $tahunAkhir;
            }
            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;

        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis != 'late' AND d.lunas = false
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);


        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }
    protected function schoolFeeSettlement(bool $for_akt, $params = [])
    {
        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan = ?";
            $p[] = $params['bulan'];
        }

        if (($params['start'] != NULL_VALUE) && ($params['end'] != NULL_VALUE)) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            if ($for_akt) {
                $startDate->modify('+10 days');
                $startDate->modify('-1 month');
            }
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;
        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis != 'late' AND d.lunas = true
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);

        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }

    protected function getLateFee(bool $for_akt, $params = [])
    {
        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan = ?";
            $p[] = $params['bulan'];
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;
        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis = 'late' AND d.lunas = false
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);

        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }
    protected function getPaidLateFee(bool $for_akt, $params = [])
    {
        $paramQuery = NULL_VALUE;

        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan = ?";
            $p[] = $params['bulan'];
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;
        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis = 'late' AND d.lunas = true
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);

        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }

    protected function getVAFee(bool $for_akt, $params = [])
    {
        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan <= ?";
            $p[] = (int)$params['bulan'];
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;
        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis = 'admin' AND d.lunas = false
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);

        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }
    protected function getPaidVAFee(bool $for_akt, $params = [])
    {
        $paramQuery = NULL_VALUE;

        $q = ["u.deleted_at IS NULL"];
        $p = [];

        if (!empty($params['siswa'])) {
            $q[] = "u.id = ?";
            $p[] = $params['siswa'];
        }
        if (!empty($params['level'])) {
            $q[] = "j.id = ?";
            $p[] = $params['level'];
        }
        if (!empty($params['grade'])) {
            $q[] = "t.id = ?";
            $p[] = $params['grade'];
        }
        if (!empty($params['section'])) {
            $q[] = "k.id = ?";
            $p[] = $params['section'];
        }
        if (!empty($params['bulan'])) {
            $q[] = "d.bulan <= ?";
            $p[] = $params['bulan'] + 1;
        }

        if (!empty($params['start']) && !empty($params['end'])) {
            $startDateStr = $params['start'];
            $startDate = new DateTime($startDateStr);
            $start_plus_one_month = $startDate->format('Y-m-d');
            $endDateStr = $params['end'];
            $endDate = new DateTime($endDateStr);
            $end_plus_one_month = $endDate->format('Y-m-d');

            $q[] = "b.jatuh_tempo >= ?";
            $p[] = $start_plus_one_month;
            $q[] = "b.jatuh_tempo <= ?";
            $p[] = $end_plus_one_month;
        }

        $query = "SELECT
            SUM(CASE
                WHEN d.jenis = 'admin' AND d.lunas = true
                THEN d.nominal
                ELSE 0
            END) AS result
        FROM
            spp_tagihan_detail d JOIN
            spp_tagihan b ON d.tagihan_id = b.id LEFT JOIN
            siswa u ON b.siswa_id = u.id LEFT JOIN
            jenjang j on u.jenjang_id = j.id LEFT JOIN
            tingkat t on u.tingkat_id = t.id LEFT JOIN
            kelas k on u.kelas_id = k.id
        WHERE " . implode(" AND ", $q);

        $result = $this->db->fetchAssoc($this->db->query($query, $p));

        return $result;
    }

    public function getJournals($level = NULL_VALUE, $for_akt = false, $for_export = false, $bulan = NULL_VALUE)
    {
        $journalDate = Call::splitDate(); 

        $year = $journalDate['year'];

        $params = [
            'siswa' => $_GET['filter-siswa'] ?? NULL_VALUE,
            'year' => $_GET['filter-tahun'] ?? $year,
            'month' => $_GET['filter-bulan'] ?? NULL_VALUE,
            'level' => $_GET['filter-jenjang'] ?? $level,
            'grade' => $_GET['filter-tingkat'] ?? NULL_VALUE,
            'section' => $_GET['filter-kelas'] ?? NULL_VALUE,
        ];

        if ($params['month'] != NULL_VALUE) {
            $start = new DateTime("$year-$params[month]-01");
            $end = clone $start;
            $end->modify('last day of this month');

            $dateFilter = [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ];
        } else if($for_akt) {
            $max = $this->db->fetchAssoc(
                $this->db->query("SELECT MAX(bulan) AS bulan, MAX(tahun) AS tahun FROM spp_tagihan WHERE is_active = 1")
            );
            $start = new DateTime("$year-$max[bulan]-01");
            $end = clone $start;
            $end->modify('last day of this month');
            $dateFilter = [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'bulan' => $bulan
            ];
        } else {
            $dateFilter = [
                'start' => "$year-01-01",
                'end' => "$year-12-31",
            ];
        }

        // get journals
        $params = array_merge($params, $dateFilter);
        $journal_details = [
            'piutang' => $this->schoolFeesIssuance($for_akt, $params)['result'],
            'pelunasan' => $this->schoolFeeSettlement($for_akt, $params)['result'],
            'hutang' => $this->getLateFee($for_akt, $params)['result'],
            'hutang_terbayar' => $this->getPaidLateFee($for_akt, $params)['result'],
            'hutang_va' => $this->getVAFee($for_akt, $params)['result'],
            'hutang_va_terbayar' => $this->getPaidVAFee($for_akt, $params)['result'],
        ];

        if ($for_export) {
            $this->exportJournals($journal_details, $params);
        }
        return $journal_details;
    }

    public function exportJournals($dataJurnal, $filter)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4);

        $margin = 0.5;
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

        $sheet->mergeCells('A' . $currentRow . ':A' . $currentRow)->setCellValue('A' . $currentRow, 'Semester');
        $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow)->setCellValue('B' . $currentRow, 'Tahun Ajaran');
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow)->setCellValue('D' . $currentRow, 'Kelas');
        $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($headerStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;

        $semesterText = '';
        if (($filter['semester'] ?? null) == FIRST_SEMESTER) {
            $semesterText = 'Ganjil';
        } elseif (($filter['semester'] ?? null) == SECOND_SEMESTER) {
            $semesterText = 'Genap';
        } else {
            $semesterText = (string) ($filter['semester'] ?? '-');
        }
        $tahun = $filter['year'] ?? '-';
        $class_list = [];
        $studentName = "";

        if($filter['siswa'] != NULL_VALUE){
            $studentName = $this->db->find('siswa', [
                'id' => $filter['siswa']
            ])['nama'];
        }

        $query = "SELECT
                    tf.jenjang_id AS level_id, tf.tingkat_id AS grade_id, tf.kelas_id AS section_id,
                    j.nama AS level_name, j.va_code AS va_prefix, t.nama AS grade_name, k.nama AS section_name,
                    tf.nominal AS monthly
                  FROM
                    spp_tarif tf INNER JOIN
                    jenjang j ON j.id = tf.jenjang_id LEFT JOIN
                    tingkat t ON t.id = tf.tingkat_id LEFT JOIN
                    kelas k ON k.id = tf.kelas_id
                  WHERE
                    tahun = $tahun
                    ";
        $classResult = $this->db->fetchAll($this->db->query($query));

        foreach ($classResult as $data) {
            $class_list[$data['level_id']][$data['grade_id']][$data['section_id']] = [
                'va_prefix' => $data['va_prefix'],
                'level_name' => $data['level_name'],
                'grade_name' => $data['grade_name'],
                'section_name' => $data['section_name'],
                'monthly_fee' => $data['monthly'],
                'late_fee' => Call::denda(),
            ];
        }

        $class = $class_list[$filter['level']][$filter['grade']][$filter['section']];
        $kelasText = $class['level_name'] . ' ' . ($class['grade_name'] ?? null) . ' ' . ($class['section_name'] ?? null);

        $sheet->mergeCells('A' . $currentRow . ':A' . $currentRow)->setCellValue('A' . $currentRow, $semesterText);
        $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow)->setCellValue('B' . $currentRow, $tahun);
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow)->setCellValue('D' . $currentRow, $kelasText);
        $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($cellStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;
        $currentRow++;

        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow)->setCellValue('A' . $currentRow, 'Nama');
        $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($headerStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;

        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow)->setCellValue('A' . $currentRow, $studentName ?? '-');
        $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($cellStyleArray);
        $sheet->getRowDimension($currentRow)->setRowHeight($rowHeightHeaderTables);
        $currentRow++;
        $currentRow++;

        $drawJournalSubTable = function ($sheet, $labelCol, $valueCol, $startRow, $title, $label1, $value1, $isLabel1Bold, $label1Align, $label2, $value2, $isLabel2Bold, $label2Align, $formatCurrencyFunc) use ($rowHeightHeaderTables) {
            $titleCellsRange = $labelCol . $startRow . ':' . $valueCol . $startRow;
            $sheet->mergeCells($titleCellsRange);
            $sheet->setCellValue($labelCol . $startRow, $title);
            $styleTitle = $sheet->getStyle($titleCellsRange);
            $styleTitle->getFont()->setBold(true)->setSize(9);
            $styleTitle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $styleTitle->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $styleTitle->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleTitle->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($startRow)->setRowHeight($rowHeightHeaderTables - 2);
            $row = $startRow + 1;

            // Row 1
            $sheet->setCellValue($labelCol . $row, $label1);
            $styleL1 = $sheet->getStyle($labelCol . $row);
            $styleL1->getFont()->setBold($isLabel1Bold)->setSize(9);
            $styleL1->getAlignment()->setHorizontal($label1Align)->setVertical(Alignment::VERTICAL_CENTER);
            $styleL1->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleL1->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
            $styleL1->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->setCellValue($valueCol . $row, $formatCurrencyFunc($value1));
            $styleV1 = $sheet->getStyle($valueCol . $row);
            $styleV1->getFont()->setBold(false)->setSize(9);
            $styleV1->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $styleV1->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $styleV1->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
            $styleV1->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($row)->setRowHeight($rowHeightHeaderTables - 2);
            $row++;

            // Row 2
            $sheet->setCellValue($labelCol . $row, $label2);
            $styleL2 = $sheet->getStyle($labelCol . $row);
            $styleL2->getFont()->setBold($isLabel2Bold)->setSize(9);
            $styleL2->getAlignment()->setHorizontal($label2Align)->setVertical(Alignment::VERTICAL_CENTER);
            $styleL2->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $styleL2->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->setCellValue($valueCol . $row, $formatCurrencyFunc($value2));
            $styleV2 = $sheet->getStyle($valueCol . $row);
            $styleV2->getFont()->setBold(false)->setSize(9);
            $styleV2->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
            $styleV2->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $styleV2->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getRowDimension($row)->setRowHeight($rowHeightHeaderTables - 2);

            return $row + 1;
        };

        $dataStartRow = $currentRow;

        $nextRowLeft = $drawJournalSubTable($sheet, 'A', 'B', $dataStartRow, 'PENERBITAN UANG SEKOLAH', 'PIUT UANG SEKOLAH', $dataJurnal['piutang'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PENERIMAAN - UANG SEKOLAH', $dataJurnal['piutang'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);

        $nextRowRight = $drawJournalSubTable($sheet, 'D', 'E', $dataStartRow, 'PELUNASAN UANG SEKOLAH', 'BANK BNI', $dataJurnal['pelunasan'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PIUT. UANG SEKOLAH', $dataJurnal['pelunasan'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);

        $currentRow = max($nextRowLeft, $nextRowRight);
        $currentRow++;

        $nextRowLeft = $drawJournalSubTable($sheet, 'A', 'B', $currentRow, 'PENERBITAN DENDA', 'PIUT. DENDA', $dataJurnal['hutang'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PENERIMAAN - UANG DENDA', $dataJurnal['hutang'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);

        $nextRowRight = $drawJournalSubTable($sheet, 'D', 'E', $currentRow, 'PEMBAYARAN DENDA LUNAS', 'BANK BNI', $dataJurnal['hutang_terbayar'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PIUT. DENDA', $dataJurnal['hutang_terbayar'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);
        $currentRow = max($nextRowLeft, $nextRowRight);
        $currentRow++;
        $nextRowLeft = $drawJournalSubTable($sheet, 'A', 'B', $currentRow, 'PENERBITAN TAGIHAN VA', 'PIUT. LAIN-LAIN', $dataJurnal['hutang_va'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PENDAPATAN LAIN-LAIN', $dataJurnal['hutang_va'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);
        $nextRowRight = $drawJournalSubTable($sheet, 'D', 'E', $currentRow, 'PELUNASAN TAGIHAN VA', 'BANK', $dataJurnal['hutang_va_terbayar'] ?? 0, true, Alignment::HORIZONTAL_LEFT, 'PIUT. LAIN-LAIN', $dataJurnal['hutang_va_terbayar'] ?? 0, false, Alignment::HORIZONTAL_RIGHT, $formatCurrency);


        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Mpdf::class);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');

        $pdfFilename = 'Data_Penjurnalan_Lengkap_' . $tahun . '_' . $semesterText . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="' . $pdfFilename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }
}
