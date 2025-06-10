<?php

namespace App\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\FormatHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentBE
{
    private $db;
    private $classBE;

    public function __construct($database, ClassBE $classBE)
    {
        $this->db = $database;
        $this->classBE = $classBE;
    }

    /**
     * Mengambil daftar siswa untuk ditampilkan di tabel.
     */
    public function getStudents()
    {
        $query = "SELECT
                    s.id, s.nis, s.nama, j.nama AS jenjang, t.nama AS tingkat, k.nama AS kelas,
                    s.va, s.spp,
                    (SELECT MAX(p.tanggal_pembayaran) FROM spp_pembayaran p WHERE p.siswa_id = s.id) AS latest_payment
                  FROM
                    siswa s
                    LEFT JOIN jenjang j ON s.jenjang_id = j.id
                    LEFT JOIN tingkat t ON s.tingkat_id = t.id
                    LEFT JOIN kelas k ON s.kelas_id = k.id
                  WHERE
                    s.deleted_at IS NULL";

        $params = [];

        if (!empty($_GET['search'])) {
            $search = '%' . trim($_GET['search']) . '%';
            $query .= ' AND (s.nama LIKE ? OR s.nis LIKE ? OR s.va LIKE ?)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($_GET['jenjang_id'])) {
            $query .= ' AND s.jenjang_id = ?';
            $params[] = (int) $_GET['jenjang_id'];
        }

        if (!empty($_GET['tingkat_id'])) {
            $query .= ' AND s.tingkat_id = ?';
            $params[] = (int) $_GET['tingkat_id'];
        }

        if (!empty($_GET['kelas_id'])) {
            $query .= ' AND s.kelas_id = ?';
            $params[] = (int) $_GET['kelas_id'];
        }

        $query .= ' GROUP BY s.id ORDER BY j.id, t.id, k.id, s.nama ASC';

        $result = $this->db->query($query, $params);
        return $this->db->fetchAll($result);
    }

    /**
     * Menerima dan memproses data dari form tambah siswa manual.
     */
    public function formCreateStudent()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Format JSON tidak valid.', 400);
        }

        $required = ['nama', 'nis', 'jenjang_id', 'tingkat_id', 'spp'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '$field' wajib diisi.";
            }
        }
        if (!empty($errors)) {
            return ApiResponse::error('Data tidak lengkap.', 422, $errors);
        }

        $nis = trim($data['nis']);

        $existingStudent = $this->db->find('siswa', ['nis' => $nis]);

        if ($existingStudent) {
            return ApiResponse::error("NIS '{$nis}' sudah terdaftar.", 409);
        }

        $jenjang = $this->db->find('jenjang', ['id' => (int) $data['jenjang_id']]);
        if (!$jenjang) {
            return ApiResponse::error('Jenjang tidak valid.', 422);
        }

        $insertData = [
            'nama' => trim($data['nama']),
            'nis' => $nis,
            'jenjang_id' => (int) $data['jenjang_id'],
            'tingkat_id' => (int) $data['tingkat_id'],
            'kelas_id' => isset($data['kelas_id']) ? (int) $data['kelas_id'] : null,
            'va' => FormatHelper::formatVA($jenjang['va_code'], $nis),
            'no_hp_ortu' => $data['no_hp_ortu'] ?? null,
            'spp' => (float) $data['spp'],
        ];

        try {
            $this->db->beginTransaction();

            // Menggunakan method insert() yang baru
            $newStudentId = $this->db->insert('siswa', $insertData);
            if (!$newStudentId) {
                throw new Exception('Gagal menyimpan data siswa.');
            }

            $userData = [
                'username' => $insertData['va'],
                'password' => md5($nis),
                'role' => 'siswa',
                'siswa_id' => $newStudentId,
            ];
            $this->db->insert('users', $userData);

            $this->db->commit();

            return ApiResponse::success(['message' => 'Siswa ' . htmlspecialchars($insertData['nama']) . ' berhasil ditambahkan.']);
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Gagal membuat siswa: ' . $e->getMessage());
            return ApiResponse::error('Terjadi kesalahan di server saat menyimpan data.', 500);
        }
    }

    /**
     * Menangani upload file Excel untuk impor siswa massal.
     */
    public function importStudentsFromXLSX()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Metode tidak diizinkan', 405);
        }

        if (!isset($_FILES['student_xlsx_file']) || $_FILES['student_xlsx_file']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('Error saat upload file. Pastikan nama input adalah "student_xlsx_file".', 400);
        }

        $filePath = $_FILES['student_xlsx_file']['tmp_name'];

        // 1. Memuat semua data master untuk validasi
        $jenjangData = $this->db->findAll('jenjang');
        $tingkatData = $this->db->findAll('tingkat');
        $kelasData = $this->db->findAll('kelas');

        // Buat map untuk pencarian cepat (case-insensitive)
        $jenjangMap = [];
        foreach ($jenjangData as $j) {
            $jenjangMap[strtolower($j['nama'])] = $j['id'];
        }

        $tingkatMap = [];
        foreach ($tingkatData as $t) {
            $tingkatMap[strtolower($t['nama'])][$t['jenjang_id']] = $t['id'];
        }

        $kelasMap = [];
        foreach ($kelasData as $k) {
            $kelasMap[strtolower($k['nama'])][$k['tingkat_id']] = $k['id'];
        }

        $validRows = [];
        $errorRows = [];

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $header = $sheet->rangeToArray('A1:F1', null, true, false)[0];

            // 2. Loop melalui setiap baris di Excel (mulai dari baris 2)
            for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
                $rowData = $sheet->rangeToArray('A' . $rowNum . ':F' . $rowNum, null, true, false)[0];
                if (empty(array_filter($rowData))) {
                    continue;
                }

                $rowErrors = [];
                $nis = trim($rowData[0] ?? '');
                $nama = trim($rowData[1] ?? '');
                $jenjangName = strtolower(trim($rowData[2] ?? ''));
                $tingkatName = strtolower(trim($rowData[3] ?? ''));
                $kelasName = strtolower(trim($rowData[4] ?? ''));
                $noHpOrtu = trim($rowData[5] ?? '');

                // 3. Validasi setiap kolom
                if (empty($nis)) {
                    $rowErrors[] = 'NIS wajib diisi.';
                }
                if (empty($nama)) {
                    $rowErrors[] = 'Nama wajib diisi.';
                }
                if (empty($jenjangName)) {
                    $rowErrors[] = 'Jenjang wajib diisi.';
                }
                if (empty($tingkatName)) {
                    $rowErrors[] = 'Tingkat wajib diisi.';
                }

                $jenjangId = $jenjangMap[$jenjangName] ?? null;
                if (!$jenjangId) {
                    $rowErrors[] = "Jenjang '{$rowData[2]}' tidak ditemukan.";
                }

                $tingkatId = $jenjangId ? $tingkatMap[$tingkatName][$jenjangId] ?? null : null;
                if (!$tingkatId) {
                    $rowErrors[] = "Tingkat '{$rowData[3]}' tidak valid untuk Jenjang '{$rowData[2]}'.";
                }

                $kelasId = $tingkatId ? $kelasMap[$kelasName][$tingkatId] ?? null : null;
                if ($kelasName !== '' && !$kelasId) {
                    $rowErrors[] = "Kelas '{$rowData[4]}' tidak valid untuk Tingkat '{$rowData[3]}'.";
                }

                if (empty($rowErrors)) {
                    $validRows[] = [
                        'nis' => $nis,
                        'nama' => $nama,
                        'jenjang_id' => $jenjangId,
                        'tingkat_id' => $tingkatId,
                        'kelas_id' => $kelasId,
                        'no_hp_ortu' => $noHpOrtu,
                    ];
                } else {
                    $errorRows[] = array_merge($rowData, [implode('; ', $rowErrors)]);
                }
            }
        } catch (Exception $e) {
            return ApiResponse::error('Gagal membaca file Excel: ' . $e->getMessage(), 500);
        }

        // 4. Proses hasil validasi
        if (!empty($errorRows)) {
            // Jika ada error, buat dan kirim file Excel berisi error
            $this->sendErrorExcel($errorRows, array_merge($header, ['Errors']));
        }

        if (empty($validRows)) {
            return ApiResponse::error('Tidak ada data valid yang ditemukan untuk diimpor.', 400);
        }

        // 5. Simpan semua data valid ke database
        try {
            $this->db->beginTransaction();
            $sppTarifCache = [];

            foreach ($validRows as $row) {
                // Ambil tarif SPP
                $key = "{$row['jenjang_id']}-{$row['tingkat_id']}-{$row['kelas_id']}";
                if (!isset($sppTarifCache[$key])) {
                    $tarif = $this->classBE->getTarifSPP($row['jenjang_id'], $row['tingkat_id'], $row['kelas_id']);
                    $sppTarifCache[$key] = $tarif['nominal'] ?? 0;
                }
                $spp = $sppTarifCache[$key];

                // Siapkan data untuk tabel 'siswa' dan 'users'
                $jenjang = $this->db->find('jenjang', ['id' => $row['jenjang_id']]);
                $va = FormatHelper::formatVA($jenjang['va_code'], $row['nis']);

                $studentData = [
                    'nama' => $row['nama'],
                    'nis' => $row['nis'],
                    'jenjang_id' => $row['jenjang_id'],
                    'tingkat_id' => $row['tingkat_id'],
                    'kelas_id' => $row['kelas_id'],
                    'va' => $va,
                    'no_hp_ortu' => $row['no_hp_ortu'],
                    'spp' => $spp,
                ];
                $newStudentId = $this->db->insert('siswa', $studentData);

                if (!$newStudentId) {
                    throw new Exception("Gagal menyimpan siswa dengan NIS {$row['nis']}");
                }

                $userData = [
                    'username' => $va,
                    'password' => md5($row['nis']),
                    'role' => 'siswa',
                    'siswa_id' => $newStudentId,
                ];
                $this->db->insert('users', $userData);
            }

            $this->db->commit();
            return ApiResponse::success(count($validRows) . ' siswa berhasil diimpor.');
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Gagal impor massal: ' . $e->getMessage());
            return ApiResponse::error('Terjadi kesalahan saat menyimpan data ke database. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fungsi bantuan untuk membuat dan mengirim file Excel berisi error validasi.
     */
    private function sendErrorExcel(array $errorData, array $header)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$header], null, 'A1');
        $sheet->fromArray($errorData, null, 'A2');

        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet
            ->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()
            ->setBold(true);

        $writer = new Xlsx($spreadsheet);
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Mengirim header khusus agar Axios tahu ini adalah file download error
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="import_errors.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    /**
     * Membuat dan mengirimkan file template Excel untuk impor siswa.
     */
    public function getStudentFormatXLSX()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NIS', 'Nama Lengkap', 'Jenjang', 'Tingkat', 'Kelas', 'No. HP Orang Tua'];
        $sheet->fromArray([$headers], null, 'A1');

        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet
            ->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()
            ->setBold(true);

        $writer = new Xlsx($spreadsheet);
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_import_siswa.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    /**
     * Menangani update siswa massal dari file Excel.
     * Meng-update/membuat siswa dari file, dan me-soft-delete yang tidak ada di file.
     */
    public function bulkUpdateStudentsFromXLSX()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Metode tidak diizinkan', 405);
        }
        if (!isset($_FILES['bulk_update_xlsx_file']) || $_FILES['bulk_update_xlsx_file']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('Error saat upload file.', 400);
        }

        $filePath = $_FILES['bulk_update_xlsx_file']['tmp_name'];

        // 1. Validasi file Excel (sama seperti bulk create)
        $validationResult = $this->validateStudentExcel($filePath);

        if (!empty($validationResult['errors'])) {
            $this->sendErrorExcel($validationResult['errors'], $validationResult['header']);
        }

        $validRows = $validationResult['valid'];
        if (empty($validRows)) {
            return ApiResponse::error('Tidak ada data valid yang ditemukan untuk diproses.', 400);
        }

        // 2. Ambil semua NIS siswa yang aktif dari database
        $allDbNisResult = $this->db->query('SELECT nis FROM siswa WHERE deleted_at IS NULL');
        $allDbNis = array_column($this->db->fetchAll($allDbNisResult), 'nis');

        $nisFromExcel = array_column($validRows, 'nis');

        // 3. Tentukan Aksi untuk Setiap Baris (UPDATE, CREATE, DELETE)
        $studentsToUpdate = [];
        $studentsToCreate = [];
        foreach ($validRows as $row) {
            if (in_array($row['nis'], $allDbNis)) {
                $studentsToUpdate[] = $row;
            } else {
                $studentsToCreate[] = $row;
            }
        }
        $nisToDelete = array_diff($allDbNis, $nisFromExcel);

        // 4. Proses dalam Transaksi
        try {
            $this->db->beginTransaction();

            $sppTarifCache = [];

            // --- Proses CREATE ---
            if (!empty($studentsToCreate)) {
                foreach ($studentsToCreate as $row) {
                    $this->createSingleStudent($row, $sppTarifCache);
                }
            }

            // --- Proses UPDATE ---
            if (!empty($studentsToUpdate)) {
                foreach ($studentsToUpdate as $row) {
                    $this->updateSingleStudent($row, $sppTarifCache);
                }
            }

            // --- Proses DELETE (Soft Delete) ---
            if (!empty($nisToDelete)) {
                $placeholders = implode(',', array_fill(0, count($nisToDelete), '?'));
                $this->db->query("UPDATE siswa SET deleted_at = NOW() WHERE nis IN ($placeholders)", $nisToDelete);
                $this->db->query("UPDATE users u JOIN siswa s ON u.siswa_id = s.id SET u.password = NULL WHERE s.nis IN ($placeholders)", $nisToDelete);
            }

            $this->db->commit();

            $summary = [
                'created' => count($studentsToCreate),
                'updated' => count($studentsToUpdate),
                'deleted' => count($nisToDelete),
            ];

            return ApiResponse::success($summary, 'Proses update massal selesai.');
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Gagal update massal: ' . $e->getMessage());
            return ApiResponse::error('Terjadi kesalahan saat memproses data. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Memvalidasi file Excel siswa dan mengembalikan data valid & error.
     */
    private function validateStudentExcel(string $filePath): array
    {
        $jenjangData = $this->db->findAll('jenjang');
        $tingkatData = $this->db->findAll('tingkat');
        $kelasData = $this->db->findAll('kelas');

        $jenjangMap = [];
        foreach ($jenjangData as $j) {
            $jenjangMap[strtolower($j['nama'])] = $j['id'];
        }
        $tingkatMap = [];
        foreach ($tingkatData as $t) {
            $tingkatMap[strtolower($t['nama'])][$t['jenjang_id']] = $t['id'];
        }
        $kelasMap = [];
        foreach ($kelasData as $k) {
            $kelasMap[strtolower($k['nama'])][$k['tingkat_id']] = $k['id'];
        }

        $validRows = [];
        $errorRows = [];
        $header = [];

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $header = $sheet->rangeToArray('A1:F1', null, true, false)[0];

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $rowData = $sheet->rangeToArray('A' . $rowNum . ':F' . $rowNum, null, true, false)[0];
            if (empty(array_filter($rowData))) {
                continue;
            }

            $rowErrors = [];
            $nis = trim($rowData[0] ?? '');
            $nama = trim($rowData[1] ?? '');
            $jenjangName = strtolower(trim($rowData[2] ?? ''));
            $tingkatName = strtolower(trim($rowData[3] ?? ''));
            $kelasName = strtolower(trim($rowData[4] ?? ''));
            $noHpOrtu = trim($rowData[5] ?? '');

            if (empty($nis)) {
                $rowErrors[] = 'NIS wajib diisi.';
            }
            if (empty($nama)) {
                $rowErrors[] = 'Nama wajib diisi.';
            }
            if (empty($jenjangName)) {
                $rowErrors[] = 'Jenjang wajib diisi.';
            }
            if (empty($tingkatName)) {
                $rowErrors[] = 'Tingkat wajib diisi.';
            }

            $jenjangId = $jenjangMap[$jenjangName] ?? null;
            if (!$jenjangId) {
                $rowErrors[] = "Jenjang '{$rowData[2]}' tidak ditemukan.";
            }

            $tingkatId = $jenjangId ? $tingkatMap[$tingkatName][$jenjangId] ?? null : null;
            if (!$tingkatId) {
                $rowErrors[] = "Tingkat '{$rowData[3]}' tidak valid untuk Jenjang '{$rowData[2]}'.";
            }

            $kelasId = $tingkatId ? $kelasMap[$kelasName][$tingkatId] ?? null : null;
            if ($kelasName !== '' && !$kelasId) {
                $rowErrors[] = "Kelas '{$rowData[4]}' tidak valid untuk Tingkat '{$rowData[3]}'.";
            }

            if (empty($rowErrors)) {
                $validRows[] = [
                    'nis' => $nis,
                    'nama' => $nama,
                    'jenjang_id' => $jenjangId,
                    'tingkat_id' => $tingkatId,
                    'kelas_id' => $kelasId,
                    'no_hp_ortu' => $noHpOrtu,
                ];
            } else {
                $errorRows[] = array_merge($rowData, [implode('; ', $rowErrors)]);
            }
        }

        return ['valid' => $validRows, 'errors' => $errorRows, 'header' => $header];
    }

    /**
     * Logika untuk membuat satu siswa (dipakai oleh bulk create & update)
     */
    private function createSingleStudent(array $row, array &$sppTarifCache): void
    {
        $key = "{$row['jenjang_id']}-{$row['tingkat_id']}-{$row['kelas_id']}";
        if (!isset($sppTarifCache[$key])) {
            $tarif = $this->classBE->getTarifSPP($row['jenjang_id'], $row['tingkat_id'], $row['kelas_id']);
            $sppTarifCache[$key] = $tarif['nominal'] ?? 0;
        }
        $spp = $sppTarifCache[$key];

        $jenjang = $this->db->find('jenjang', ['id' => $row['jenjang_id']]);
        $va = FormatHelper::formatVA($jenjang['va_code'], $row['nis']);

        $studentData = [
            'nama' => $row['nama'],
            'nis' => $row['nis'],
            'jenjang_id' => $row['jenjang_id'],
            'tingkat_id' => $row['tingkat_id'],
            'kelas_id' => $row['kelas_id'],
            'va' => $va,
            'no_hp_ortu' => $row['no_hp_ortu'],
            'spp' => $spp,
        ];
        $newStudentId = $this->db->insert('siswa', $studentData);

        if (!$newStudentId) {
            throw new Exception("Gagal menyimpan siswa baru dengan NIS {$row['nis']}");
        }

        $userData = [
            'username' => $va,
            'password' => md5($row['nis']),
            'role' => 'siswa',
            'siswa_id' => $newStudentId,
        ];
        $this->db->insert('users', $userData);
    }

    /**
     * Logika untuk meng-update satu siswa (dipakai oleh bulk update)
     */
    private function updateSingleStudent(array $row, array &$sppTarifCache): void
    {
        $key = "{$row['jenjang_id']}-{$row['tingkat_id']}-{$row['kelas_id']}";
        if (!isset($sppTarifCache[$key])) {
            $tarif = $this->classBE->getTarifSPP($row['jenjang_id'], $row['tingkat_id'], $row['kelas_id']);
            $sppTarifCache[$key] = $tarif['nominal'] ?? 0;
        }
        $spp = $sppTarifCache[$key];

        $jenjang = $this->db->find('jenjang', ['id' => $row['jenjang_id']]);
        $va = FormatHelper::FormatVA($jenjang['va_code'], $row['nis']);

        $studentData = [
            'nama' => $row['nama'],
            'jenjang_id' => $row['jenjang_id'],
            'tingkat_id' => $row['tingkat_id'],
            'kelas_id' => $row['kelas_id'],
            'va' => $va,
            'no_hp_ortu' => $row['no_hp_ortu'],
            'spp' => $spp,
            'deleted_at' => null,
        ];

        $this->db->update('siswa', $studentData, ['nis' => $row['nis']]);

        $this->db->update('users', ['username' => $va], ['siswa_id' => $this->db->find('siswa', ['nis' => $row['nis']])['id']]);
    }

    /**
     * Mengambil data siswa aktif dan mengekspornya ke file Excel.
     */
    public function exportStudentXLSX()
    {
        // 1. Definisikan header yang sama persis dengan template upload
        $header = ['NIS', 'Nama Lengkap', 'Jenjang', 'Tingkat', 'Kelas', 'No. HP Orang Tua'];

        // 2. Query untuk mengambil data siswa aktif dengan format yang sesuai
        $query = "SELECT
                    s.nis,
                    s.nama,
                    j.nama AS jenjang,
                    t.nama AS tingkat,
                    k.nama AS kelas,
                    s.no_hp_ortu
                  FROM
                    siswa s
                    LEFT JOIN jenjang j ON s.jenjang_id = j.id
                    LEFT JOIN tingkat t ON s.tingkat_id = t.id
                    LEFT JOIN kelas k ON s.kelas_id = k.id
                  WHERE
                    s.deleted_at IS NULL
                  ORDER BY
                    j.id, t.id, k.id, s.nama ASC";

        $studentsResult = $this->db->query($query);
        $studentsData = $this->db->fetchAll($studentsResult);

        // 3. Buat file Excel menggunakan PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Tulis header
        $sheet->fromArray([$header], null, 'A1');

        // Tulis data siswa mulai dari baris 2
        if (!empty($studentsData)) {
            $sheet->fromArray($studentsData, null, 'A2');
        }

        // 4. Styling (opsional tapi bagus)
        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet
            ->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()
            ->setBold(true);

        // 5. Kirim file ke browser untuk diunduh
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Membersihkan output buffer jika ada
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Set header HTTP untuk download file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export_siswa_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

/**
     * Mengambil detail lengkap satu siswa berdasarkan ID untuk form edit.
     */
    public function getStudentDetailById($id)
    {
        if (empty($id) || !is_numeric($id)) return null;
        return $this->db->find('siswa', ['id' => (int)$id]);
    }

    /**
     * Meng-update data utama seorang siswa dari form edit.
     */
    public function updateStudentData($id, $data)
    {
        if (empty($id) || empty($data)) return ApiResponse::error('Data tidak lengkap.', 400);

        $nis = trim($data['nis'] ?? '');
        if (empty($nis)) return ApiResponse::error('NIS tidak boleh kosong.', 422);

        $existingResult = $this->db->query("SELECT id FROM siswa WHERE nis = ? AND id != ?", [$nis, $id]);
        if ($this->db->fetchAssoc($existingResult)) {
            return ApiResponse::error("NIS '{$nis}' sudah digunakan oleh siswa lain.", 409);
        }
        
        $jenjang = $this->db->find('jenjang', ['id' => (int)$data['jenjang_id']]);
        if (!$jenjang) return ApiResponse::error('Jenjang tidak valid.', 422);
        
        $updateData = [
            'nama' => trim($data['nama']),
            'nis' => $nis,
            'jenjang_id' => (int)$data['jenjang_id'],
            'tingkat_id' => (int)$data['tingkat_id'],
            'kelas_id' => !empty($data['kelas_id']) ? (int)$data['kelas_id'] : null,
            'va' => FormatHelper::formatVA($jenjang['va_code'], $nis),
            'no_hp_ortu' => $data['no_hp_ortu'] ?? null,
            'spp' => (float)$data['spp']
        ];

        try {
            $this->db->beginTransaction();
            $this->db->update('siswa', $updateData, ['id' => (int)$id]);
            $this->db->update('users', ['username' => $updateData['va']], ['siswa_id' => (int)$id]);
            $this->db->commit();
            return ApiResponse::success([], 'Data siswa berhasil diupdate.');
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Gagal update siswa #{$id}: " . $e->getMessage());
            return ApiResponse::error('Gagal mengupdate data siswa.', 500);
        }
    }
    
    /**
     * Mengambil daftar kategori biaya tambahan.
     */
    public function getFeeCategories()
    {
        return [
            ['id' => 'praktek', 'nama' => 'Biaya Praktek'],
            ['id' => 'ekstra', 'nama' => 'Biaya Ekstrakurikuler'],
            ['id' => 'daycare', 'nama' => 'Biaya Daycare'],
        ];
    }
    
    /**
     * Mengambil biaya tambahan yang ada untuk siswa pada periode tertentu.
     */
    public function getStudentFeesByPeriod($siswaId, $month, $year)
    {
        if (empty($siswaId) || empty($month) || empty($year)) return [];
        
        return $this->db->findAll('spp_biaya_tambahan', [
            'siswa_id' => (int)$siswaId,
            'bulan'    => (int)$month,
            'tahun'    => (int)$year
        ]);
    }
    
    /**
     * Menyimpan (Create/Update/Delete) biaya tambahan untuk siswa pada periode tertentu.
     */
    public function updateStudentFees($siswaId)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $fees = $data['fees'] ?? [];
        $period = $data['period'] ?? [];
        $month = $period['month'] ?? null;
        $year = $period['year'] ?? null;

        if (empty($siswaId) || empty($month) || empty($year)) {
            return ApiResponse::error('Data tidak lengkap (ID Siswa atau Periode).', 400);
        }

        try {
            $this->db->beginTransaction();

            $idsFromRequest = array_filter(array_column($fees, 'id'));
            
            if (!empty($idsFromRequest)) {
                $placeholders = implode(',', array_fill(0, count($idsFromRequest), '?'));
                $params = [(int)$siswaId, (int)$month, (int)$year, ...$idsFromRequest];
                $this->db->query("DELETE FROM spp_biaya_tambahan WHERE siswa_id = ? AND bulan = ? AND tahun = ? AND id NOT IN ($placeholders)", $params);
            } else {
                $this->db->delete('spp_biaya_tambahan', ['siswa_id' => (int)$siswaId, 'bulan' => (int)$month, 'tahun' => (int)$year]);
            }
            
            foreach ($fees as $fee) {
                if (empty($fee['kategori']) || !isset($fee['nominal']) || !is_numeric($fee['nominal'])) continue;

                $feeData = [
                    'siswa_id' => (int)$siswaId, 'bulan' => (int)$month, 'tahun' => (int)$year,
                    'kategori' => $fee['kategori'], 'nominal' => (float)$fee['nominal'],
                    'keterangan' => $fee['keterangan'] ?? '',
                ];

                if (!empty($fee['id'])) {
                    $this->db->update('spp_biaya_tambahan', $feeData, ['id' => (int)$fee['id']]);
                } else {
                    $this->db->insert('spp_biaya_tambahan', $feeData);
                }
            }

            $this->db->commit();
            return ApiResponse::success([], 'Biaya tambahan berhasil disimpan.');
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Gagal update biaya tambahan siswa #{$siswaId}: " . $e->getMessage());
            return ApiResponse::error('Gagal menyimpan biaya tambahan.', 500);
        }
    }

    /**
     * Menghapus (Soft Delete) Siswa
     */
    public function deleteStudent($siswaId)
    {
        $siswa = $this->db->find('siswa', ['id' => $siswaId]);

        $update = $this->db->update('siswa', [
            'deleted_at' => Call::date(),
        ], ['id' => $siswaId]);

        if(!$update){
            return ApiResponse::error('Tidak menemukan data siswa untuk dihapus', 400);
        }
        return ApiResponse::success(null, "Berhasil menonaktifkan $siswa[nama] dari sistem");
    }
}
