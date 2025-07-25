<?php
namespace App\Routes;

use App\Router;

class WebRouter
{
    private $app;
    private $authBE;
    private $router;

    public function __construct($app, Router $routerInstance)
    {
        $this->app = $app;
        $this->router = $routerInstance;
        $this->authBE = $app->AuthBE();
    }

    public function handle(array $segments)
    {
        $page = $segments[0] ?? '';
        
        if (empty($page)) {
            $userRole = $_SESSION['role'] ?? 'guest';
            $defaultPages = [
                'admin' => 'dashboard',
                'siswa' => 'student-recap',
            ];
            $page = $defaultPages[$userRole] ?? 'login.php';
            header("Location: /{$page}");
            exit;
        }

        if ($page === 'secret-login') {
            return $this->app->AuthBE()->aktDecryptLogin();
        }

        if ($page === 'invoice') {
            $this->router->loadView('invoice');
            return;
        }
        
        if ($page !== 'login' && !$this->app->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }

        if ($page == 'update-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->app->AuthBE()->changePassword();
            exit();
        }

        if ($page == 'tagihan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->app->BillBE()->updateLateFee();
            exit();
        }

        $user = $this->authBE->getUser();
        if (!$this->authBE->checkAccess($user['user']['role'], $page)) {
            $this->router->render('403');
            exit();
        }

        $this->renderPage($page);
    }
    
    private function renderPage($page)
    {
        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
        $viewFile = __DIR__ . '/../views/' . $page . '.php';

        if (file_exists($viewFile)) {
            $this->router->render($page, []);
        } else {
            $this->router->render('404');
        }
    }
}