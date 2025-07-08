<?php
// app/App.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';

use App\Router;
use App\Database\Database;
use App\Midtrans\Midtrans;
use App\Helpers\ApiResponse;
use App\Helpers\FormatHelper;
use Symfony\Component\Dotenv\Dotenv;

// Import semua kelas BE
use App\Backend\AuthBE;
use App\Backend\StudentBE;
use App\Backend\PaymentBE;
use App\Backend\BillBE;
use App\Backend\ClassBE;
use App\Backend\FilterBE;
use App\Backend\RecapBE;
use App\Backend\JournalBE;
use App\Backend\DashboardBE;

class App
{
    private static ?App $instance = null;
    private Database $database;
    private Router $router;
    private Midtrans $midtrans;

    // Properti untuk menyimpan instance service (cache)
    private $authBE = null;
    private $studentBE = null;
    private $paymentBE = null;
    private $billBE = null;
    private $classBE = null;
    private $filterBE = null;
    private $recapBE = null;
    private $journalBE = null;
    private $dashboardBE = null;
    private $apiResponse = null;
    private $formatHelper = null;


    private function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/.env');

        $this->database = new Database();
        $this->router = new Router($this);
        $this->midtrans = new Midtrans();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new App();
        }
        return self::$instance;
    }

    public function run(): void
    {
        $this->router->handleRequest();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getMidtrans(): Midtrans
    {
        return $this->midtrans;
    }

    public function getRouter(): Router 
    {
        return $this->router;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    // --- SERVICE GETTERS (Lazy Loading) ---

    public function AuthBE() {
        if ($this->authBE === null) {
            $this->authBE = new AuthBE($this->database);
        }
        return $this->authBE;
    }
    
    public function StudentBE() {
        if ($this->studentBE === null) {
            $this->studentBE = new StudentBE($this->database, $this->ClassBE(), $this->BillBE(), $this->midtrans);
        }
        return $this->studentBE;
    }
    
    public function PaymentBE() {
        if ($this->paymentBE === null) {
            $this->paymentBE = new PaymentBE($this->database, $this->midtrans);
        }
        return $this->paymentBE;
    }
    
    public function BillBE() {
        if ($this->billBE === null) {
            $this->billBE = new BillBE($this->database, $this->midtrans);
        }
        return $this->billBE;
    }
    
    public function ClassBE() {
        if ($this->classBE === null) {
            $this->classBE = new ClassBE($this->database);
        }
        return $this->classBE;
    }

    public function FilterBE() {
        if ($this->filterBE === null) {
            $this->filterBE = new FilterBE($this->database);
        }
        return $this->filterBE;
    }

    public function RecapBE() {
        if ($this->recapBE === null) {
            $this->recapBE = new RecapBE($this->database);
        }
        return $this->recapBE;
    }
    
    public function JournalBE() {
        if ($this->journalBE === null) {
            $this->journalBE = new JournalBE($this->database);
        }
        return $this->journalBE;
    }
    public function DashboardBE() {
        if ($this->dashboardBE === null) {
            $this->dashboardBE = new DashboardBE($this->database);
        }
        return $this->dashboardBE;
    }

    // --- HELPER GETTERS ---
    
    public function getApiResponse(): ApiResponse
    {
      if ($this->apiResponse === null) {
          $this->apiResponse = new ApiResponse();
      }
      return $this->apiResponse;
    }

    public function getFormatHelper(): FormatHelper
    {
      if ($this->formatHelper === null) {
          $this->formatHelper = new FormatHelper();
      }
      return $this->formatHelper;
    }
}