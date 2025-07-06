<?php
namespace App;

use App;
use App\Routes\ApiRouter;
use App\Routes\WebRouter;

class Router
{
    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function handleRequest()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $path = parse_url($requestUri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        $mainRoute = $segments[0] ?? '';

        switch ($mainRoute) {
            case 'api':
                $apiRouter = new ApiRouter($this->app);
                array_shift($segments);
                $apiRouter->handle($segments);
                break;
            
            case 'exports':
                $this->handleExportRequest($segments);
                break;
            
            case 'format':
                $this->getFormat();
                break;

            case 'route-to-akt':
                $this->app->AuthBE()->aktEncryptLogin();
                break;

            default:
                $webRouter = new WebRouter($this->app, $this);
                $webRouter->handle($segments);
                break;
        }
    }

    // --- Logika View ---
    public function render($page, $data = [])
    {
        $data['router'] = $this;
        $data['app'] = $this->app;
        ob_start();
        include __DIR__ . '/views/main_template.php';
        echo ob_get_clean();
    }

    public function loadView($view, $data = [])
    {
        $viewPath = __DIR__ . '/views/' . $view . '.php';
        if (file_exists($viewPath)) {
            $data['router'] = $this;
            $data['app'] = $this->app;
            extract($data);
            include $viewPath;
        } else {
            echo 'Error: View not found: ' . htmlspecialchars($view);
        }
    }

    // --- Logika Lainnya ---
    protected function handleExportRequest(array $segments)
    {
        $exportEndpoint = $segments[1] ?? null;
        switch ($exportEndpoint) {
            case 'students': $this->app->StudentBE()->exportStudentXLSX(); break;
            case 'bills': $this->app->BillBE()->exportBillXLSX(); break;
            case 'journals': $this->app->JournalBE()->getJournals(null, false, true); break;
        }
        $this->back();
    }

    public function getFormat()
    {
        $type = $_GET['type'] ?? '';
        switch ($type){
            case 'student': $this->app->StudentBE()->getStudentFormatXLSX(); break;
            case 'payment': $this->app->PaymentBE()->getPaymentFormatXLSX(); break;
        }
        $this->back();
    }

    public function back()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}