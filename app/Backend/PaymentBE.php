<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use DateTime;
use Error;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;
use LDAP\Result;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PaymentBE
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

    public function getPayments()
    {        
        $query = "SELECT p.id, u.name AS payment, p.trx_amount, p.trx_timestamp, p.details, c.virtual_account
                FROM payments AS p INNER JOIN users AS u ON p.user_id = u.id LEFT JOIN user_class c ON c.user_id = u.id
                WHERE TRUE ";

        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $query .= " AND u.name LIKE '%$search%'";
        }

        if (!empty($_GET['year-filter'])) {
            $year = $_GET['year-filter'];
            $academicYear = explode('/', $year, 2);

            $years = [
                'min' => "$academicYear[0]-07-01",
                'max' => "$academicYear[1]-06-30"
            ];

            $query .= " AND p.trx_timestamp BETWEEN '$years[min]' AND '$years[max]'";
        }
        if (!empty($_GET['month-filter'])) {
            $month = $_GET['month-filter'];
            $query .= " AND MONTH(p.trx_timestamp) = $month";
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

    public function importPaymentsFromXLSX()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 405);
        }

        if (!isset($_FILES['import-payments']) || $_FILES['import-payments']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('File upload error occurred.', 400);
        }

        $filePath = $_FILES['import-payments']['tmp_name'];
        $allowedMimes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $fileMime = mime_content_type($filePath);
        if (!in_array($fileMime, $allowedMimes)) {
            return ApiResponse::error('Invalid file type. Please upload an XLSX file.', 400);
        }

        $validPaymentData = [];
        $vaList = [];
        $errorRowsData = [];
        $processedRowCount = 0;
        $importedRowCount = 0;

        try{
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $originalHeaders = $sheet->rangeToArray('A1:E1', null, true, false, true)[1] ?? [];

            for ($row = 2; $row <= $highestRow; $row++) {
                $processedRowCount++;
                $originalRowValues = $sheet->rangeToArray('A' . $row . ':E' . $row, null, true, false, false)[0] ?? [];
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
                $va = trim($rowData['B'] ?? '');
                $trxAmount = trim($rowData['C'] ?? '');
                $notes = trim($rowData['D'] ?? '');
                $timestampRaw = trim($rowData['E'] ?? '');

                if ($nis === '') {
                    $rowErrors[] = 'NIS is required.';
                }
                if ($va === '') {
                    $rowErrors[] = 'Virtual Account is required.';
                }
                if ($trxAmount === '') {
                    $rowErrors[] = 'Transaction Amount is required.';
                }

                $timestamp = Call::timestamp();
                if (!empty($timestampRaw)) {
                    try {
                        if (is_numeric($timestampRaw)) {
                            $timestamp = ExcelDate::excelToDateTimeObject($timestampRaw)->format('Y-m-d');
                        } else {
                            $dateTime = new DateTime(str_replace('/', '-', $timestampRaw));
                            $timestamp = $dateTime->format('Y-m-d');
                        }
                    } catch (Exception $e) {
                        $rowErrors[] = 'Timestamp invalid.';
                    }
                }

                if (!empty($rowErrors)) {
                    $errorRowsData[] = [
                        'original_data' => $originalRowValues,
                        'errors' => implode('; ', $rowErrors),
                    ];
                    continue;
                } 

                $importedRowCount++;
                if(isset($validPaymentData[$va])){
                    $d = $validPaymentData[$va];
                    $d['trxAmount'] += $trxAmount;
                    $d['notes'][] = $notes;
                    $d['timestmap'] = (new DateTime($d['timestmap']) > new DateTime($timestamp)) ? $d['timestmap'] : $timestamp;
                } else {
                    $vaList[] = $va;

                    $validPaymentData[$va] = [
                        'nis' => $nis,
                        'va' => $va,
                        'trxAmount' => $trxAmount,
                        'notes' => [$notes],
                        'timestamp' => $timestamp
                    ];
                }
            }

        } catch (\Exception $e) {
            error_log('Error processing XLSX file or class list: ' . $e->getMessage());
            return ApiResponse::error('Error reading, processing the XLSX file, or loading class data.', 500);
        }

        // Get Bill lists
        $vaString = implode(",", $vaList);
        $billQuery = "SELECT
                        virtual_account, (SUM(trx_amount) + SUM(late_fee)) AS trx_amount
                      FROM 
                        bills
                      WHERE 
                        trx_status IN (?,?) AND
                        virtual_account IN ($vaString)
                      GROUP BY 
                        virtual_account";
        $status = $this->status;
        $billStmt = $this->db->query($billQuery, [$status['active'], $status['unpaid']]);
        $billAmountResult = $this->db->fetchAll($billStmt);
        $billAmounts = [];
        $paymentData = [];
        foreach($billAmountResult as $amount){
            $billAmounts[$amount['virtual_account']] = $amount['trx_amount'];
        }

        foreach($validPaymentData as $va => $detail){
            if((float)$billAmounts[$va] == (float)$detail['trxAmount']){
                $paymentData['va'][] = $va;
                $paymentData['detail'][] = $detail;
            } else {
                $requiredAmount = FormatHelper::formatRupiah((float)$billAmounts[$va]);
                $inputtedAmount = FormatHelper::formatRupiah((float)$detail['trxAmount']);
                $errorRowsData[] = [
                    'original_data' => [
                        $detail['nis'], $detail['va'], $detail['trxAmount'],
                        $detail['notes'], $detail['timestamp']
                    ],
                    'errors' => "Invalid Value! needing $requiredAmount, but only inputed amount $inputtedAmount",
                ];
            }
        }

        $vaString = implode(",", $paymentData['va']);
        $billDetailQuery = "SELECT
                                b.id, b.user_id, b.virtual_account, 
                                b.trx_detail, u.parent_phone
                            FROM
                                bills b INNER JOIN
                                users u ON u.id = b.user_id
                            WHERE
                                virtual_account IN ($vaString) AND
                                trx_status IN (?, ?)";
        $billDetailStmt = $this->db->query($billDetailQuery, [$status['active'], $status['unpaid']]);

        $detailData = [];
        $paymentData = [];
        $billId = [];
        $waMsg = [];

        foreach($this->db->fetchAll($billDetailStmt) as $r){
            $va = $r['virtual_account'];
            $billId[] = $r['id'];
            $items = [];
            $total = (float)0;
            $convertedDetails= json_decode($r['trx_detail'], true);
            foreach($convertedDetails['items'] as $item){
                $items[] = [
                    "item_name" => $item['item_name'],
                    "amount" => $item['amount'],
                    "billing_month" => $convertedDetails['billing_month']
                ];
                $total += (float)$item['amount'];
            }
            if (!isset($detailData[$va])){
                $detailData[$va] = [
                    "name" => $convertedDetails['name'] ?? "",
                    "virtual_account" => $r['virtual_account'] ?? '',
                    "class" => $convertedDetails['class'] ?? "",
                    "items" => $items ?? [],
                    "notes" => $r['notes'] ?? "",
                    "total_payment" => $total,
                    "confirmation_date" => Call::timestamp(),
                ];
            } else {
                array_push($detailData[$va]['items'], ...$items);
                $detailData[$va]['total_payment'] += (float)$total;
            }

            $bill_id = ($paymentData[$va]["bill_id"] ?? 0) > $r['id'] ? $paymentData[$va]["bill_id"] : $r['id'];

            $paymentData[$va] = [
                "bill_id" => $bill_id,
                "user_id" => $r['user_id'],
                "trx_amount" => $validPaymentData[$va]['trxAmount'],
                "trx_timestamp" => $validPaymentData[$va]['timestamp'],
                "details" => json_encode($detailData[$va])
            ];

            $url = $_SERVER['HTTP_HOST'];
            $encrypted = $this->generateInvoiceURL($r['user_id'], $bill_id);

            $waMsg[$va] = [
                'target' => $r['parent_phone'],
                'message' => "Pembayaran SPP untuk $convertedDetails[name] telah masuk ke dalam sistem. Untuk mendapatkan detail resi pembayaran, bisa menggunakan link berikut:\n\n
                http://$url/invoice/$encrypted",
                'delay' => 1
            ];
        }

        $paymentFinal = [];
        foreach($paymentData as $data){
            $paymentFinal[] = $data;
        }

        if(!empty($errorRowsData)){
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
            header('Content-Disposition: attachment; filename="import_payments_errors.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }

        try{
            $this->db->beginTransaction();
            
            $paymentResult = $this->db->insert('payments', $paymentFinal);
            if(!$paymentResult){
                throw new Exception("Failed to input Payment to Database");
            }

            $now = Call::timestamp();
            $b = implode(',', $billId);

            $q = "UPDATE 
                    bills b 
                  SET 
                    b.trx_status = CASE
                        WHEN b.trx_status = '$status[unpaid]' THEN '$status[late]'
                        WHEN b.trx_status = '$status[active]' THEN '$status[paid]'
                    END,
                    b.trx_detail = JSON_SET(b.trx_detail, '$.status', b.trx_status, '$.payment_date', '$now')
                  WHERE b.id IN ($b)";

            $updateBillStmt = $this->db->prepare($q);
            if(!$updateBillStmt){
                throw new Exception("Failed to input Payment to Database");
            }

            $updateBillStmt->execute();
            $messages = json_encode($waMsg);
            $fonnte = Fonnte::sendMessage(['data' => $messages]);

            if(!$fonnte){
                throw new Exception("Failed to sent WhatsApp Message to Parents");
            }

            $this->db->commit();

            return ApiResponse::success($waMsg, 'Upload Payments successful');
        } catch (\Exception $e){
            return ApiResponse::error("Failed to save payments to database: " . $e->getMessage());
        }
    }

    protected function generateInvoiceURL($user, $bill)
    {
        $key = $_ENV['ENCRYPTION_KEY'];
        $method = $_ENV['ENCRYPTION_METHOD'];

        $string = "$user||$bill";

        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);
        $encrypted_with_iv = base64_encode($iv . $encrypted);

        return $encrypted_with_iv;
    }

    protected function decryptInvoiceCode($encrypted)
    {
        $key = $_ENV['ENCRYPTION_KEY'];
        $method = $_ENV['ENCRYPTION_METHOD'];

        $data = base64_decode($encrypted);

        $ivLength = openssl_cipher_iv_length($method);

        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        $string = openssl_decrypt($cipherText, $method, $key, 0, $iv);
        
        if(!$string){
            return [
                'status' => false,
                'error' => "Failed to Decrypt Details"
            ];
        }

        list($user, $bill) = explode("||", $string);

        $query = "SELECT
                    details
                  FROM
                    payments
                  WHERE
                    bill_id = ? AND user_id = ?";
        
        $stmt = $this->db->query($query, [$bill, $user]);
        $result = $this->db->fetchAssoc($stmt);

        if(!$result){
            return [
                'status' => false,
                'error' => "Payment Not Found"
            ];
        }

        return [
            'status' => true,
            'details' => $result 
        ];
    }

    public function getPublicInvoice($segments)
    {
        $cyper = $segments[1];
        $data = $this->decryptInvoiceCode($cyper);
        echo ApiResponse::error($data);
    }
}