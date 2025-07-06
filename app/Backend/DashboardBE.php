<?php

namespace App\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;

class DashboardBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function index()
    {
        return [
            'percentage' => $this->percentage(),
            'by_jenjang' => $this->byJenjang(),
            'count_siswa' => $this->countSiswa(),
            'payment_trend' => $this->paymentTrend()
        ];
    }

    protected function percentage()
    {
        $stmt = "SELECT
                    SUM(
                        CASE
                            WHEN t.status = 'belum_lunas' THEN 1
                            ELSE 0
                        END    
                    ) AS belum_lunas,
                    COUNT(t.id) AS total
                FROM spp_tagihan t JOIN siswa s ON t.siswa_id = s.id
                WHERE s.deleted_at IS NULL";
        $dbResult = $this->db->fetchAssoc($this->db->query($stmt));

        return $dbResult;
    }

    protected function byJenjang()
    {
        $stmt = "SELECT
            j.nama as jenjang,
            SUM(
                CASE
                    WHEN t.status = 'belum_lunas' THEN 1
                    ELSE 0
                END    
            ) AS belum_lunas,
            COUNT(t.id) AS total
        FROM spp_tagihan t JOIN siswa s ON t.siswa_id = s.id JOIN jenjang j ON j.id = s.jenjang_id
        WHERE 
            s.deleted_at IS NULL
        GROUP BY j.nama";
        $resultsWithData = $this->db->fetchAll($this->db->query($stmt));

        $dataMap = [];
        foreach ($resultsWithData as $row) {
            $dataMap[$row['jenjang']] = $row;
        }

        $allJenjang = $this->db->findAll('jenjang');

        $finalData = [];

        foreach($allJenjang as $j){
            $jenjangName = $j['nama'];

            if (isset($dataMap[$jenjangName])) {
                $finalData[] = $dataMap[$jenjangName];
            } else {
                $finalData[] = [
                    'jenjang' => $jenjangName,
                    'belum_lunas' => 0,
                    'total' => 0
                ];
            }
        }

        return $finalData;
    }

    protected function countSiswa()
    {
        $stmt = "SELECT
                    COUNT(id) AS aktif FROM siswa WHERE deleted_at IS NULL";

        $active = $this->db->fetchAssoc($this->db->query($stmt))['aktif'];
        
        $stmt = "SELECT
                    COUNT(id) AS inactive FROM siswa WHERE deleted_at IS NOT NULL";

        $inactive = $this->db->fetchAssoc($this->db->query($stmt))['inactive'];

        return ['active' => $active, 'inactive' => $inactive];
    }

    protected function paymentTrend()
    {
        $stmt = "SELECT 
                    YEAR(tanggal_pembayaran) as tahun,
                    MONTH(tanggal_pembayaran) as bulan,
                    SUM(jumlah_bayar) as total_pembayaran
                FROM 
                    spp_pembayaran
                WHERE 
                    tanggal_pembayaran >= DATE_FORMAT(NOW() - INTERVAL 11 MONTH, '%Y-%m-01')
                GROUP BY 
                    tahun, bulan
                ORDER BY
                    tahun, bulan";
        
        $results = $this->db->fetchAll($this->db->query($stmt));

        $paymentMap = [];
        foreach ($results as $row) {
            $key = $row['tahun'] . '-' . str_pad($row['bulan'], 2, '0', STR_PAD_LEFT);
            $paymentMap[$key] = (float)$row['total_pembayaran'];
        }

        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = strtotime("-$i months");
            
            $key = date('Y-m', $date);
            
            $labels[] = date('M Y', $date);
            
            $data[] = $paymentMap[$key] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

}