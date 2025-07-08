<?php

namespace app\Backend;

use App\Helpers\Call;

require_once dirname(dirname(__DIR__)) . '/config/constants.php';

class RecapBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getRecaps()
    {        
        $params = [
            'siswa' => $_GET['filter-siswa'] ??  NULL_VALUE,
            'year' => $_GET['filter-tahun'] ?? Call::year(),
            'month' => $_GET['filter-bulan'] ??  NULL_VALUE,
            'level' => $_GET['filter-jenjang'] ??  NULL_VALUE,
            'grade' => $_GET['filter-tingkat'] ??  NULL_VALUE,
            'section' => $_GET['filter-kelas'] ??  NULL_VALUE,
        ];

        $p = ["s.deleted_at IS NULL"];
        $q = [];

        if(!empty($params['siswa'])){
            $p[] = "s.id = ?";
            $q[] = $params['siswa'];
        }
        if(!empty($params['year'])){
            $p[] = "d.tahun = ?";
            $q[] = $params['year'];
        }
        if(!empty($params['month'])){
            $p[] = "d.bulan = ?";
            $q[] = $params['month'];
        }
        if(!empty($params['level'])){
            $p[] = "l.id = ?";
            $q[] = $params['level'];
        }
        if(!empty($params['grade'])){
            $p[] = "g.id = ?";
            $q[] = $params['grade'];
        }
        if(!empty($params['section'])){
            $p[] = "s.id = ?";
            $q[] = $params['section'];
        }

        $query = "SELECT 
                    s.nama, CONCAT(
                        COALESCE(j.nama, ''),
                        ' ', 
                        COALESCE(t.nama, ''), 
                        ' ', 
                        COALESCE(k.nama, '')
                    ) AS kelas,
                    SUM(CASE
                        WHEN d.lunas = true THEN d.nominal
                        ELSE 0
                    END) AS penerimaan,
                    SUM(CASE
                        WHEN d.jenis = 'late' AND d.lunas = false THEN d.nominal
                        ELSE 0
                    END) AS denda
                  FROM
                    spp_tagihan_detail d JOIN
                    spp_tagihan ON d.tagihan_id = spp_tagihan.id LEFT JOIN
                    siswa s ON spp_tagihan.siswa_id = s.id LEFT JOIN
                    jenjang j ON s.jenjang_id = j.id LEFT JOIN
                    tingkat t ON s.tingkat_id = t.id LEFT JOIN
                    kelas k   ON s.kelas_id   = k.id
                  WHERE " . implode(" AND ", $p) . "
                  GROUP BY 
                    s.nama, j.nama, t.nama, k.nama
                  ";
        
        $result = $this->db->query($query, $q);
        return $this->db->fetchAll($result);
    }
}