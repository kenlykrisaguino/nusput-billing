<?php

namespace App\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;

class ClassBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getClassList()
    {
        $stmt = "SELECT
                    k.id AS id,
                    j.nama AS jenjang,
                    t.nama AS tingkat,
                    k.nama AS kelas,
                    st.nominal AS nominal,
                    st.tahun,
                    (SELECT COUNT(id) FROM siswa WHERE kelas_id = k.id) AS active_student
                 FROM
                    kelas k
                    JOIN tingkat t ON k.tingkat_id = t.id
                    JOIN jenjang j ON t.jenjang_id = j.id
                    LEFT JOIN spp_tarif st ON st.kelas_id = k.id
                 GROUP BY
                    k.id, j.nama, t.nama, k.nama, st.nominal, st.tahun
                 ORDER BY
                    j.id, t.id, k.id";

        return $this->db->fetchAll($this->db->query($stmt));
    }

    public function getAllJenjang()
    {
        $query = 'SELECT id, nama FROM jenjang ORDER BY id ASC';
        return $this->db->fetchAll($this->db->query($query));
    }

    public function getTingkatByJenjang($jenjangId)
    {
        if (empty($jenjangId) || !is_numeric($jenjangId)) {
            return [];
        }
        $query = 'SELECT id, nama FROM tingkat WHERE jenjang_id = ? ORDER BY id ASC';
        return $this->db->fetchAll($this->db->query($query, [$jenjangId]));
    }

    public function getKelasByTingkat($tingkatId)
    {
        if (empty($tingkatId) || !is_numeric($tingkatId)) {
            return [];
        }
        $query = 'SELECT id, nama FROM kelas WHERE tingkat_id = ? ORDER BY id ASC';
        return $this->db->fetchAll($this->db->query($query, [$tingkatId]));
    }

    public function getTarifSPP($jenjangId, $tingkatId, $kelasId = null)
    {
        if (empty($jenjangId) || empty($tingkatId)) {
            return ['nominal' => 0];
        }

        if (!empty($kelasId)) {
            $tarifSpesifik = $this->db->find('spp_tarif', [
                'jenjang_id' => (int) $jenjangId,
                'tingkat_id' => (int) $tingkatId,
                'kelas_id' => (int) $kelasId,
            ]);
            if ($tarifSpesifik) {
                return $tarifSpesifik;
            }
        }

        $tarifUmum = $this->db->find('spp_tarif', [
            'jenjang_id' => (int) $jenjangId,
            'tingkat_id' => (int) $tingkatId,
        ]);

        if ($tarifUmum) {
            return $tarifUmum;
        }

        return ['nominal' => 0];
    }

    /**
     * Menyimpan jenjang baru ke database.
     */
    public function createLevel()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $nama = trim($data['nama'] ?? '');
        $vaCode = trim($data['va_code'] ?? '');

        if (empty($nama) || empty($vaCode)) {
            return ApiResponse::error('Nama Jenjang dan Kode VA wajib diisi.', 422);
        }

        if (!is_numeric($vaCode)) {
            return ApiResponse::error('Kode VA harus berupa angka.', 422);
        }

        $existing = $this->db->query('SELECT id FROM jenjang WHERE nama = ? OR va_code = ?', [$nama, $vaCode]);
        if ($this->db->fetchAssoc($existing)) {
            return ApiResponse::error('Nama Jenjang atau Kode VA sudah digunakan.', 409);
        }

        try {
            $this->db->insert('jenjang', [
                'nama' => $nama,
                'va_code' => (int) $vaCode,
            ]);
            return ApiResponse::success([], 'Jenjang baru berhasil ditambahkan! Silakan muat ulang halaman untuk melihat perubahan di dropdown.');
        } catch (\Exception $e) {
            error_log('Gagal membuat jenjang: ' . $e->getMessage());
            return ApiResponse::error('Gagal menyimpan jenjang ke database.', 500);
        }
    }

    /**
     * Menyimpan tingkat baru ke database.
     */
    public function createGrade()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $jenjangId = $data['jenjang_id'] ?? null;
        $nama = trim($data['nama'] ?? '');

        if (empty($jenjangId) || empty($nama)) {
            return ApiResponse::error('Jenjang dan Nama Tingkat wajib diisi.', 422);
        }

        // Cek duplikasi: tidak boleh ada nama tingkat yang sama dalam satu jenjang yang sama.
        $existing = $this->db->find('tingkat', [
            'jenjang_id' => (int) $jenjangId,
            'nama' => $nama,
        ]);
        if ($existing) {
            return ApiResponse::error("Tingkat dengan nama '{$nama}' sudah ada di jenjang ini.", 409);
        }

        try {
            $this->db->insert('tingkat', [
                'jenjang_id' => (int) $jenjangId,
                'nama' => $nama,
            ]);
            return ApiResponse::success([], 'Tingkat baru berhasil ditambahkan! Silakan muat ulang halaman.');
        } catch (\Exception $e) {
            error_log('Gagal membuat tingkat: ' . $e->getMessage());
            return ApiResponse::error('Gagal menyimpan tingkat ke database.', 500);
        }
    }

    /**
     * Menyimpan kelas baru ke database.
     */
    public function createClass()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $tingkatId = $data['tingkat_id'] ?? null;
        $nama = trim($data['nama'] ?? '');

        if (empty($tingkatId) || empty($nama)) {
            return ApiResponse::error('Tingkat dan Nama Kelas wajib diisi.', 422);
        }

        $existing = $this->db->find('kelas', [
            'tingkat_id' => (int) $tingkatId,
            'nama' => $nama,
        ]);
        if ($existing) {
            return ApiResponse::error("Kelas dengan nama '{$nama}' sudah ada di tingkat ini.", 409);
        }

        try {
            $this->db->insert('kelas', [
                'tingkat_id' => (int) $tingkatId,
                'nama' => $nama,
            ]);
            return ApiResponse::success([], 'Kelas baru berhasil ditambahkan! Silakan muat ulang halaman.');
        } catch (\Exception $e) {
            error_log('Gagal membuat kelas: ' . $e->getMessage());
            return ApiResponse::error('Gagal menyimpan kelas ke database.', 500);
        }
    }

    /**
     * Mengambil daftar tarif yang ada untuk ditampilkan di tabel.
     */
    public function getTariffList()
    {
        $year = Call::year();
        $query = "SELECT
                    st.id,
                    j.nama AS jenjang,
                    t.nama AS tingkat,
                    k.nama AS kelas,
                    st.nominal,
                    st.tahun,
                    (
                        SELECT COUNT(s.id)
                        FROM siswa s
                        WHERE s.spp = st.nominal
                        AND s.jenjang_id = st.jenjang_id
                        AND s.tingkat_id = st.tingkat_id
                        AND (s.kelas_id = st.kelas_id OR st.kelas_id IS NULL)
                    ) AS jumlah_siswa,
                    CONCAT(j.nama, ' ', t.nama, ' ', COALESCE(k.nama, '')) AS nama_tarif
                FROM spp_tarif st
                JOIN jenjang j ON st.jenjang_id = j.id
                JOIN tingkat t ON st.tingkat_id = t.id
                LEFT JOIN kelas k ON st.kelas_id = k.id
                WHERE st.tahun = (SELECT MAX(tahun) FROM spp_tarif)
                ORDER BY j.id, t.id, k.id;";
        return $this->db->fetchAll($this->db->query($query));
    }

    /**
     * Menyimpan tarif baru ke database.
     */
    public function createTariff()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $jenjangId = $data['jenjang_id'] ?? null;
        $tingkatId = $data['tingkat_id'] ?? null;
        $kelasId = $data['kelas_id'] ?? null;
        $nominal = $data['nominal'] ?? null;
        $tahun = $data['tahun'] ?? Call::year();

        if (empty($jenjangId) || empty($tingkatId) || !isset($nominal)) {
            return ApiResponse::error('Jenjang, Tingkat, dan Nominal wajib diisi.', 422);
        }

        if(empty($kelasId)) {
            $criteria = [
                'jenjang_id' => (int) $jenjangId,
                'tingkat_id' => (int) $tingkatId,
                'tahun' => $tahun
            ];
        } else {
            $criteria = [
                'jenjang_id' => (int) $jenjangId,
                'tingkat_id' => (int) $tingkatId,
                'kelas_id' => (int) $kelasId,
                'tahun' => $tahun
            ];
        }
        
        try {
            $tariff = $this->db->findAll('spp_tarif', $criteria);
            $count = count($tariff);
            if ($tariff) {
                if($count == 1){
                    $this->db->update('spp_tarif', array_merge($criteria, ['nominal' => (float) $nominal]), ['id' => $tariff[0]['id']]);
                } else {
                    return ApiResponse::error('Masukan kombinasi jenjang, tingkat, dan kelas yang tepat.', 400);
                }
            } else {
                $this->db->insert('spp_tarif', array_merge($criteria, ['nominal' => (float) $nominal]));
            }
            return ApiResponse::success([], 'Tarif baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            error_log('Gagal membuat tarif: ' . $e->getMessage());
            return ApiResponse::error('Gagal menyimpan tarif ke database.', 500);
        }
    }

    /**
     * Mengambil detail satu tarif beserta nama relasinya (Jenjang, Tingkat, Kelas).
     */
    public function getTariffDetailById($tariffId)
    {
        if (empty($tariffId) || !is_numeric($tariffId)) {
            return null;
        }

        $query = "SELECT
                    st.id,
                    st.jenjang_id,
                    st.tingkat_id,
                    st.kelas_id,
                    st.nominal,
                    st.tahun,
                    j.nama AS jenjang,
                    t.nama AS tingkat,
                    k.nama AS kelas
                  FROM spp_tarif st
                  JOIN jenjang j ON st.jenjang_id = j.id
                  JOIN tingkat t ON st.tingkat_id = t.id
                  LEFT JOIN kelas k ON st.kelas_id = k.id
                  WHERE st.id = ?";

        $result = $this->db->query($query, [$tariffId]);
        return $this->db->fetchAssoc($result);
    }

    /**
     * Hanya meng-update nominal dari sebuah tarif.
     */
    public function updateTariffNominal($tariffId, $nominal)
    {
        if (empty($tariffId) || !isset($nominal) || !is_numeric($nominal)) {
            return ApiResponse::error('Data tidak valid.', 422);
        }

        $affectedRows = $this->db->update('spp_tarif', ['nominal' => (float) $nominal], ['id' => (int) $tariffId]);

        if ($affectedRows > 0) {
            return ApiResponse::success([], 'Tarif berhasil diupdate.');
        } else {
            return ApiResponse::error('Tidak ada perubahan atau tarif tidak ditemukan.', 400);
        }
    }
}
