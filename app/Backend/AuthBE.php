<?php

namespace app\Backend;

use App\Helpers\ApiResponse as Response;
use Exception;

class AuthBE
{
    private $db;

    private $accessRules = [
        USER_ROLE_SUPERADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'logs'],
        USER_ROLE_ADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan'],
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

            $result = $this->db->query("SELECT u.* FROM user_class c JOIN users u ON u.id = c.user_id WHERE virtual_account = '$username' AND password = '$password'");
            $user = $this->db->fetchAssoc($result);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
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
            $result = $this->db->query('SELECT * FROM users WHERE id = ' . $_SESSION['user_id']);
            return $this->db->fetchAssoc($result);
        }
        return null;
    }

    public function getRole()
    {
        if ($this->isLoggedIn()) {
            return $this->getUser()['role'];
        }
        return null;
    }

    public function changePassword()
    {
        $oldPassword = $_POST['password-lama'] ?? null;
        $newPassword = $_POST['password-baru'] ?? null;
        $confirmation = $_POST['konfirmasi-password-baru'] ?? null;

        try{
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

            $_SESSION['msg'] = "Password berhasil diperbarui.";
        } catch (\Exception $e){
            $_SESSION['msg'] = "Terjadi kesalahan saat mengubah password: ".$e->getMessage();
        } finally {
            header("Location: /dashboard");
        }
        
    }

    protected function generatePasswordOTP()
    {

    }

    public function sendOTP()
    {
        
    }
    public function verifyOTP()
    {

    }
    public function updatePassword()
    {

    }
}