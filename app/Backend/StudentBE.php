<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\FormatHelper;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getStudents($params = [])
    {        
        $query = "SELECT
                    u.nis, u.name, l.name AS level, g.name AS grade, s.name AS section,
                    u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee,
                    MAX(p.trx_timestamp) AS latest_payment
                  FROM 
                    users u
                    LEFT JOIN user_class c ON u.id = c.user_id
                    INNER JOIN levels l ON c.level_id = l.id
                    INNER JOIN grades g ON c.grade_id = g.id
                    INNER JOIN sections s ON c.section_id = s.id
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
                u.nis, u.name, l.name, g.name, s.name,
                u.phone, u.email, u.parent_phone, c.virtual_account, c.monthly_fee";

        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }

    protected function getStudentFormat()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'NIS',             
            'Nama',            
            'Jenjang',         
            'Tingkat',         
            'Kelas',           
            'Alamat',          
            'Tanggal Lahir',   
            'Nomor Telepon',   
            'Alamat Email',    
            'Nomor Orang Tua'  
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
        exit;
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
                    INNER JOIN levels l ON c.level_id = l.id
                    INNER JOIN grades g ON c.grade_id = g.id
                    INNER JOIN sections s ON c.section_id = s.id
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
        exit;
    }

    public function deleteStudent($id)
    {
        $now = new Datetime();
        $query = "UPDATE users SET deleted_at='$now' WHERE id=$id";
        return $this->db->query($query);
    }

    protected function inputStudent($students)
    {
        
    }
    
}