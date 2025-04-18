<?php

namespace app\Backend;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaymentBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getPayments($params = [])
    {        
        $query = "SELECT p.id, u.name AS student, p.trx_amount, p.trx_timestamp, p.details
                FROM payments AS p INNER JOIN users AS u ON p.user_id = u.id
                WHERE TRUE";

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query .= " AND u.name LIKE '%$search%";
        }

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }

    public function getFeeCategories()
    {
        $query = "SELECT * FROM fee_categories";
        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }

    protected function getPaymentFormat()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'NIS',             
            'Virtual Account',            
            'Trx Amount',         
            'Notes',         
            'Timestamp',  
        ];

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

    public function getPaymentFormatXLSX()
    {
        $spreadsheet = $this->getPaymentFormat();
        $writer = new Xlsx($spreadsheet);

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="import_payment_format.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}