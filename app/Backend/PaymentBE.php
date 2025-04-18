<?php

namespace app\Backend;

class PaymentBE
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getPayments($params = [])
    {        
        $query = "SELECT p.id, u.name AS student, p.trx_amount, p.trx_timestamp, p.details
                FROM payments AS p INNER JOIN users AS u ON p.user_id = u.id
                WHERE TRUE";

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query .= " AND u.name LIKE '%$search%";
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
}