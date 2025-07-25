<?php

namespace app\Backend;

use App\Helpers\ApiResponse as Response;
use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use App\Helpers\FormatHelper;
use Exception;

class AuthBE
{
    private $db;

    private $accessRules = [
        USER_ROLE_ADMIN => ['dashboard', 'students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'laporan', 'keringanan'],
        USER_ROLE_STUDENT => ['student-recap', 'update-password'],
    ];

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function checkAccess($role, $page)
    {
        if (!$role) {
            return false;
        }

        $page = strtolower(trim($page));

        if (isset($this->accessRules[$role]) && in_array($page, $this->accessRules[$role])) {
            return true;
        }

        return false;
    }

    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->db->find('users', ['username' => $username] );

            $isValid = FormatHelper::verifyPassword($password, $user['password']);
            
            if ($user && $isValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                Response::success($user, 'Login successful');
            } else {
                Response::error('Invalid credentials', 401);
            }
        } else {
            Response::error('Invalid request method', 405);
        }
    }

    public function handleLogout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            session_destroy();
            header('Location: /login.php');
            exit();
        } else {
            Response::error('Invalid request method', 405);
        }
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUser()
    {
        if ($this->isLoggedIn()) {
            $stmt = $this->db->query('SELECT * FROM users WHERE id = ' . $_SESSION['user_id']);
            $result = $this->db->fetchAssoc($stmt);

            if($result['siswa_id'] == null){
                return [
                    'user' => $result
                ];
            }
            $stmtSiswa = $this->db->query('SELECT * FROM siswa WHERE id = ' . $result['siswa_id']);
            $student = $this->db->fetchAssoc($stmtSiswa);
            return [
                'user' => $result,
                'student' => $student
            ];
        }
        return null;
    }

    public function getRole()
    {
        if ($this->isLoggedIn()) {
            return $this->getUser()['user']['role'];
        }
        return null;
    }

    public function changePassword()
    {
        $oldPassword = $_POST['password-lama'] ?? null;
        $newPassword = $_POST['password-baru'] ?? null;
        $confirmation = $_POST['konfirmasi-password-baru'] ?? null;

        try{
            $this->db->beginTransaction();
            if(empty($oldPassword) || empty($newPassword) || empty($confirmation)){
                throw new Exception("Masukan seluruh input untuk mengubah password"); 
            }

            if($newPassword !== $confirmation){
                throw new Exception("Password yang baru tidak sama"); 
            }

            $user = $this->getUser();
            $passwordQuery = "SELECT u.password FROM users u WHERE u.id = ?";
            $password = $this->db->fetchAssoc($this->db->query($passwordQuery, [$user['user']['id']]))['password'];
            
            $validateOldPassword = FormatHelper::verifyPassword($oldPassword, $password);
            if(!$validateOldPassword){
                throw new Exception("Password Lama tidak sesuai"); 
            }

            $update = $this->db->update('users', ['password' => FormatHelper::hashPassword($newPassword)], ['id' => $user['user']['id']]);
            if(!$update){
                throw new Exception("Error mengupdate password");
            }
            $this->db->commit();
            $_SESSION['msg'] = "Password berhasil diperbarui.";
        } catch (\Exception $e){
            $_SESSION['msg'] = "Terjadi kesalahan saat mengubah password: ".$e->getMessage();
        } finally {
            header("Location: /student-recap");
        }
        
    }

    protected function generatePasswordOTP(String $username): bool|array
    {
        $user = $this->db->find('users', [
            'username' => $username
        ]);

        $siswa = $this->db->find('siswa', [
            'id' => $user['siswa_id']
        ]);

        if(count($user) < 0){
            return false;
        }
        $sixDigitRandomNumber = random_int(0, 999999);

        $token = sprintf('%06d', $sixDigitRandomNumber);

        $now = Call::timestamp();

        $updateQuery = $this->db->update('users', ['otp_code' => $token, 'otp_created' => $now], ['id' => $user['id']]);
        if(!$updateQuery){
            return false;
        }
        return [
            $token,
            $siswa['no_hp_ortu']
        ];
    }

    public function sendOTP()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 404);
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        $result = $this->generatePasswordOTP($data['virtual_account']);

        if(!$result){
            return ApiResponse::error("Failed to generate OTP", 401);
        }

        list($token, $phone) = $result;

        $msg[] = [
            'target' => $phone,
            'message' => "Kode OTP untuk Reset Password Sistem Keuangan Nusaputera adalah sebagai berikut:\n*$token*\nKode OTP ini hanya berlaku selama 15 menit. Jangan bagikan kode ini kepada siapapun.",
            'delay' => '1'
        ];

        Fonnte::sendMessage(['data' => json_encode($msg)]);

        return ApiResponse::success($token, 'Berhasil mengirimkan OTP');
    }

    public function verifyOTP()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 404);
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        $user = $this->db->find('users', ['username' => $data['virtual_account'], 'otp_code' => $data['otp']]);

        if( empty($user) || $data['otp'] != $user['otp_code']){
            return ApiResponse::error('Invalid OTP Token', 401);
        }

        return ApiResponse::success(null, 'OTP Telah Terverifikasi');
    }
    
    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ApiResponse::error('Invalid API endpoint', 404);
        }

        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        try{
            $this->db->beginTransaction();

            $user = $this->db->find('users', ['username' => $data['virtual_account'], 'otp_code' => $data['otp']]);

            if( empty($user) || $data['otp'] != $user['otp_code']){
                throw new Exception('Invalid OTP Token');
            }
    
            $updateUser = $this->db->update('users', ['otp_code' => null, 'otp_created' => null], ['id' => $user['id']]);
            
            if(!$updateUser){
                throw new Exception('Failed to remove token from database');
            }
            $password = FormatHelper::hashPassword($data['password']);
            $this->db->update('users', ['password' => $password], ['id' => $user['id']]);
    
            $this->db->commit();
            return ApiResponse::success(null, 'Password telah diupdate!');
        
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update Password: '.$e->getMessage(), 500);
        }
    }

    public function aktEncryptLogin()
    {
        $user = $this->getUser();
        $systemCode = "PBN";

        $string = $user['user']['username']."|-|$systemCode";
        $key = $_ENV['NUSPUT_SECRET_KEY'];
        $method = $_ENV['ENCRYPTION_METHOD'];

        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);
        $encrypted_with_iv = base64_encode($iv . $encrypted);

        $base_url = rtrim($_ENV['ACCOUNTING_SYSTEM_URL'], '/');
        $url = "$base_url/secret-login.php?secret=".$encrypted_with_iv;
        header('Location: '.$url, true);
    }

    public function aktDecryptLogin()
    {
        if(!isset($_GET['secret'])){
            return ApiResponse::error("Page Not Found", 404);
        }

        $secret = $_GET['secret'];
        $key = $_ENV['NUSPUT_SECRET_KEY'];
        $method = $_ENV['ENCRYPTION_METHOD'];
        
        $data = base64_decode($secret);

        if ($data === false) {
            return ApiResponse::error('Invalid base64 data');
        }

        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        $string = openssl_decrypt($cipherText, $method, $key, 0, $iv);

        if (!$string) {
            return ApiResponse::error("Failed to Decrypt Details");
        }

        $decrypted = explode('|-|', $string);
        if (count($decrypted) != 2) {
            return ApiResponse::error("Invalid Details format");
        }

        [$user, $bill] = $decrypted;

        $user = $this->db->find('users', [
            'username' => $user
        ]);

        if(!isset($user)){
            return ApiResponse::error("Login Failed");
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if($user['role'] == 'admin'){
            header("Location: /dashboard");
        } else {
            header("Location: /login.php");
        }
    }
}