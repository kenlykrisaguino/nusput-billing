<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';

use App\Database\Database;
use App\Helpers\ApiResponse;
use App\Helpers\FormatHelper;
use App\Web\Web; 
use Symfony\Component\Dotenv\Dotenv;


class App
{
    private static ?App $instance = null;
    private Database $database;
    private Web $web;


    private function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/.env');

        $this->database = new Database();
        $this->web = new Web($this); 

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

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getWeb(): Web
    {
        return $this->web;
    }

    public function getApiResponse(): ApiResponse
    {
      return new ApiResponse();
    }

    public function getFormatHelper(): FormatHelper
    {
      return new FormatHelper(); 
    }

     public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']); 
    }

    public function run(): void
    {
        $this->web->handleRequest();
    }
}

require_once __DIR__ . '/vendor/autoload.php';

$app = App::getInstance();
$app->run();