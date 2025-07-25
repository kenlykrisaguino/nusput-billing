<?php

namespace App\Database;

use App\Helpers\ApiResponse;
use Exception;
use mysqli;
use mysqli_result;

class Database
{
    private mysqli $connection;

    /**
     * Membuat koneksi ke database saat class dibuat.
     */
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $this->connection = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

        if ($this->connection->connect_error) {
            die('Connection failed: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    /**
     * Menutup koneksi saat object tidak lagi digunakan.
     */
    public function __destruct()
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }

    /**
     * Menjalankan query SQL mentah dengan aman (pakai parameter).
     * Contoh: $db->query("SELECT * FROM siswa WHERE id = ?", [1]);
     */
    public function query(string $sql, array $params = []): mysqli_result|bool
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->connection->error . ' | SQL: ' . $sql);
        }

        if ($params) {
            $types = '';
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i'; // Integer
                } elseif (is_float($param)) {
                    $types .= 'd'; // Double / Float
                } elseif (is_string($param)) {
                    $types .= 's'; // String
                } else {
                    $types .= 's'; 
                }
            }
            
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();
        
        if ($result === false && $stmt->errno === 0) {
            return true;
        }   

        return $result;
    }

    /**
     * Mencari satu baris data dari sebuah tabel.
     * Contoh: $db->find('siswa', ['id' => 1], ['nama DESC', 'umur ASC']);
     */
    public function find(string $table, array $where, array $orderBy = []): ?array
    {
        $whereParts = [];
        $params = [];

        foreach ($where as $key => $value) {
            if (is_null($value)) {
                $whereParts[] = "`$key` IS NULL";
            } else {
                $whereParts[] = "`$key` = ?";
                $params[] = $value;
            }
        }

        $orderStr = '';
        if (!empty($orderBy)) {
            $orderStr = ' ORDER BY ' . implode(', ', $orderBy);
        }

        $sql = "SELECT * FROM `$table` WHERE " . implode(' AND ', $whereParts) . $orderStr . ' LIMIT 1';

        $result = $this->query($sql, $params);
        return $result ? $this->fetchAssoc($result) : null;
    }

    /**
     * Mencari semua baris data dari sebuah tabel dengan kondisi yang lebih kompleks.
     *
     * Contoh Penggunaan:
     * // 1. Kondisi Gleichheit (Sama Dengan)
     * $db->findAll('siswa', ['kelas_id' => 5]);
     * // -> WHERE `kelas_id` = 5
     *
     * // 2. Kondisi Ketidaksamaan (Tidak Sama Dengan)
     * $db->findAll('spp_tagihan_detail', ['lunas' => ['!=', 1]]);
     * // -> WHERE `lunas` != 1
     *
     * // 3. Kondisi Lebih Besar dari
     * $db->findAll('produk', ['stok' => ['>', 0]]);
     * // -> WHERE `stok` > 0
     *
     * // 4. Kondisi IN
     * $db->findAll('user', ['status' => ['IN', ['active', 'pending']]]);
     * // -> WHERE `status` IN (?, ?)
     *
     * // 5. Kondisi LIKE
     * $db->findAll('siswa', ['nama' => ['LIKE', '%budi%']]);
     * // -> WHERE `nama` LIKE ?
     *
     * // 6. Gabungan
     * $db->findAll('spp_tagihan_detail', [
     *     "tagihan_id" => 101,
     *     "lunas" => 0,
     *     "jenis" => ['!=', 1]
     * ]);
     *
     * @param string $table Nama tabel.
     * @param array $where Kondisi pencarian.
     * @param array $orderBy Urutan data.
     * @return array Hasil query.
     */
    public function findAll(string $table, array $where = [], array $orderBy = []): array
    {
        $sql = "SELECT * FROM `$table`";
        $params = [];

        if (!empty($where)) {
            $whereParts = [];
            foreach ($where as $key => $value) {
                if (!is_array($value)) {
                    if (is_null($value)) {
                        $whereParts[] = "`$key` IS NULL";
                    } else {
                        $whereParts[] = "`$key` = ?";
                        $params[] = $value;
                    }
                } else {
                    if (count($value) === 2) {
                        $operator = strtoupper($value[0]);
                        $comparisonValue = $value[1];

                        $allowedOperators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];

                        if (in_array($operator, $allowedOperators)) {
                            if ($operator === 'IN' || $operator === 'NOT IN') {
                                if (is_array($comparisonValue) && !empty($comparisonValue)) {
                                    $placeholders = implode(', ', array_fill(0, count($comparisonValue), '?'));
                                    $whereParts[] = "`$key` $operator ($placeholders)";
                                    $params = array_merge($params, $comparisonValue);
                                }
                            } else {
                                $whereParts[] = "`$key` $operator ?";
                                $params[] = $comparisonValue;
                            }
                        }
                    }
                }
            }

            if (!empty($whereParts)) {
                $sql .= ' WHERE ' . implode(' AND ', $whereParts);
            }
        }

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }
        
        $result = $this->query($sql, $params);
        return $this->fetchAll($result);
    }

    /**
     * Mengambil satu baris dari hasil query.
     */
    public function fetchAssoc(mysqli_result $result): ?array
    {
        return $result->fetch_assoc();
    }

    /**
     * Mengambil semua baris dari hasil query.
     */
    public function fetchAll(mysqli_result $result): array
    {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Memasukkan data baru ke tabel. Bisa satu baris atau banyak.
     * Contoh: $db->insert('siswa', ['nama' => 'Budi', 'nis' => '123']);
     */
    public function insert(string $table, array $data): int|bool
    {
        if (empty($data)) {
            return false;
        }

        $isMulti = isset($data[0]) && is_array($data[0]);
        $dataToInsert = $isMulti ? $data : [$data];

        $columns = array_keys($dataToInsert[0]);
        $columnSql = '`' . implode('`, `', $columns) . '`';

        $rowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $sql = "INSERT INTO `$table` ($columnSql) VALUES " . implode(', ', array_fill(0, count($dataToInsert), $rowPlaceholders));

        $values = [];
        foreach ($dataToInsert as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->connection->error);
        }

        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        $affectedRows = $stmt->affected_rows;
        $lastId = $this->connection->insert_id;
        $stmt->close();

        return $affectedRows > 0 ? ($isMulti ? $affectedRows : $lastId) : false;
    }

    /**
     * Mengupdate data di tabel.
     * Contoh: $db->update('siswa', ['nama' => 'Budi Baik'], ['id' => 1]);
     */
    public function update(string $table, array $data, array $where): int
    {
        if (empty($data) || empty($where)) {
            return 0;
        }

        $setParts = [];
        $values = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "`$key` = ?";
        }
        $values = array_values($data);

        $whereParts = [];
        foreach (array_keys($where) as $key) {
            $whereParts[] = "`$key` = ?";
        }
        $values = array_merge($values, array_values($where));

        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . ' WHERE ' . implode(' AND ', $whereParts);

        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->connection->error);
        }

        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }

    /**
     * Menghapus data dari tabel.
     * Contoh: $db->delete('siswa', ['id' => 1]);
     */
    public function delete(string $table, array $where): int
    {
        if (empty($where)) {
            return 0;
        }

        $whereParts = [];
        foreach (array_keys($where) as $key) {
            $whereParts[] = "`$key` = ?";
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereParts);

        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->connection->error);
        }

        $types = str_repeat('s', count($where));
        $stmt->bind_param($types, ...array_values($where));
        $stmt->execute();

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }

    /**
     * Mengambil ID dari baris terakhir yang di-insert.
     */
    public function lastInsertId(): int
    {
        return $this->connection->insert_id;
    }

    /**
     * Memulai sebuah transaksi.
     */
    public function beginTransaction(): void
    {
        $this->connection->begin_transaction();
    }

    /**
     * Menyimpan semua perubahan dalam transaksi.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Membatalkan semua perubahan dalam transaksi.
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }
}
