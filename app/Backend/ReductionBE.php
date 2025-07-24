<?php

namespace App\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use App\Midtrans\Midtrans;
use Exception;

class ReductionBE
{
    private $db;
    private $midtrans;

    public function __construct($database, Midtrans $midtrans)
    {
        $this->db = $database;
        $this->midtrans = $midtrans;
    }

    public function get()
    {
        $stmt = "SELECT 
                    r.id, s.nama, j.nama as jenjang, 
                    t.nama as tingkat, k.nama as kelas, r.bulan,
                    r.tahun, r.nominal, r.created_at
                 FROM
                    spp_request_keringanan r LEFT JOIN
                    siswa s ON s.id = r.siswa_id LEFT JOIN
                    jenjang j ON j.id = s.jenjang_id LEFT JOIN
                    tingkat t ON t.id = s.tingkat_id LEFT JOIN
                    kelas k ON k.id = s.kelas_id
                    ";
        $data = $this->db->fetchAll($this->db->query($stmt));

        foreach($data as $id => $item){
            $bulan = FormatHelper::formatMonthNameInBahasa($item['bulan']);
            $item['periode'] = "$bulan - " . $item['tahun'];
            $data[$id] = $item;
        }
        return $data;
    }

    public function create()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Format JSON tidak valid.', 400);
        }

        $required = ['va', 'nominal', 'bulan', 'tahun'];

        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '$field' wajib diisi.";
            }
        }

        if (!empty($errors)) {
            return ApiResponse::error('Data tidak lengkap.', 422, $errors);
        }

        // Find Siswa
        $siswa = $this->db->find('siswa', ['va' => $data['va']]);

        // Find Bill
        $bill = $this->db->find('spp_tagihan', ['siswa_id' => $siswa['id']]);

        $billAwal = $bill['total_nominal'];

        // Find Bill Details
        $lateBill = $this->db->find('spp_tagihan_detail', [
            'tagihan_id' => $bill['id'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
            'jenis' => 'late'
        ]);

        if(!isset($lateBill)){
            return ApiResponse::error('Tagihan Tidak Ditemukan.', 404, null);
        }

        $diff = $lateBill['nominal'] - $data['nominal'];
        if($diff < 0){
            return ApiResponse::error('Nominal peringanan biaya terlambat uang sekolah melebihi batas : ' . FormatHelper::formatRupiah($lateBill['nominal']), 400);
        }

        $adminBill = $this->db->find('spp_tagihan_detail', [
            'tagihan_id' => $bill['id'],
            'bulan' => $data['bulan'],
            'tahun' => $data['tahun'],
            'jenis' => 'admin'
        ]);

        if(!isset($adminBill)){
            $this->db->insert('spp_tagihan_detail', [
                'tagihan_id' => $bill['id'],
                'bulan' => $data['bulan'],
                'tahun' => $data['tahun'],
                'jenis' => 'admin',
                'nominal' => Call::adminVA()
            ]);
        }

        try {
            $this->db->beginTransaction();
            $trx_id = Call::uuidv4();

                
            $this->db->update('spp_tagihan_detail', [
                'nominal' => $adminBill['nominal'] + Call::adminVA()
            ], [
                'tagihan_id' => $bill['id'],
                'bulan' => $data['bulan'],
                'tahun' => $data['tahun'],
                'jenis' => 'admin'
            ]);

            $this->db->update(
                'spp_tagihan_detail',
                ['nominal' => $diff],
                ['id' => $lateBill['id']],
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

            $dendaFinal = $bill['denda'] - $data['nominal'];
            $this->db->update(
                'spp_tagihan',
                [
                    'denda' => (int)$dendaFinal,
                    'total_nominal' => (int)$sum,
                    'midtrans_trx_id' => $trx_id,
                ],
                ['id' => $bill['id']],
            );

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
            $academicYear = Call::academicYear(ACADEMIC_YEAR_AKT_FORMAT, [
                'year' => $data['tahun'],
                'month' => $data['bulan'],
                'day' => 1,
            ]);

            $this->db->insert('spp_request_keringanan', [
                'siswa_id' => $siswa['id'],
                'nominal' => $data['nominal'],
                'bulan' => $data['bulan'],
                'tahun' => $data['tahun'],
                'keterangan' => '',
                'created_at' => Call::timestamp()
            ]);

            $siswa = $this->db->find('siswa', ['id' => $bill['siswa_id']]);
            $jenjang = $this->db->find('jenjang', ['id' => $siswa['jenjang_id']]);
            
            $base_url = rtrim($_ENV['ACCOUNTING_SYSTEM_URL'], '/');
            $url = "$base_url/page/transaksi/backend/create.php";

            $bulanStr = FormatHelper::formatMonthNameInBahasa((int)$data['bulan']);

            $message = "Pengajuan Peringanan Denda SPP Periode $data[bulan] $data[tahun] telah disetujui.
            
            SPP Awal: ". FormatHelper::formatRupiah($billAwal) ."
            Peringanan: - ". FormatHelper::formatRupiah($data['nominal']) ."
            Admin: +". FormatHelper::formatRupiah(Call::adminVA()) ."
            Total Akhir: ". FormatHelper::formatRupiah($sum);

            $msgLists[] = [
                'target' => $siswa['no_hp_ortu'],
                'message' => $message,
                'delay' => '1',
            ];
            $messages = json_encode($msgLists);
            $fonnte = Fonnte::sendMessage(['data' => $messages]);

            $kodeTrx = $data['nominal'] >= 0 ? 'PHPD' : 'PNBD';
            // PNBD
            $postValue[] = [
                'kode_transaksi' => $kodeTrx,
                'tahun_ajaran' => $academicYear,
                'sumber_dana' => 'Rutin',
                'nama_jenjang' => $jenjang['nama'],
                'saldo' => abs($data['nominal']),
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