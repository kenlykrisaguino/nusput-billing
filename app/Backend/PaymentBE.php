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
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Mpdf\Output\Destination;

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
                'delay' => '1'
            ];
        }

        $paymentFinal = [];
        foreach($paymentData as $data){
            $paymentFinal[] = $data;
        }

        $queueMsg = [];
        foreach($waMsg as $msg){
            $queueMsg[] = $msg;
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
            $messages = json_encode($queueMsg);
            $fonnte = Fonnte::sendMessage(['data' => $messages]);

            $this->db->commit();

            return ApiResponse::success($fonnte, 'Upload Payments successful');
        } catch (Exception $e){
            return ApiResponse::error("Failed to save payments to database: " . $e->getMessage());
        }
    }

    public function generateInvoiceURL($user, $bill)
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

        // list($user, $bill) = explode("|-|", $string);
        $encrypted = explode("|-|", $string);
        if(count($encrypted) != 2){
            return [
                'status' => false,
                'error' => "Invalid Details"
            ];
        }
        list($user, $bill) = $encrypted;

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
        array_shift($segments);
        $cyper = implode("/", $segments);
        $data = $this->decryptInvoiceCode($cyper);
        return $data;
    }

    public function exportPublicInvoice($segments)
    {
        array_shift($segments);
        $cyper = implode("/", $segments);
        $decryptionResult = $this->decryptInvoiceCode($cyper);

        if (!$decryptionResult['status']) {
            return ApiResponse::error("Code decryption error", 404);
            exit;
        }

        $paymentDetailsJson = $decryptionResult['details']['details'] ?? null;

        if ($paymentDetailsJson === null) {
            return ApiResponse::error("Payment details JSON is missing for invoice code");
            exit;
        }

        $invoiceData = json_decode($paymentDetailsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error("Failed to parse payment details JSON");
        }

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'tempDir' => sys_get_temp_dir() . '/mpdf' 
            ]);

            $studentName = htmlspecialchars($invoiceData['name'] ?? '');
            $studentClass = htmlspecialchars($invoiceData['class'] ?? '');
            $virtualAccount = htmlspecialchars($invoiceData['virtual_account'] ?? '');
            $confirmationDate = '';
             if (isset($invoiceData['confirmation_date'])) {
                 try {
                     $dt = new DateTime($invoiceData['confirmation_date']);
                     $confirmationDate = $dt->format('d F Y H:i:s');
                 } catch (Exception $e) {
                      $confirmationDate = htmlspecialchars($invoiceData['confirmation_date']) . ' (Invalid Format)';
                      error_log("Invalid date format in confirmation_date for PDF: " . $invoiceData['confirmation_date']);
                 }
             }
            $totalPayment = isset($invoiceData['total_payment']) ? FormatHelper::formatRupiah((float)$invoiceData['total_payment']) : '';
            $items = $invoiceData['items'] ?? [];
            $notes = isset($invoiceData['notes']) ? nl2br(htmlspecialchars($invoiceData['notes'])) : '';
            $displayCode = htmlspecialchars($cyper);

            $html = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Invoice {$studentName}</title>
                <style>
                    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #333; }
                    .container { padding: 10px; }
                    .header { border-bottom: 2px solid #6b7280; margin-bottom: 24px; padding-bottom: 12px; }
                    .header-flex { display: flex; justify-content: space-between; align-items: flex-start; } /* Basic flex sim */
                    .invoice-title h1 { font-size: 1.5rem; font-weight: bold; margin: 0; }
                    .invoice-title p { font-size: 0.75rem; color: #3b82f6; font-style: italic; margin: 4px 0 0 0; }
                    .payment-date { text-align: right; font-weight: 600; color: #9ca3af; }
                    .payment-date p.label { font-size: 0.75rem; margin: 0; }
                    .payment-date p.date { font-size: 0.875rem; margin: 4px 0 0 0; color: #4b5563; }
                    .student-details { margin-bottom: 16px; font-size: 0.875rem; }
                    .student-details table td { padding: 2px 8px 2px 0; vertical-align: top; }
                    .student-details table td:first-child { font-weight: normal; } /* Adjusted from original bold */
                    .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.875rem; }
                    .items-table thead { background-color: #f9fafb; }
                    .items-table th { text-transform: uppercase; font-size: 0.75rem; color: #374151; border-bottom: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; }
                    .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
                    .items-table td { padding: 10px 12px; vertical-align: top; }
                    .items-table th.text-right, .items-table td.text-right { text-align: right; }
                    .items-table th.text-center, .items-table td.text-center { text-align: center; }
                    .items-table tfoot { background-color: #f9fafb; font-weight: bold; }
                    .items-table tfoot th { padding: 10px 12px; border-top: 1px solid #e5e7eb; color: #374151; }
                    .items-table tfoot th.total-label { text-transform: uppercase; font-size: 0.75rem; text-align: right; }
                    .items-table tfoot th.total-amount { font-size: 1rem; text-align: right; }
                    .notes { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 9pt; color: #555; }
                    .footer { text-align: center; margin-top: 30px; font-size: 8pt; color: #888; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <table width="100%" style="border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: top;">
                                    <div class="invoice-title">
                                        <h1>Invoice Pembayaran SPP</h1>
                                        <p>#{$displayCode}</p>
                                    </div>
                                </td>
                                <td style="vertical-align: top; text-align: right;">
                                    <div class="payment-date">
                                        <p class="label">Tanggal Pembayaran Masuk Sistem</p>
                                        <p class="date">{$confirmationDate}</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="student-details">
                        <table>
                            <tbody>
                                <tr><td>Nama</td><td>: {$studentName}</td></tr>
                                <tr><td>Kelas</td><td>: {$studentClass}</td></tr>
                                <tr><td>Virtual Account</td><td>: {$virtualAccount}</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col" class="text-center">Periode</th>
                                <th scope="col" class="text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
            HTML;

            if (!empty($items)) {
                foreach ($items as $item) {
                    $itemNameRaw = htmlspecialchars($item['item_name'] ?? '');
                    $itemDisplayName = $itemNameRaw;
                     if ($itemNameRaw == "monthly_fee") {
                         $itemDisplayName = "Tagihan Bulanan";
                     } elseif ($itemNameRaw == "late_fee") {
                         $itemDisplayName = "Biaya Keterlambatan";
                     }
                    $itemAmount = isset($item['amount']) ? FormatHelper::formatRupiah((float)$item['amount']) : '';
                    $billingMonth = htmlspecialchars($item['billing_month'] ?? '');
                    $html .= <<<HTML
                            <tr>
                                <td scope="row" style="font-weight: 500; color: #111827; white-space: nowrap;">{$itemDisplayName}</td>
                                <td class="text-center">{$billingMonth}</td>
                                <td class="text-right">{$itemAmount}</td>
                            </tr>
                    HTML;
                }
            } else {
                $html .= "<tr><td colspan='3' style='text-align: center; padding: 20px;'>No itemized details available.</td></tr>";
            }

            $html .= <<<HTML
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="col" colspan="2" class="total-label">Total Pembayaran</th>
                                <th scope="col" class="total-amount text-right">{$totalPayment}</th>
                            </tr>
                        </tfoot>
                    </table>
            HTML;

            $mpdf->WriteHTML($html);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentName);
            $fileName = "Invoice_" . $safeName . "_" . date('Ymd') . ".pdf";

            $mpdf->Output($fileName, Destination::DOWNLOAD);

            exit;

        } catch (\Mpdf\MpdfException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}