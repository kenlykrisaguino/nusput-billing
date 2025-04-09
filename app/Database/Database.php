<?php

namespace App\Database;

use Exception;
use mysqli;
use mysqli_result;

class Database
{
    private mysqli $connection;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';  

        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function query(string $sql, array $params = []): mysqli_result|bool
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        if ($params) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i'; 
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function beginTransaction(): void
    {
        $this->connection->begin_transaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

     public function fetchAssoc(mysqli_result $result): array|null
    {
        return $result->fetch_assoc();
    }

    public function fetchAll(mysqli_result $result): array
    {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insert(string $table, array $data): array|bool
    {
        if (empty($data)) {
            throw new Exception("insert() called with empty data");
        }
    
        $isMany = isset($data[0]) && is_array($data[0]);
    
        if ($isMany) {
            $columns = array_keys($data[0]);
            $placeholders = [];
            $values = [];
    
            foreach ($data as $row) {
                if (count($row) !== count($columns)) {
                    throw new Exception("Mismatch in column count");
                }
                $placeholders[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
                foreach ($row as $val) {
                    $values[] = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
                }
            }
    
            $sql = "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES " . implode(', ', $placeholders);
            $this->query($sql, $values);
    
            $lastId = $this->connection->insert_id;
            $count = count($data);
            $idList = range($lastId, $lastId + $count - 1);
            $idPlaceholders = implode(', ', array_fill(0, $count, '?'));
            $result = $this->query("SELECT * FROM `$table` WHERE id IN ($idPlaceholders)", $idList);
            $rows = $this->fetchAll($result);
    
            return array_map([$this, 'prettifyJsonFields'], $rows);
        } else {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $values = array_map(function ($val) {
                return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
            }, array_values($data));
    
            $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
            $this->query($sql, $values);
    
            $insertId = $this->connection->insert_id;
            $result = $this->query("SELECT * FROM `$table` WHERE id = ?", [$insertId]);
            $row = $this->fetchAssoc($result);
    
            return $this->prettifyJsonFields($row);
        }
    }
    

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    private function prettifyJsonFields(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && $this->isJson($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$key] = $decoded;
                }
            }
        }
        return $row;
    }

    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}