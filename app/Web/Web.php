<?php
namespace App\Web;

use App;
use App\Helpers\ApiResponse;
use App\Database\Database;

use App\Backend\AuthBE;
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
    private $authBE;
    private $studentBE;
    private $paymentBE;
    private $billBE;
    private $recapBE;
    private $journalBE;
    private $logBE;
    private $filterBE;

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
        $this->authBE = new AuthBE($db);
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

        if ($page === 'invoice') {
            $viewFile = __DIR__ . "/../views/invoice.php";
            $web = $this;
            $data['web'] = $web;
            extract($data);
            include $viewFile;
            return;
        }

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

        
        if($page == 'update-password'){
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->authBE->changePassword();
                exit();
            }
        }

        $user = $this->authBE->getUser();
        if (!$this->authBE->checkAccess($user['role'], $page)) {
            http_response_code(403);
            $this->render('403');
            exit();
        }
        
        if($page == 'tagihan'){
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->billBE->updateLateFee();
                exit();
            }
        }
        
        $this->renderPage($page);
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
                $this->authBE->handleLogin();
                break;
            case 'logout':
                $this->authBE->handleLogout();
                break;
            case 'create-bills':
                $this->billBE->createBills();
                break;
            case 'check-bills':
                $this->billBE->checkBills();
                break;
            case 'manual-create-bills':
                $this->billBE->manualCreateBills();
                break;
            case 'manual-check-bills':
                $this->billBE->manualCheckBills();
                break;
            case 'filter-classes':
                $this->filterBE->getClassDetails();
                break;
            case 'notify-bills':
                $this->billBE->notifyBills();
                break;
            case 'get-student-fees':
                $this->studentBE->getStudentFees();
                break;
            case 'get-fee-categories':
                $this->billBE->getPublicFeeCategories();
                break;
            case 'upload-student':
                $this->studentBE->formCreateStudent();
                break;
            case 'upload-students-bulk':
                $this->studentBE->importStudentsFromXLSX();
                break;
            case 'get-student-detail':
                $this->studentBE->getStudentDetail();
                break;
            case 'update-student':
                $this->studentBE->updateStudent();
                break;
            case 'import-payment':
                $this->paymentBE->importPaymentsFromXLSX();
                break;
            case 'export-invoice':
                $this->paymentBE->exportPublicInvoice($segments);
                break;
            case 'send-otp':
                $this->authBE->sendOTP($segments);
                break;
            case 'verify-otp':
                $this->authBE->verifyOTP($segments);
                break;
            case 'update-password':
                $this->authBE->updatePassword($segments);
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
            case 'bills':
                $this->billBE->exportBillXLSX();
                $this->back();
                break;
        }
    }

    public function getFormat()
    {
        $type = $_GET['type'] ?? '';

        switch ($type){
            case 'student':
                $this->studentBE->getStudentFormatXLSX();
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            case 'payment':
                $this->paymentBE->getPaymentFormatXLSX();
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
