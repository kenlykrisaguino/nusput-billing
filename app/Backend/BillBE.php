<?php

namespace app\Backend;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';
use App\Backend\JournalBE;
use App\Helpers\ApiResponse as Response;
use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use App\Midtrans\Midtrans;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BillBE
{
    private $db;
    private $journal;
    private Midtrans $midtrans;

    public function __construct($database, $midtrans)
    {
        $this->db = $database;
        $this->journal = new JournalBE($database);
        $this->midtrans = new Midtrans($midtrans);
    }

    public function getBills()
    {
        $params = ['s.deleted_at IS NULL'];
        $filterData = [];
        if (!empty($_GET['filter-jenjang'])) {
            $params[] = 'j.id = ?';
            $filterData[] = $_GET['filter-jenjang'];
        }
        if (!empty($_GET['filter-tingkat'])) {
            $params[] = 't.id = ?';
            $filterData[] = $_GET['filter-tingkat'];
        }
        if (!empty($_GET['filter-kelas'])) {
            $params[] = 'k.id = ?';
            $filterData[] = $_GET['filter-kelas'];
        }
        $stmt =
            "SELECT
                    s.nama, j.nama AS jenjang, t.nama AS tingkat,
                    k.nama AS kelas, tg.*, s.spp, s.va as virtual_account, s.va_midtrans
                 FROM
                    siswa s LEFT JOIN
                    jenjang j on s.jenjang_id = j.id LEFT JOIN
                    tingkat t on s.tingkat_id = t.id LEFT JOIN
                    kelas k ON s.kelas_id = k.id LEFT JOIN
                    spp_tagihan tg ON s.id = tg.siswa_id
                 WHERE " . implode(' AND ', $params);
        $data = $this->db->fetchAll($this->db->query($stmt, $filterData));
        $sum = [
            'monthly' => 0,
            'late' => 0,
            'total' => 0
        ];

        foreach($data as $fee){
            $sum['monthly'] += $fee['total_nominal'];
            $sum['late'] += $fee['denda'];
            $sum['total'] += $fee['total_nominal'] + $fee['denda'];
        }

        return [
            'data'  => $data,
            'year'  => $_GET['filter-tahun'] ?? Call::year(),
            'total' => $sum
        ];
    }

    public function getMonthBill($month, $year, $id)
    {
        $bill = $this->db->find('spp_tagihan', ['id' => $id]);
        if (!isset($bill)) {
            return ['sum' => 0, 'detail' => []];
        }

        $stmt = 'SELECT SUM(nominal) as sum FROM spp_tagihan_detail WHERE tagihan_id = ? AND bulan = ? AND tahun = ?';
        $sum = $this->db->fetchAssoc($this->db->query($stmt, [$bill['id'], $month, $year]))['sum'];

        $stmt = 'SELECT * FROM spp_tagihan_detail WHERE tagihan_id = ? AND bulan = ? AND tahun = ?';
        $detail = $this->db->fetchAssoc($this->db->query($stmt, [$bill['id'], $month, $year]));

        return [
            'sum' => $sum,
            'detail' => $detail,
        ];
    }

    public function createBills()
    {
        try {
            $this->db->beginTransaction();

            // 1. Tahun dibuat tagihannya (Kalau belum ada tagihan sebelumnya, pakai tahun sekarang)
            $stmt = 'SELECT MAX(tahun) AS tahun FROM spp_tagihan';
            $query = $this->db->query($stmt);
            $year = $this->db->fetchAssoc($query)['tahun'] ?? Call::year();

            // 2. Check kalau sudah di bulan 12
            $stmt = 'SELECT MAX(bulan) AS bulan FROM spp_tagihan WHERE tahun=?';
            $query = $this->db->query($stmt, [$year]);
            $bulanResult = $this->db->fetchAssoc($query);

            if (isset($bulanResult['bulan'])) {
                if ($bulanResult['bulan'] != 13) {
                    return Response::error('Harap selesaikan cek tagihan sampai bulan desember untuk membuat tagihan tahunan');
                }
            }

            $bulan = $bulanResult['bulan'];

            // 3. Dapatkan seluruh data siswa aktif
            $stmt = 'SELECT id, spp FROM siswa WHERE deleted_at IS NULL';
            $students = $this->db->fetchAll($this->db->query($stmt));

            // TODO: Get Student yang sudah ada
            $stmt = 'SELECT id, siswa_id FROM spp_tagihan';
            $avlbBills = $this->db->fetchAll($this->db->query($stmt));
            $ids = array_combine(array_column($avlbBills, 'id'), array_column($avlbBills, 'siswa_id'));

            foreach ($students as $student) {
                $trx_id = Call::uuidv4();

                $count = 0;

                if (in_array($student['id'], $ids)) {
                    // TODO: Update yang ada
                    $billId = array_search($student['id'], $ids);
                    $bill = $this->db->find('spp_tagihan', ['id' => $billId]);

                    $this->db->insert('spp_tagihan_detail', [
                        'tagihan_id' => $billId,
                        'jenis' => 'spp',
                        'nominal' => $student['spp'],
                        'bulan' => 1,
                        'tahun' => $bill['tahun'] + 1,
                    ]);

                    $count = $this->countSPPRenewal($billId, $bill['bulan'], $bill['tahun']);
                    $this->midtrans->expireTransaction($bill['midtrans_trx_id']);

                    $this->db->update(
                        'spp_tagihan',
                        [
                            'tahun' => $bill['tahun'] + 1,
                            'bulan' => 1,
                            'jatuh_tempo' => $bill['tahun'] + 1 . '-01-10',
                            'total_nominal' => $count,
                            'count_denda' => $bill['count_denda'] + 1,
                            'status' => 'belum_lunas',
                            'midtrans_trx_id' => $trx_id,
                        ],
                        ['id' => $billId],
                    );
                } else {
                    // TODO: Masuk ke create
                    $billId = $this->db->insert('spp_tagihan', [
                        'siswa_id' => $student['id'],
                        'bulan' => 1,
                        'tahun' => $year,
                        'jatuh_tempo' => "$year-01-10",
                        'total_nominal' => $student['spp'],
                        'count_denda' => 0,
                        'denda' => 0,
                        'status' => 'belum_lunas',
                        'midtrans_trx_id' => $trx_id,
                    ]);

                    $this->db->insert('spp_tagihan_detail', [
                        'tagihan_id' => $billId,
                        'jenis' => 'spp',
                        'nominal' => $student['spp'],
                        'bulan' => 1,
                        'tahun' => $year,
                    ]);

                    $count = $student['spp'];
                }

                $st = $this->db->find('siswa', ['id' => $student['id']]);
                $bd = $this->db->findAll('spp_tagihan_detail', ['id' => $student['id'], 'lunas' => 0]);

                $items = [];

                foreach ($bd as $d) {
                    $items[] = [
                        'id' => $d['id'],
                        'price' => $d['nominal'],
                        'quantity' => 1,
                        'name' => $d['jenis'] . ' ' . $d['bulan'] . ' ' . $d['tahun'],
                    ];
                }

                $mdResult = $this->midtrans->charge([
                    'payment_type' => 'bank_transfer',
                    'transaction_details' => [
                        'gross_amount' => $count,
                        'order_id' => $trx_id,
                    ],
                    'customer_details' => [
                        'email' => '',
                        'first_name' => $st['nama'],
                        'last_name' => '',
                        'phone' => $st['no_hp_ortu'],
                    ],
                    'item_details' => $items,
                    'bank_transfer' => [
                        'bank' => 'bni',
                        'va_number' => $st['va'],
                    ],
                ]);

                $this->db->update('siswa', ['va_midtrans' => $mdResult->va_numbers[0]->va_number], ['id' => $student['id']]);
            }
            $this->sendToAKTSystem(1, $year);

            $this->db->commit();

            return Response::success(null, "Berhasil membuat tagihan tahun $year");
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to create bills: ' . $e->getMessage(), 500);
        }
    }

    public function checkBills()
    {
        // 1. Get data bulan dan tahun terbaru
        $stmt = "SELECT tahun, MAX(bulan) AS bulan
                    FROM spp_tagihan
                    WHERE is_active = 1
                    GROUP BY tahun
                    ORDER BY tahun DESC
                    LIMIT 1";
        $latest = $this->db->fetchAssoc($this->db->query($stmt));

        if ($latest['bulan'] > 12) {
            return ApiResponse::error('Sudah melakukan cek tagihan sampai bulan desember, harap lakukan buat tagihan untuk melanjutkan');
        }

        // 2. Get tagihan yang pakai bulan dan tahun ini
        $bills = $this->db->findAll('spp_tagihan', ['tahun' => $latest['tahun'], 'bulan' => $latest['bulan'], 'is_active' => 1]);

        // 3. Logika penambahan SPP
        try {
            $this->db->beginTransaction();

            foreach ($bills as $bill) {
                // TODO: Get Data Siswa
                $student = $this->db->find('siswa', ['id' => $bill['siswa_id']]);

                // TODO: Cek Status
                $status = $bill['status'];

                $trx_id = Call::uuidv4();
                $countTotal = 0;

                // Cek Biaya Tambahan di bulan ini
                $billDetails = $this->db->findAll('spp_tagihan_detail',[
                    'tagihan_id' => $bill['id'],
                    'bulan' => $bill['bulan'],
                    'tahun' => $bill['tahun'],
                    'lunas' => false
                ]);

                $totalAdditional = 0;
                foreach($billDetails as $detail) {
                    if(!in_array($detail['jenis'], ['spp', 'late'])) {
                        $totalAdditional += $detail['nominal'];
                    }
                }

                if ($status == 'lunas') {
                    $this->db->update(
                        'spp_tagihan',
                        [
                            'siswa_id' => $student['id'],
                            'bulan' => $latest['bulan'] + 1,
                            'tahun' => $bill['tahun'],
                            'jatuh_tempo' => $bill['tahun'] . '-01-10',
                            'total_nominal' => $student['spp'] + $totalAdditional,
                            'count_denda' => 0,
                            'denda' => 0,
                            'status' => 'belum_lunas',
                            'midtrans_trx_id' => $trx_id,
                        ],
                        ['id' => $bill['id']],
                    );

                    $countTotal = $student['spp'];

                    if ($latest['bulan'] < 12) {
                        $this->db->insert('spp_tagihan_detail', [
                            'tagihan_id' => $bill['id'],
                            'jenis' => 'spp',
                            'nominal' => $student['spp'],
                            'bulan' => $latest['bulan'] + 1,
                            'tahun' => $latest['tahun'],
                        ]);
                    }
                } else {
                    // $this->midtrans->expireTransaction($bill['midtrans_trx_id']);
                    if ($latest['bulan'] < 12) {
                        $this->db->insert('spp_tagihan_detail', [
                            'tagihan_id' => $bill['id'],
                            'jenis' => 'spp',
                            'nominal' => $student['spp'],
                            'bulan' => $latest['bulan'] + 1,
                            'tahun' => $latest['tahun'],
                        ]);
                    }

                    $this->db->insert('spp_tagihan_detail', [
                        'tagihan_id' => $bill['id'],
                        'jenis' => 'late',
                        'nominal' => Call::denda(),
                        'bulan' => $latest['bulan'],
                        'tahun' => $latest['tahun'],
                    ]);

                    $count = $this->countSPPRenewal($bill['id'], (int) $bill['bulan'] + 1, $bill['tahun']);

                    $this->db->update(
                        'spp_tagihan',
                        [
                            'siswa_id' => $student['id'],
                            'bulan' => $latest['bulan'] + 1,
                            'tahun' => $bill['tahun'],
                            'jatuh_tempo' => $bill['tahun'] . '-'.($bill['bulan'] + 1).'-10',
                            'total_nominal' => $count,
                            'count_denda' => $bill['count_denda'] + 1,
                            'denda' => Call::denda() * ($bill['count_denda'] + 1) + $bill['denda'],
                            'status' => 'belum_lunas',
                            'midtrans_trx_id' => $trx_id,
                        ],
                        ['id' => $bill['id']],
                    );

                    $countTotal += $count;
                    $countTotal += Call::denda() * ($bill['count_denda'] + 1) + $bill['denda'];

                    // TODO: Update late bills yang terlalui
                    for ($i = $bill['count_denda']; $i > 0; $i--) {
                        $d = $this->db->find('spp_tagihan_detail', [
                            'tagihan_id' => $bill['id'],
                            'bulan' => $latest['bulan'] - $i,
                            'tahun' => $latest['tahun'],
                            'jenis' => 'late',
                        ]);
                        // ! LOGIC FAILED
                        $data = [
                            'tagihan_id' => $d['tagihan_id'],
                            'jenis' => $d['jenis'],
                            'nominal' => $d['nominal'] + Call::denda(),
                            'keterangan' => $d['keterangan'],
                            'bulan' => $d['bulan'],
                            'tahun' => $d['tahun'],
                        ];

                        $this->db->update('spp_tagihan_detail', $data, [
                            'tagihan_id' => $bill['id'],
                            'bulan' => $latest['bulan'] - $i,
                            'tahun' => $latest['tahun'],
                            'jenis' => 'late',
                        ]);

                        $countTotal += $d['nominal'] + Call::denda();
                    }
                }

                $stmt = "SELECT
                            bd.id, bd.tagihan_id, b.siswa_id,
                            bd.jenis, bd.nominal, bd.bulan, bd.tahun
                         FROM
                            spp_tagihan b JOIN
                            spp_tagihan_detail bd ON b.id = bd.tagihan_id
                         WHERE
                            b.siswa_id = ? AND bd.lunas = ? AND bd.bulan <= ? AND bd.tahun <=  ?";


                $bd = $this->db->fetchAll($this->db->query($stmt, [
                    $student['id'],
                    0,
                    $latest['bulan'] + 1,
                    $latest['tahun']
                ]));

                $items = [];
                $countBelumLunas = 0;

                foreach ($bd as $d) {
                    $items[] = [
                        'id' => $d['id'],
                        'price' => $d['nominal'],
                        'quantity' => 1,
                        'name' => $d['jenis'] . ' ' . $d['bulan'] . ' ' . $d['tahun'],
                    ];
                    if ($status != 'lunas') {
                        $countBelumLunas += $d['nominal'];
                    }
                }
                if ($status != 'lunas') {
                    $countTotal = $countBelumLunas;
                }

                $mdPayload = [
                    'payment_type' => 'bank_transfer',
                    'transaction_details' => [
                        'gross_amount' => $countTotal,
                        'order_id' => $trx_id,
                    ],
                    'customer_details' => [
                        'email' => '',
                        'first_name' => $student['nama'],
                        'last_name' => '',
                        'phone' => $student['no_hp_ortu'],
                    ],
                    'item_details' => $items,
                    'bank_transfer' => [
                        'bank' => 'bni',
                        'va_number' => $student['va'],
                    ],
                ];

                $mdResult = $this->midtrans->charge($mdPayload);
                $this->db->update('siswa', ['va_midtrans' => $mdResult->va_numbers[0]->va_number], ['id' => $student['id']]);
            }
            $this->sendToAKTSystem((int) $latest['bulan'] + 1, $latest['tahun']);

            $this->db->commit();
            return ApiResponse::success(null, 'Berhasil mengupdate Tagihan');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e);
        }
    }

    public function createSingularBill($studentId, $status = "CREATE")
    {
        try {
            $this->db->beginTransaction();

            // 1. Tahun dibuat tagihan
            $stmt = 'SELECT MAX(tahun) AS tahun FROM spp_tagihan';
            $query = $this->db->query($stmt);
            $year = $this->db->fetchAssoc($query)['tahun'];

            if (!isset($year)) {
                return null;
            }

            // 2. Check bulan
            $stmt = 'SELECT MAX(bulan) AS bulan FROM spp_tagihan WHERE tahun=?';
            $query = $this->db->query($stmt, [$year]);
            $bulanResult = $this->db->fetchAssoc($query);

            $st = $this->db->find('siswa', ['id' => $studentId]);

            $bulan = $bulanResult['bulan'];

            $trx_id = Call::uuidv4();

            $count = 0;
            $billId = 0;

            if($status == 'CREATE'){
                $billId = $this->db->insert('spp_tagihan', [
                    'siswa_id' => $studentId,
                    'bulan' => $bulan,
                    'tahun' => $year,
                    'jatuh_tempo' => "$year-$bulan-10",
                    'total_nominal' => $st['spp'],
                    'count_denda' => 0,
                    'denda' => 0,
                    'status' => 'belum_lunas',
                    'midtrans_trx_id' => $trx_id,
                ]);
    
                $this->db->insert('spp_tagihan_detail', [
                    'tagihan_id' => $billId,
                    'jenis' => 'spp',
                    'nominal' => $st['spp'],
                    'bulan' => $bulan,
                    'tahun' => $year,
                ]);
            } else {
                $currBill = $this->db->find('spp_tagihan', ['siswa_id' => $st['id']]);
                $monthDetail = $this->db->find('spp_tagihan_detail', [
                    'tagihan_id' => $currBill['id'],
                    'bulan'      => $bulan,
                    'tahun'      => $year,
                    'jenis'      => 'spp'
                ]);

                $tagihan = $currBill['total_nominal'] - $monthDetail['nominal'] + $st['spp'];

                $bill = $this->db->update('spp_tagihan', [
                    'total_nominal' => $tagihan,
                    'midtrans_trx_id' => $trx_id,
                ], ['id' => $currBill['id']]);

                $detail = $this->db->update('spp_tagihan_detail', [
                    'nominal' => $st['spp']
                ], ['tagihan_id' => $currBill['id'], 'jenis' => 'spp', 'bulan' => $bulan]);
                $billId = $currBill['id'];
            }

            $bd = $this->db->findAll('spp_tagihan_detail', ['tagihan_id' => $billId, 'lunas' => 0]);

            $items = [];
            $sum = 0;

            foreach ($bd as $d) {
                $items[] = [
                    'id' => $d['id'],
                    'price' => $d['nominal'],
                    'quantity' => 1,
                    'name' => $d['jenis'] . ' ' . $d['bulan'] . ' ' . $d['tahun'],
                ];
                $sum += $d['nominal'];
            }

            $mdPayload = [
                'payment_type' => 'bank_transfer',
                'transaction_details' => [
                    'gross_amount' => $sum,
                    'order_id' => $trx_id,
                ],
                'customer_details' => [
                    'email' => '',
                    'first_name' => $st['nama'],
                    'last_name' => '',
                    'phone' => $st['no_hp_ortu'],
                ],
                'item_details' => $items,
                'bank_transfer' => [
                    'bank' => 'bni',
                    'va_number' => $st['va'],
                ],
            ];

            $mdResult = $this->midtrans->charge($mdPayload);

            $this->db->update('siswa', ['va_midtrans' => $mdResult->va_numbers[0]->va_number], ['id' => $st['id']]);
        } catch (\Exception $e) {
            $this->db->rollback();
            return Response::error('Failed to create bills: ' . $e->getMessage(), 500);
        }
    }

    protected function sendToAKTSystem($month, $year)
    {
        // Dapetin Tahun Ajaran
        $academicYear = Call::academicYear(ACADEMIC_YEAR_AKT_FORMAT, [
            'year' => $year,
            'month' => $month,
            'day' => 1,
        ]);

        // ! Kirim ke Sistem AKT
        $levels = $this->db->fetchAll($this->db->query('SELECT * FROM jenjang'));
        $base_url = rtrim($_ENV['ACCOUNTING_SYSTEM_URL'], '/');
        $url = "$base_url/page/transaksi/backend/create.php";
        // TODO: Store the data that are being sent
        $journalData = [];

        $smk1 = ['SMK TKJ', 'SMK MM'];

        $import_akt_data = [
            'tahun_ajaran' => $academicYear,
            'sumber_dana' => 'Rutin',
        ];

        foreach ($levels as $level) {
            $journals = $this->journal->getJournals($level['id'], true);
            if (in_array($level['nama'], $smk1)) {
                if (!isset($journalData['SMK1'])) {
                    $journalData['SMK1'] = [
                        'PTUS' => 0.0,
                        'PLUS' => 0.0,
                        'PNBD' => 0.0,
                        'PBDL' => 0.0,
                    ];
                }
                $journalData['SMK1']['PTUS'] += $journals['piutang'];
                $journalData['SMK1']['PLUS'] += $journals['pelunasan'];
                $journalData['SMK1']['PNBD'] += $journals['hutang'];
                $journalData['SMK1']['PBDL'] += $journals['hutang_terbayar'];
            } else {
                $journalData[$level['nama']] = [
                    'PTUS' => $journals['piutang'] ?? 0.0,
                    'PLUS' => $journals['pelunasan'] ?? 0.0,
                    'PNBD' => $journals['hutang'] ?? 0.0,
                    'PBDL' => $journals['hutang_terbayar'] ?? 0.0,
                ];
            }
        }

        $bulan = FormatHelper::formatMonthNameInBahasa($month);
        $bulanSebelum = FormatHelper::formatMonthNameInBahasa($month - 1);
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
                        'bulan' => $code == 'PLUS' ? $bulanSebelum : $bulan,
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
    }

    public function notifyBills()
    {
        $type = $_GET['type'] ?? '';

        $stmt = 'SELECT MAX(bulan) AS bulan, MAX(tahun) AS tahun, MAX(jatuh_tempo) AS jatuh_tempo FROM spp_tagihan';
        $max = $this->db->fetchAssoc($this->db->query($stmt));

        // Dapetin semua tagihan di bulan tertinggi
        $bills = $this->db->findAll('spp_tagihan', [
            'bulan' => $max['bulan'],
            'tahun' => $max['tahun'],
        ]);
        $monthName = FormatHelper::formatMonthNameInBahasa($max['bulan']);

        $msg = [
            'success' => ['Pembayaran SPP Bulan', $monthName],
            'failed' => ['Pembayaran SPP Bulan', $monthName],
        ];

        if ($type == 1) {
            $msg['failed'][] = 'telah dibuka dan akan berakhir di tanggal';
            $msg['failed'][] = $max['jatuh_tempo'] . ', ';
            $msg['success'][] = 'telah dibuka dan akan berakhir di tanggal';
            $msg['success'][] = $max['jatuh_tempo'] . ', ';
        } else {
            $msg['failed'][] = 'belum dibayarkan.';
            $msg['success'][] = 'telah dibayarkan.';
        }

        $msg['failed'][] = "Diharapkan dapat melakukan pembayaran sebagai berikut: \n\n";

        $msgLists = [];
        foreach ($bills as $bill) {
            $siswa = $this->db->find('siswa', ['id' => $bill['siswa_id']]);
            $monthlyFee = FormatHelper::formatRupiah($bill['total_nominal']);
            $denda = FormatHelper::formatRupiah($bill['denda']);
            $sum = FormatHelper::formatRupiah($bill['denda'] + $bill['total_nominal']);
            $status = $bill['status'] === 'lunas';
            $message = implode(' ', $msg[$status ? 'success' : 'failed']);
            if($status){
                $bill = $this->db->find('spp_tagihan', ['siswa_id' => $siswa['id']]);
                $rels = $this->db->find('spp_pembayaran_tagihan', ['tagihan_id' => $bill['id']]); 
                $pymt = $this->db->find('spp_pembayaran', ['id' => $rels['pembayaran_id']]); 
                $url = $_SERVER['HTTP_HOST'];
                $encrypted = $this->generateInvoiceURL($siswa['id'], $rels['id']);
                $message .= "Terima kasih kepada orang tua $siswa[nama] yang telah melakukan pembayaran pada tanggal *$pymt[tanggal_pembayaran]*.";
                $message .= "Untuk bukti pembayaran bisa dilihat di http://$url/invoice/$encrypted";
            } else {
                $message .= "SPP: $monthlyFee\nDenda: $denda\n*Total Pembayaran: $sum*\n\n";
                $message .= "Virtual Account: BNI *$siswa[va]* atas nama *$siswa[nama]*\n";
                $message .= "Alternate VA: BNI *$siswa[va_midtrans]* atas nama *$siswa[nama]*";
            }

            $msgLists[] = [
                'target' => $siswa['no_hp_ortu'],
                'message' => $message,
                'delay' => '1',
            ];
        }
        $messages = json_encode($msgLists);
        $fonnte = Fonnte::sendMessage(['data' => $messages]);

        return ApiResponse::success(null);
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

    public function checkSingularBillStatus($billId, $month, $year)
    {
        $details = $this->db->findAll('spp_tagihan_detail', ['tagihan_id' => $billId, 'bulan' => $month, 'tahun' => $year]);
        $status = 1;
        foreach ($details as $d) {
            if (!$d['lunas']) {
                $status = 0;
            }
        }
        return $status ? ['bg' => 'bg-green-100', 'text' => 'text-green-500'] : ['bg' => 'bg-red-100', 'text' => 'text-red-500'];
    }

    public function countSPPRenewal($billId, $month, $year)
    {
        $conditions = [
            'tagihan_id' => $billId,
            'bulan' => ['<=', $month],
            'tahun' => ['<=', $year],
            'lunas' => 0,
            'jenis' => ['!=', 'late'],
        ];

        $details = $this->db->findAll('spp_tagihan_detail', $conditions);

        $sum = 0;

        foreach ($details as $d) {
            $sum += $d['nominal'];
        }

        return $sum;
    }

    protected function getBillFormat(int $late = 0)
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'VA', 'NIS', 'Nama', 'Jenjang', 'Tingkat', 'Kelas', 'SPP'];

        $feeCategories = ['praktek', 'ekstra', 'daycare'];

        foreach ($feeCategories as $fee) {
            $headers[] = $fee;
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
        $feeCategory = ['praktek', 'ekstra', 'daycare'];
        $dataSiswa = [];

        $students = $this->db->findAll('siswa');
        foreach ($students as $siswa) {
            $bill = $this->db->find('spp_tagihan', [
                'siswa_id' => $siswa['id'],
                'status' => 'belum_lunas',
            ]);
            if (empty($bill)) {
                continue;
            }
            $dataSiswa[$siswa['va']] = $siswa;
            $detail = $this->db->findAll('spp_tagihan_detail', ['lunas' => 0, 'tagihan_id' => $bill['id'] ?? 0]);
            $dataSiswa[$siswa['va']]['detail'] = $detail;
        }

        $stmt = "SELECT MAX(count_denda) as denda FROM spp_tagihan WHERE status = 'belum_lunas'";
        $max = $this->db->fetchAssoc($this->db->query($stmt))['denda'];

        $stmt = "SELECT COUNT(status) as denda FROM spp_tagihan WHERE status = 'belum_lunas'";
        $count = $this->db->fetchAssoc($this->db->query($stmt))['denda'];

        $maxLateCount = (int) $max + ($count > 0 ? 1 : 0);
        $spreadsheet = $this->getBillFormat($maxLateCount);

        $startRow = 2;
        $max_late = 0;
        $data = [];
        $total = 0;
        $id = 0;
        $maxLen = 0;
        foreach ($dataSiswa as $siswa) {
            $additionalFee = [
                'praktek' => 0,
                'ekstra' => 0,
                'daycare' => 0,
            ];
            $fee = [];

            $jenjang = $this->db->find('jenjang', ['id' => $siswa['jenjang_id']]);
            $tingkat = $this->db->find('tingkat', ['id' => $siswa['tingkat_id']]);
            $kelas = $this->db->find('kelas', ['id' => $siswa['kelas_id']]);

            foreach ($siswa['detail'] as $d) {
                if (isset($additionalFee[$d['jenis']])) {
                    $additionalFee[$d['jenis']] += $d['nominal'];
                    if (isset($fee[$d['tahun']][$d['bulan']][$d['jenis']])) {
                        $fee[$d['tahun']][$d['bulan']][$d['jenis']] += $d['nominal'];
                    } else {
                        $fee[$d['tahun']][$d['bulan']][$d['jenis']] = $d['nominal'];
                    }
                } else {
                    if (isset($fee[$d['tahun']][$d['bulan']][$d['jenis']])) {
                        $fee[$d['tahun']][$d['bulan']][$d['jenis']] += $d['nominal'];
                    } else {
                        $fee[$d['tahun']][$d['bulan']][$d['jenis']] = $d['nominal'];
                    }
                }
            }

            $bill = $this->db->find('spp_tagihan', ['siswa_id' => $siswa['id']]);

            $data[$id] = [
                $id + 1, 
                $siswa['va'], 
                $siswa['nis'], 
                $siswa['nama'], 
                $jenjang['nama'], 
                $tingkat['nama'], 
                $kelas['nama'] ?? '', 
                FormatHelper::formatRupiah($siswa['spp']), 
                FormatHelper::formatRupiah($additionalFee['praktek']), 
                FormatHelper::formatRupiah($additionalFee['ekstra']), 
                FormatHelper::formatRupiah($additionalFee['daycare']), 
                $bill['tahun'] . '/' . $bill['bulan'], 
                $bill['count_denda'] + 1
            ];

            $setCount = $bill['count_denda'] + 1;

            for ($i = 0; $i <= $max; $i++) {
                if ($setCount > 0) {
                    if($bill['bulan'] - $i < 0){
                        $periode = '';
                        $amount = '';
                    } else {
                        $periode = $bill['tahun'] . '/' . $bill['bulan'] - $i;
                        $amount = 0;
                    }
                    foreach($fee[$bill['tahun']][$bill['bulan'] - $i] as $a){
                        $amount += $a;
                    }
                    array_push($data[$id], $periode, FormatHelper::formatRupiah($amount));
                } else {
                    array_push($data[$id], '', '');
                }
            }
            array_push($data[$id], FormatHelper::formatRupiah($bill['total_nominal'] + $bill['denda']), '', FormatHelper::formatRupiah($bill['total_nominal'] + $bill['denda']));
            $total += $bill['total_nominal'] + $bill['denda'];
            $len = count($data[$id]);
            $maxLen = $len > $maxLen ? $len : $maxLen;
            $id++;
        }

        $emptyData = [];
        for($lenCount = 0; $lenCount < $maxLen - 2; $lenCount++){
            $emptyData[] = '';
        }
        array_push($data, $emptyData, ['Total Keseluruhan', FormatHelper::formatRupiah($total)]);
        $spreadsheet = $this->getBillFormat($max);
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

    public function getFeeDetails($data)
    {
        $feeDetails = $this->db->findAll('spp_tagihan_detail', [
            'tagihan_id' => $data['billId'],
            'bulan' => (int) $data['month'],
            'tahun' => $data['year'],
        ]);
        $feeRecap = [
            'spp' => 0,
            'denda' => 0,
            'dynamic_fees' => [],
        ];
        $lateCount = 0;
        foreach ($feeDetails as $fee) {
            if ($fee['jenis'] == 'spp') {
                $feeRecap['spp'] = $fee['nominal'];
            } elseif ($fee['jenis'] == 'late') {
                $feeRecap['denda'] = $fee['nominal'];
                $lateCount++;
            } else {
                $feeRecap['dynamic_fees'][] = $fee;
            }
        }

        $siswa = $this->db->fetchAssoc(
            $this->db->query(
                "SELECT s.nama
             FROM spp_tagihan b INNER JOIN siswa s ON s.id = b.siswa_id WHERE b.id = ?",
                [$data['billId']],
            ),
        );
        return ApiResponse::success([
            'fee_details' => $feeRecap,
            'siswa' => $siswa['nama'],
        ]);
    }
    public function updateLateFee($data)
    {
        try {
            $this->db->beginTransaction();
            $bill = $this->db->find('spp_tagihan', ['id' => $data['billId']]);
            $initialLateFee = $this->db->find('spp_tagihan_detail', [
                'tagihan_id' => $bill['id'],
                'jenis' => 'late',
                'bulan' => $data['month'],
                'tahun' => $data['year'],
            ]);
            $trx_id = Call::uuidv4();
            $response = $this->db->update(
                'spp_tagihan_detail',
                ['nominal' => $data['lateFee']],
                [
                    'tagihan_id' => $data['billId'],
                    'jenis' => 'late',
                    'bulan' => $data['month'],
                    'tahun' => $data['year'],
                ],
            );
            $dendaFinal = $bill['denda'] - $initialLateFee['nominal'] + $data['lateFee'];
            $this->db->update(
                'spp_tagihan',
                [
                    'denda' => (int)$dendaFinal,
                    'midtrans_trx_id' => $trx_id,
                ],
                ['id' => $data['billId']],
            );

            $this->midtrans->cancelTransaction($bill['midtrans_trx_id']);

            $st = $this->db->find('siswa', ['id' => $bill['siswa_id']]);
            if (!$st) {
                throw new Exception("Data siswa dengan ID " . $bill['siswa_id'] . " tidak ditemukan.");
            }

            $bd = $this->db->findAll('spp_tagihan_detail', ['tagihan_id' => $bill['id'], 'lunas' => 0, 'bulan' => ["<=", $bill['bulan']]]);
            if (empty($bd)) {
                throw new Exception("Tidak ada detail tagihan yang belum lunas untuk tagihan ID " . $bill['id']);
            }

            $items = [];
            $sum = 0;
            foreach ($bd as $d) {
                $items[] = [
                    'id'       => $d['id'],
                    'price'    => (int)$d['nominal'], 
                    'quantity' => 1,
                    'name'     => $d['jenis'] . ' ' . $d['bulan'] . ' ' . $d['tahun'],
                ];
                $sum += (int)$d['nominal'];
            }

            $mdResult = $this->midtrans->charge([
                'payment_type' => 'bank_transfer',
                'transaction_details' => [
                    'gross_amount' => $sum,
                    'order_id' => $trx_id,
                ],
                'customer_details' => [
                    'email' => '', 
                    'first_name' => $st['nama'],
                    'last_name' => '',
                    'phone' => $st['no_hp_ortu'],
                ],
                'item_details' => $items,
                'bank_transfer' => [
                    'bank' => 'bni',
                    'va_number' => $st['va'], 
                ],
            ]);

            if (isset($mdResult->va_numbers[0]->va_number)) {
                $this->db->update('siswa', ['va_midtrans' => $mdResult->va_numbers[0]->va_number], ['id' => $st['id']]);
            } else {
                throw new Exception("Transaksi Midtrans berhasil, namun tidak menerima VA Number.");
            }

            // Memasukan Jurnal
            $diff = $initialLateFee['nominal'] - $data['lateFee'];
            $academicYear = Call::academicYear(ACADEMIC_YEAR_AKT_FORMAT, [
                'year' => $data['year'],
                'month' => $data['month'],
                'day' => 1,
            ]);

            $siswa = $this->db->find('siswa', ['id' => $bill['siswa_id']]);
            $jenjang = $this->db->find('jenjang', ['id' => $siswa['jenjang_id']]);
            
            $base_url = rtrim($_ENV['ACCOUNTING_SYSTEM_URL'], '/');
            $url = "$base_url/page/transaksi/backend/create.php";

            $bulanStr = FormatHelper::formatMonthNameInBahasa((int)$data['month']);
            $kodeTrx = $diff >= 0 ? 'PHPD' : 'PNBD';
            // PNBD
            $postValue[] = [
                'kode_transaksi' => $kodeTrx,
                'tahun_ajaran' => $academicYear,
                'sumber_dana' => 'Rutin',
                'nama_jenjang' => $jenjang['nama'],
                'saldo' => abs($diff),
                'bulan' => $bulanStr
            ];

            $json_data = json_encode($postValue);
            $headers = ['Content-Type: application/json'];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            curl_close($ch);

            $this->db->commit();
            return ApiResponse::success($response);
        } catch (\Exception $e) {
            return ApiResponse::error("Gagal Mengubah Denda Tagihan: ". $e, 500, $e);
        }
    }
}
