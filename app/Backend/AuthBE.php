<?php

namespace app\Backend;

use App\Helpers\ApiResponse as Response;
use App\Helpers\ApiResponse;
use App\Helpers\Call;
use App\Helpers\Fonnte;
use Exception;

class AuthBE
{
    private $db;

    private $accessRules = [
        USER_ROLE_SUPERADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'logs'],
        USER_ROLE_ADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'logs'],
        USER_ROLE_STUDENT => ['dashboard', 'update-password'],
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
            $password = md5($_POST['password']) ?? '';

            $result = $this->db->query("SELECT u.* FROM users u WHERE username = '$username' AND password = '$password'");
            $user = $this->db->fetchAssoc($result);
            if ($user) {
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
            $stmtSiswa = $this->db->query('SELECT * FROM siswa WHERE id = ' . $stmt['siswa_id']);
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
            $passwordQuery = "SELECT c.password FROM user_class c WHERE c.user_id = ? AND date_left IS NULL";
            $password = $this->db->fetchAssoc($this->db->query($passwordQuery, [$user['id']]))['password'];
            
            $CryptedOldPass = md5($oldPassword);
            if($CryptedOldPass != $password){
                throw new Exception("Password Lama tidak sesuai"); 
            }

            $update = $this->db->update('user_class', ['password' => md5($newPassword)], ['user_id' => $user['id'], 'date_left' => null]);
            if(!$update){
                throw new Exception("Error mengupdate password");
            }
            $this->db->commit();
            $_SESSION['msg'] = "Password berhasil diperbarui.";
        } catch (\Exception $e){
            $_SESSION['msg'] = "Terjadi kesalahan saat mengubah password: ".$e->getMessage();
        } finally {
            header("Location: /dashboard");
        }
        
    }

    protected function generatePasswordOTP(String $va): bool|array
    {
        $userQuery = "SELECT u.* FROM user_class c JOIN users u ON c.user_id = u.id WHERE c.virtual_account = ? AND c.date_left IS NULL";
        $user = $this->db->fetchAssoc($this->db->query($userQuery, [$va]));

        if(count($user) < 0){
            return false;
        }
        $six_digit_random_number = random_int(0, 999999);

        $token = sprintf('%06d', $six_digit_random_number);

        $now = Call::timestamp();

        $updateQuery = $this->db->update('users', ['otp_code' => $token, 'otp_created' => $now], ['id' => $user['id']]);
        if(!$updateQuery){
            return false;
        }
        return [
            $token,
            $user['parent_phone']
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

        $userQuery = "SELECT u.otp_code, u.id FROM user_class c JOIN users u ON c.user_id = u.id WHERE c.virtual_account = ? AND c.date_left IS NULL";
        $user = $this->db->fetchAssoc($this->db->query($userQuery, [$data['virtual_account']]));

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

            $userQuery = "SELECT u.otp_code, u.id FROM user_class c JOIN users u ON c.user_id = u.id WHERE c.virtual_account = ? AND c.date_left IS NULL";
            $user = $this->db->fetchAssoc($this->db->query($userQuery, [$data['virtual_account']]));
    
            if( empty($user) || $data['otp'] != $user['otp_code']){
                throw new Exception('Invalid OTP Token');
            }
    
            $updateUser = $this->db->update('users', ['otp_code' => null, 'otp_created' => null], ['id' => $user['id']]);
            
            if(!$updateUser){
                throw new Exception('Failed to remove token from database');
            }
            $password = md5($data['password']);
            $this->db->update('user_class', ['password' => $password], ['virtual_account' => $data['virtual_account']]);
    
            $this->db->commit();
            return ApiResponse::success(null, 'Password telah diupdate!');
        
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update Password: '.$e->getMessage(), 500);
        }
    }
}