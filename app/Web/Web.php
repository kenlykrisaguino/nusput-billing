<?php
namespace App\Web;

use App;
use App\Helpers\ApiResponse;
use App\Database\Database;

use App\Backend\StudentBE;
use App\Backend\PaymentBE;
use App\Backend\BillBE;
use App\Backend\FilterBE;
use App\Backend\RecapBE;
use App\Backend\JournalBE;
use App\Backend\LogBE;
use App\Backend\FilterFE;

class Web
{
    private $app;
    private Database $db;
    private $studentBE;
    private $paymentBE;
    private $billBE;
    private $recapBE;
    private $journalBE;
    private $logBE;
    private $filterBE;

    private $accessRules = [
        USER_ROLE_SUPERADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan', 'logs'],
        USER_ROLE_ADMIN => ['students', 'pembayaran', 'tagihan', 'rekap', 'penjurnalan'],
        USER_ROLE_STUDENT => ['dashboard'],
    ];

    private $defaultPages = [
        USER_ROLE_SUPERADMIN => '/students',
        USER_ROLE_ADMIN => '/students',
        USER_ROLE_STUDENT => '/dashboard',
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
        $db = $this->app->getDatabase();
        $this->db = $db;
        $this->studentBE = new StudentBE($db);
        $this->paymentBE = new PaymentBE($db);
        $this->billBE = new BillBE($db);
        $this->recapBE = new RecapBE($db);
        $this->journalBE = new JournalBE($db);
        $this->logBE = new LogBE($db);
        $this->filterBE = new FilterBE($db);
    }

    public function render($page, $data = [])
    {
        $web = $this;
        $data['web'] = $web;
        ob_start();
        include __DIR__ . '/../views/main_template.php';
        $content = ob_get_clean();
        echo $content;
    }
    public function loadView($view, $data = [])
    {
        $web = $this;
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            $data['web'] = $web;
            extract($data);
            include $viewPath;
        } else {
            echo 'Error: View not found: ' . htmlspecialchars($view);
        }
    }

    public function handleRequest()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestUri = $_SERVER['REQUEST_URI'];
        $path = parse_url($requestUri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        $page = $segments[0] ?? 'dashboard';

        if ($page === 'api') {
            $this->handleApiRequest($segments);
            return;
        }
        if ($page === 'exports') {
            $this->handleExportRequest($segments);
            return;
        }
        if ($page === 'format') {
            $this->getFormat();
            return;
        }

        if ($page !== 'login' && !$this->app->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }

        $user = $this->getUser();
        if (!$this->checkAccess($user['role'], $page)) {
            http_response_code(403);
            $this->render('403');
            exit();
        }
        $this->renderPage($page);
    }

    protected function checkAccess($role, $page)
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

    protected function renderPage($page)
    {
        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

        $viewFile = __DIR__ . "/../views/$page.php";

        if (file_exists($viewFile)) {
            $this->render($page, []);
        } else {
            http_response_code(404);
            $this->render('404');
        }
    }

    protected function handleApiRequest(array $segments)
    {
        array_shift($segments);

        $apiEndpoint = $segments[0] ?? null;

        switch ($apiEndpoint) {
            case 'login':
                $this->handleLogin();
                break;
            case 'logout':
                $this->handleLogout();
                break;
            case 'create-bills':
                $this->billBE->createBills();
                break;
            case 'check-bills':
                $this->billBE->checkBills();
                break;
            case 'filter-classes':
                $this->filterBE->getClassDetails();
                break;

            default:
                ApiResponse::error('Invalid API endpoint', 404);
                break;
        }
    }
    protected function handleExportRequest(array $segments)
    {
        array_shift($segments);

        $exportEndpoint = $segments[0] ?? null;

        switch ($exportEndpoint) {
            case 'students':
                $this->studentBE->exportStudentXLSX();
                $this->back();
                break;
        }
    }

    protected function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = md5($_POST['password']) ?? '';

            $db = $this->app->getDatabase();
            $result = $db->query("SELECT u.* FROM user_class c JOIN users u ON u.id = c.user_id WHERE virtual_account = '$username' AND password = '$password'");
            $user = $db->fetchAll($result);
            if ($user) {
                $_SESSION['user_id'] = $user[0]['id'];
                ApiResponse::success($user[0], 'Login successful');
            } else {
                ApiResponse::error('Invalid credentials', 401);
            }
        } else {
            ApiResponse::error('Invalid request method', 405);
        }
    }

    protected function handleLogout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            session_destroy();
            header('Location: /login.php');
            exit();
        } else {
            ApiResponse::error('Invalid request method', 405);
        }
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUser()
    {
        if ($this->isLoggedIn()) {
            $db = $this->app->getDatabase();
            $result = $db->query('SELECT * FROM users WHERE id = ' . $_SESSION['user_id']);
            return $db->fetchAll($result)[0];
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

    public function getStudents()
    {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? [];

        $params = [
            'search' => $search,
            'filter' => $filter,
        ];

        return $this->studentBE->getStudents($params);
    }

    public function getPayments()
    {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? [];

        $params = [
            'search' => $search,
            'filter' => $filter,
        ];
        return $this->paymentBE->getPayments($params);
    }

    public function getBills()
    {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? [];

        $params = [
            'search' => $search,
            'filter' => $filter,
        ];
        return $this->billBE->getBills($params);
    }

    public function getRecaps()
    {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? [];

        $params = [
            'search' => $search,
            'filter' => $filter,
        ];

        return $this->recapBE->getRecaps($params);
    }

    public function getJournals()
    {
        return $this->journalBE->getJournals();
    }

    public function getLogs()
    {
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? [];

        $params = [
            'search' => $search,
            'filter' => $filter,
        ];
        return $this->logBE->getLogs($params);
    }

    public function getFormat()
    {
        $type = $_GET['type'] ?? '';

        switch ($type){
            case 'student':
                $this->studentBE->getStudentFormatXLSX();
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            default:
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
        }
    }

    public function back()
    {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
}
