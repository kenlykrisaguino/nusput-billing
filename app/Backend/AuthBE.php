<?php

namespace app\Backend;

use App\Helpers\ApiResponse as Response;

class AuthBE
{
    private $db;

    private $accessRules = [
        USER_ROLE_SUPERADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'logs'],
        USER_ROLE_ADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan'],
        USER_ROLE_STUDENT => ['dashboard'],
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
}