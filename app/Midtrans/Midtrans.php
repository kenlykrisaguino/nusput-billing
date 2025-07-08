<?php

namespace App\Midtrans;

use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Notification;
use Midtrans\Transaction;

class Midtrans
{
    /**
     * Konstruktor untuk menginisialisasi konfigurasi Midtrans.
     * @throws \Exception Jika file konfigurasi tidak ditemukan atau tidak valid.
     */
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/midtrans.php';

        $requiredKeys = [
            'MIDTRANS_SERVER_KEY',
            'MIDTRANS_IS_PRODUCTION',
            'MIDTRANS_IS_SANITIZED',
            'MIDTRANS_IS_3DS'
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \Exception("Missing Midtrans configuration key: {$key} in {$configPath}");
            }
        }

        Config::$serverKey = $config['MIDTRANS_SERVER_KEY'];
        Config::$isProduction = $config['MIDTRANS_IS_PRODUCTION'] == 'true';
        Config::$isSanitized = $config['MIDTRANS_IS_SANITIZED'] == 'true';
        Config::$is3ds = $config['MIDTRANS_IS_3DS'] == 'true';
    }

    /**
     * Menginisialisasi dan mengembalikan objek CoreApi untuk berbagai operasi API.
     *
     * @param array $params Parameter transaksi untuk Core API Charge.
     * @return object Respons dari Midtrans Core API Charge.
     * @throws \Exception Jika ada masalah saat melakukan Core API Charge.
     */
    public function charge(array $params): object
    {
        try {
            return CoreApi::charge($params);
        } catch (\Exception $e) {
            throw new \Exception("Gagal melakukan Core API Charge: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Mendapatkan objek Notification untuk menangani callback dari Midtrans.
     * Contoh:
     * $midtrans = new Midtrans();
     * $notification = $midtrans->getNotificationHandler();
     * $transactionStatus = $notification->transaction_status;
     *
     * @return \Midtrans\Notification
     */
    public function getNotificationHandler(): \Midtrans\Notification
    {
        return new Notification();
    }

    /**
     * Membatalkan transaksi Midtrans berdasarkan Order ID.
     * Contoh:
     * $midtrans = new Midtrans();
     * $cancelResponse = $midtrans->cancelTransaction('YOUR_ORDER_ID');
     *
     * @param string $orderId Order ID transaksi yang akan dibatalkan.
     * @return string Respons status code dari Midtrans API setelah pembatalan.
     * @throws \Exception Jika ada masalah saat membatalkan transaksi.
     */
    public function cancelTransaction(string $orderId): string
    {
        try {
            return Transaction::cancel($orderId);
        } catch (\Exception $e) {
            throw new \Exception("Gagal membatalkan transaksi dengan Order ID '{$orderId}': " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Mengkadaluarsakan (expire) transaksi Midtrans berdasarkan Order ID.
     * Ini digunakan untuk secara manual mengkadaluarsakan transaksi yang masih pending.
     * Contoh:
     * $midtrans = new Midtrans();
     * $expireResponse = $midtrans->expireTransaction('YOUR_ORDER_ID');
     *
     * @param string $orderId Order ID transaksi yang akan dikadaluarsakan.
     * @return object Respons dari Midtrans API setelah pengkadaluarsaan.
     * @throws \Exception Jika ada masalah saat mengkadaluarsakan transaksi.
     */
    public function expireTransaction(string $orderId)
    {
        try {
            return Transaction::expire($orderId);
        } catch (\Exception $e) {
            throw new \Exception("Gagal mengkadaluarsakan transaksi dengan Order ID '{$orderId}': " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function recreateTransaction(string $oldOrderId, array $payload)
    {
        try {
            Transaction::cancel($oldOrderId);
            return CoreApi::charge($payload);
        } catch (\Exception $e) {
            throw new \Exception("Gagal membuat ulang transaksi transaksi dengan Order ID '{$oldOrderId}': " . $e->getMessage(), $e->getCode(), $e);
        }
        $this->cancelTransaction($oldOrderId);
    }


}