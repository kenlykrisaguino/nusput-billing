<?php

namespace app\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use App\Midtrans\Midtrans;
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
    private Midtrans $midtrans;

    public function __construct($database, Midtrans $midtrans)
    {
        $this->db = $database;
        $this->midtrans = $midtrans;
    }

    public function getPayments()
    {
        $query = "SELECT
                    p.id,
                    u.id AS user_id,
                    u.nama AS payment,
                    p.jumlah_bayar as trx_amount,
                    p.tanggal_pembayaran as trx_timestamp,
                    j.nama as jenjang,
                    t.nama as tingkat,
                    k.nama as kelas,
                    u.va as virtual_account,
                    b.id AS id_relasi
                FROM
                    spp_pembayaran_tagihan b LEFT JOIN
                    spp_pembayaran AS p ON b.pembayaran_id = p.id LEFT JOIN
                    siswa AS u ON p.siswa_id = u.id LEFT JOIN
                    jenjang j ON j.id = u.jenjang_id LEFT JOIN
                    tingkat t ON t.id = u.tingkat_id LEFT JOIN
                    kelas k ON k.id = u.kelas_id
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
                'max' => "$academicYear[1]-06-30",
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

    protected function getPaymentFormat()
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NIS', 'Virtual Account', 'Trx Amount'];

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
        exit();
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

        // ! Proses File XLSX
        try {
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

                if ($nis === '') {
                    $rowErrors[] = 'NIS is required.';
                }
                if ($va === '') {
                    $rowErrors[] = 'Virtual Account is required.';
                }
                if ($trxAmount === '') {
                    $rowErrors[] = 'Transaction Amount is required.';
                }

                if (!empty($rowErrors)) {
                    $errorRowsData[] = [
                        'original_data' => $originalRowValues,
                        'errors' => implode('; ', $rowErrors),
                    ];
                    continue;
                }

                $importedRowCount++;
                if (isset($validPaymentData[$va])) {
                    $d = $validPaymentData[$va];
                    $d['trxAmount'] += $trxAmount;
                    $d['notes'][] = $notes;
                } else {
                    $vaList[] = $va;

                    $validPaymentData[$va] = [
                        'nis' => $nis,
                        'va' => $va,
                        'trxAmount' => $trxAmount,
                        'notes' => [$notes],
                    ];
                }
            }
        } catch (\Exception $e) {
            error_log('Error processing XLSX file or class list: ' . $e->getMessage());
            return ApiResponse::error('Error reading, processing the XLSX file, or loading class data.', 500);
        }

        // Get Bill lists
        $vaString = implode(',', $vaList);
        $billQuery = "SELECT
                        s.va as virtual_account, (b.total_nominal + b.denda) AS trx_amount
                      FROM
                        spp_tagihan b JOIN siswa s ON b.siswa_id = s.id
                      WHERE
                        b.status = 'belum_lunas' AND
                        s.va IN ($vaString)";

        $billStmt = $this->db->query($billQuery);
        $billAmountResult = $this->db->fetchAll($billStmt);
        $billAmounts = [];
        $paymentData = [];
        foreach ($billAmountResult as $amount) {
            $billAmounts[$amount['virtual_account']] = $amount['trx_amount'];
        }

        foreach ($validPaymentData as $va => $detail) {
            if ((float) $billAmounts[$va] == (float) $detail['trxAmount']) {
                $paymentData['va'][] = $va;
                $paymentData['detail'][] = $detail;
            } else {
                $requiredAmount = FormatHelper::formatRupiah((float) $billAmounts[$va]);
                $inputtedAmount = FormatHelper::formatRupiah((float) $detail['trxAmount']);
                $errorRowsData[] = [
                    'original_data' => [$detail['nis'], $detail['va'], $detail['trxAmount'], $detail['notes']],
                    'errors' => "Invalid Value! needing $requiredAmount, but only inputed amount $inputtedAmount",
                ];
            }
        }

        $vaString = implode(',', $paymentData['va']);
        $billDetailQuery = "SELECT
                                b.id, b.siswa_id as user_id, u.va as virtual_account,
                                u.no_hp_ortu as parent_phone, b.midtrans_trx_id
                            FROM
                                spp_tagihan b INNER JOIN
                                siswa u ON u.id = b.siswa_id
                            WHERE
                                u.va IN ($vaString)";
        $billDetailStmt = $this->db->query($billDetailQuery);

        $detailData = [];
        $billId = [];
        $waMsg = [];

        try {
            $this->db->beginTransaction();

            foreach ($this->db->fetchAll($billDetailStmt) as $r) {
                $va = $r['virtual_account'];
                $billId[] = $r['id'];


                $bill_id = ($paymentData[$va]['bill_id'] ?? 0) > $r['id'] ? $paymentData[$va]['bill_id'] : $r['id'];
                $bill = $this->db->find('spp_tagihan', ['id' => $bill_id]);

                $pembayaran = $this->db->insert('spp_pembayaran', [
                    'siswa_id' => $bill['siswa_id'],
                    'tanggal_pembayaran' => Call::timestamp(),
                    'jumlah_bayar' => $bill['total_nominal'] + $bill['denda'],
                ]);
                $this->db->update('spp_tagihan_detail', ['lunas' => 1, 'pembayaran_id' => $pembayaran], ['tagihan_id' => $r['id'], 'lunas' => 0]);
                $relasi_tagihan = $this->db->insert('spp_pembayaran_tagihan', [
                    'pembayaran_id' => $pembayaran,
                    'tagihan_id' => $bill_id,
                    'jumlah' => $bill['total_nominal'] + $bill['denda'],
                ]);
                $this->db->update(
                    'spp_tagihan',
                    [
                        'total_nominal' => 0,
                        'count_denda' => 0,
                        'denda' => 0,
                        'status' => 'lunas',
                    ],
                    ['id' => $bill_id],
                );

                $this->midtrans->cancelTransaction($r['midtrans_trx_id']);

                $url = $_SERVER['HTTP_HOST'];
                $encrypted = $this->generateInvoiceURL($r['user_id'], $relasi_tagihan);

                $waMsg[$va] = [
                    'target' => $r['parent_phone'],
                    'message' => "Pembayaran SPP telah masuk ke dalam sistem. Untuk mendapatkan detail resi pembayaran, bisa menggunakan link berikut:\n\nhttp://$url/invoice/$encrypted",
                    'delay' => '1',
                ];
            }

            $paymentFinal = [];
            foreach ($paymentData as $data) {
                $paymentFinal[] = $data;
            }

            $queueMsg = [];
            foreach ($waMsg as $msg) {
                $queueMsg[] = $msg;
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
                header('Content-Disposition: attachment; filename="import_payments_errors.xlsx"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
            }
            $messages = json_encode($queueMsg);
            Fonnte::sendMessage(['data' => $messages]);
            $this->db->commit();

            return ApiResponse::success(null, 'Upload Payments successful');
        } catch (Exception $e) {
            return ApiResponse::error('Failed to save payments to database: ' . $e->getMessage());
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

        if (!$string) {
            return [
                'status' => false,
                'error' => 'Failed to Decrypt Details',
            ];
        }

        $encrypted = explode('|-|', $string);
        if (count($encrypted) != 2) {
            return [
                'status' => false,
                'error' => 'Invalid Details',
            ];
        }
        [$siswa, $bill] = $encrypted;

        $paymentRelation = $this->db->find('spp_pembayaran_tagihan', ['id' => $bill]);
        $tagihan = $this->db->find('spp_tagihan', [
            'id' => $paymentRelation['tagihan_id'],
            'siswa_id' => $siswa
        ]);
        if(empty($tagihan)){
            return [
                'status' => false,
                'meta' => [
                    'nama' => '',
                    'kelas' => '',
                    'va' => '',
                    'date' => '',
                    'total' => ''
                ],
                'details' => []
            ];
        }
        $detailTagihan = $this->db->findAll('spp_tagihan_detail', [
            'tagihan_id' => $tagihan['id'],
            'pembayaran_id' => $paymentRelation['pembayaran_id']
        ]);

        $pembayaran = $this->db->find('spp_pembayaran', ['id' => $paymentRelation['pembayaran_id']]);
        $siswa = $this->db->find('siswa', ['id' => $siswa]);

        $jenjang = $this->db->find('jenjang', ['id' => $siswa['jenjang_id']]);
        $tingkat = $this->db->find('tingkat', ['id' => $siswa['tingkat_id']]);
        $kelas = $this->db->find('kelas', ['id' => $siswa['kelas_id']]);
        return [
            'status' => true,
            'meta' => [
                'nama' => $siswa['nama'],
                'kelas' => $kelas ? "$jenjang[nama] $tingkat[nama] {$kelas['nama']}" : "$jenjang[nama] $tingkat[nama]",
                'va' => $siswa['va'],
                'date' => $pembayaran['tanggal_pembayaran'],
                'total' => $pembayaran['jumlah_bayar']
            ],
            'details' => $detailTagihan,
        ];
    }

    public function getPublicInvoice($segments)
    {
        array_shift($segments);
        $cyper = implode('/', $segments);
        $data = $this->decryptInvoiceCode($cyper);
        return $data;
    }

    public function exportPublicInvoice($segments)
    {
        $decryptionResult = $this->decryptInvoiceCode($segments[0]);
 
        if (!$decryptionResult['status']) {
            return ApiResponse::error($decryptionResult['error'] ?? 'Gagal memuat data invoice.', 404);
        }

        $invoiceMeta = $decryptionResult['meta'];
        $invoiceItems = $decryptionResult['details'];

       try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'tempDir' => sys_get_temp_dir() . '/mpdf',
            ]);

            // Gunakan data dari $invoiceMeta
            $studentName = htmlspecialchars($invoiceMeta['nama'] ?? '');
            $studentClass = htmlspecialchars($invoiceMeta['kelas'] ?? '');
            $virtualAccount = htmlspecialchars($invoiceMeta['va'] ?? '');
            $totalPayment = isset($invoiceMeta['total']) ? FormatHelper::formatRupiah((float) $invoiceMeta['total']) : 'Rp 0';
            $displayCode = htmlspecialchars($segments[0]);
            
            // Format tanggal pembayaran
            $confirmationDate = 'N/A';
            if (!empty($invoiceMeta['date'])) {
                try {
                    $dt = new DateTime($invoiceMeta['date']);
                    $confirmationDate = $dt->format('d F Y H:i:s');
                } catch (Exception $e) {
                    $confirmationDate = htmlspecialchars($invoiceMeta['date']) . ' (Format Salah)';
                    error_log('Invalid date format in confirmation_date for PDF: ' . $invoiceMeta['date']);
                }
            }
            
            // Variabel 'notes' tidak ada di hasil dekripsi, jadi kita kosongkan saja.
            $notes = '';

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
                    .invoice-title h1 { font-size: 1.5rem; font-weight: bold; margin: 0; }
                    .invoice-title p { font-size: 0.75rem; color: #3b82f6; font-style: italic; margin: 4px 0 0 0; }
                    .payment-date { text-align: right; font-weight: 600; color: #9ca3af; }
                    .payment-date p.label { font-size: 0.75rem; margin: 0; }
                    .payment-date p.date { font-size: 0.875rem; margin: 4px 0 0 0; color: #4b5563; }
                    .student-details { margin-bottom: 16px; font-size: 0.875rem; }
                    .student-details table td { padding: 2px 8px 2px 0; vertical-align: top; }
                    .student-details table td:first-child { font-weight: normal; }
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
                                <th scope="col">Nama Tagihan</th>
                                <th scope="col" class="text-center">Periode</th>
                                <th scope="col" class="text-right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
            HTML;

            if (!empty($invoiceItems)) {
                foreach ($invoiceItems as $item) {
                    // Gunakan nama kolom aktual dari tabel `spp_tagihan_detail`
                    // Asumsi: 'nama_pembayaran', 'periode', 'jumlah'
                    $itemName = htmlspecialchars($item['nama_pembayaran'] ?? 'Item tidak diketahui');
                    $billingPeriod = htmlspecialchars($item['periode'] ?? '-'); // Ganti 'periode' jika nama kolomnya berbeda
                    $itemAmount = isset($item['jumlah']) ? FormatHelper::formatRupiah((float) $item['jumlah']) : 'Rp 0';

                    $html .= <<<HTML
                            <tr>
                                <td scope="row" style="font-weight: 500; color: #111827;">{$itemName}</td>
                                <td class="text-center">{$billingPeriod}</td>
                                <td class="text-right">{$itemAmount}</td>
                            </tr>
                    HTML;
                }
            } else {
                $html .= "<tr><td colspan='3' style='text-align: center; padding: 20px;'>Tidak ada rincian item.</td></tr>";
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
                </div>
            </body>
            </html>
            HTML;

            $mpdf->WriteHTML($html);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentName);
            $fileName = 'Invoice_' . $safeName . '_' . date('Ymd') . '.pdf';

            $mpdf->Output($fileName, Destination::DOWNLOAD);

            exit();
        } catch (\Mpdf\MpdfException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function midtransCallback()
    {
        $json_callback = file_get_contents('php://input');
        $data = json_decode($json_callback, true);

        try {
            $notif = $this->midtrans->getNotificationHandler();

            // Ambil data penting dari notifikasi
            $transactionStatus = $notif->transaction_status;
            $orderId = $notif->order_id;
            $grossAmount = $notif->gross_amount;
            $paymentType = $notif->payment_type;
            $fraudStatus = $notif->fraud_status;

            $bill = $this->db->find('spp_tagihan', ['midtrans_trx_id' => $orderId]);

            if (!$bill) {
                error_log('Midtrans callback for unknown order_id: ' . $orderId);
                return ApiResponse::error(['message' => 'Order ID not found in database.']); // Kembalikan 200 OK
            }

            if ($transactionStatus == 'settlement') {
                $this->db->update('spp_tagihan', [
                    'status' => 'lunas', 
                    'denda' => 0, 
                    'count_denda' => 0, 
                    'total_nominal' => 0
                ], ['id' => $bill['id']]);
                $payment = $this->db->insert('spp_pembayaran', [
                    'siswa_id' => $bill['siswa_id'],
                    'tanggal_pembayaran' => Call::timestamp(),
                    'jumlah_bayar' => $grossAmount,
                ]);
                $this->db->update('spp_tagihan_detail', ['lunas' => 1, 'pembayaran_id' => $payment], ['tagihan_id' => $bill['id'], 'lunas' => 0]);
                $relasiTagihan = $this->db->insert('spp_pembayaran_tagihan', [
                    'pembayaran_id' => $payment,
                    'tagihan_id' => $bill['id'],
                    'jumlah' => $grossAmount,
                ]);
                error_log('Transaction order_id: ' . $orderId . ' successfully settled using ' . $paymentType);
                $url = $_SERVER['HTTP_HOST'];
                $siswa = $this->db->find('siswa', ['id' => $bill['siswa_id']]);

                $encrypted = $this->generateInvoiceURL($siswa['id'], $relasiTagihan);

                $waMsg[] = [
                    'target' => $siswa['no_hp_ortu'],
                    'message' => "Pembayaran SPP telah masuk ke dalam sistem. Untuk mendapatkan detail resi pembayaran, bisa menggunakan link berikut:\n\n
                    http://$url/invoice/$encrypted",
                    'delay' => '1',
                ];
                $messages = json_encode($waMsg);
                Fonnte::sendMessage(['data' => $messages]);
            }

            return ApiResponse::success('Callback processed successfully');
        } catch (\Exception $e) {
            error_log('Error processing Midtrans callback for order_id ' . ($data['order_id'] ?? 'N/A') . ': ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile());
            return ApiResponse::error('Callback received, but an error occurred during processing. Please check server logs for order_id: ' . ($data['order_id'] ?? 'N/A'));
        }
    }
}
