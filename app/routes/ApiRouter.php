<?php
namespace App\Routes;

use App\Helpers\ApiResponse;
use App\Helpers\FormatHelper;

class ApiRouter
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function handle(array $segments)
    {
        $authBE = $this->app->AuthBE();
        $studentBE = $this->app->StudentBE();
        $billBE = $this->app->BillBE();
        $paymentBE = $this->app->PaymentBE();
        $classBE = $this->app->ClassBE();
        $filterBE = $this->app->FilterBE();
        $journalBE = $this->app->JournalBE();
        $reductionBE = $this->app->ReductionBE();

        $endpoint = array_shift($segments) ?? null;

        switch ($endpoint) {
            case 'pw':
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'pw' => FormatHelper::hashPassword($_GET['p'] ?? '')
                ]);
                exit;
                break;
            case 'journal-data':
                ApiResponse::success($journalBE->getJournals());
                break;
            case 'midtrans-callback':
                ApiResponse::success($paymentBE->midtransCallback());
                break;
            case 'filter-siswa':
                ApiResponse::success($studentBE->getStudentFilter());
                break;
            case 'jenjang':
                ApiResponse::success($classBE->getAllJenjang());
                break;
            case 'tingkat':
                ApiResponse::success($classBE->getTingkatByJenjang($_GET['jenjang_id'] ?? null));
                break;
            case 'kelas':
                ApiResponse::success($classBE->getKelasByTingkat($_GET['tingkat_id'] ?? null));
                break;
            case 'spp-tarif':
                ApiResponse::success($classBE->getTarifSPP($_GET['jenjang_id'] ?? null, $_GET['tingkat_id'] ?? null, $_GET['kelas_id'] ?? null));
                break;
            case 'students-list':
                ApiResponse::success($studentBE->getStudents());
                break;
            case 'fee-categories':
                ApiResponse::success($studentBE->getFeeCategories());
                break;
            case 'student-detail':
                $studentId = $segments[0] ?? null;
                $detail = $studentBE->getStudentDetailById($studentId);
                if ($detail) {
                    ApiResponse::success($detail);
                } else {
                    ApiResponse::error('Siswa tidak ditemukan.', 404);
                }
                break;
            case 'student-update':
                $studentId = $segments[0] ?? null;
                $data = json_decode(file_get_contents('php://input'), true);
                $studentBE->updateStudentData($studentId, $data);
                break;
            case 'get-fee-data':
                $data = json_decode(file_get_contents('php://input'), true);
                $billBE->getFeeDetails($data);
            case 'update-fee-data':
                $data = json_decode(file_get_contents('php://input'), true);
                $billBE->updateLateFee($data);
            case 'fee-categories':
                ApiResponse::success($studentBE->getFeeCategories());
                break;
            case 'student-fees':
                $siswaId = $segments[0] ?? null;
                $month = $_GET['month'] ?? null;
                $year = $_GET['year'] ?? null;
                $fees = $studentBE->getStudentFeesByPeriod($siswaId, $month, $year);
                ApiResponse::success($fees);
                break;
            case 'students-delete':
                $studentId = $segments[0] ?? null;
                $studentBE->deleteStudent($studentId);
                break;
            case 'student-fees-update':
                $studentId = $segments[0] ?? null;
                $studentBE->updateStudentFees($studentId);
                break;
            case 'level-create':
                $classBE->createLevel();
                break;
            case 'grade-create':
                $classBE->createGrade();
                break;
            case 'class-create':
                $classBE->createClass();
                break;
            case 'tariff-create':
                $classBE->createTariff();
                break;
            case 'tariff-detail':
                $tariffId = $segments[0] ?? null;
                $detail = $classBE->getTariffDetailById($tariffId);
                if ($detail) {
                    ApiResponse::success($detail);
                } else {
                    ApiResponse::error('Tarif tidak ditemukan.', 404);
                }
                break;
            
            case 'tariff-update':
                $tariffId = $segments[0] ?? null;
                $data = json_decode(file_get_contents('php://input'), true);
                $classBE->updateTariffNominal($tariffId, $data['nominal'] ?? null);
                break;
            case 'login':
                $authBE->handleLogin();
                break;
            case 'logout':
                $authBE->handleLogout();
                break;
            case 'create-bills':
                $billBE->createBills();
                break;
            case 'check-bills':
                $billBE->checkBills();
                break;
            case 'manual-create-bills':
                $billBE->manualCreateBills();
                break;
            case 'manual-check-bills':
                $billBE->manualCheckBills();
                break;
            case 'filter-classes':
                $filterBE->getClassDetails();
                break;
            case 'notify-bills':
                $billBE->notifyBills();
                break;
            case 'get-student-fees':
                $studentBE->getStudentFees();
                break;
            case 'get-fee-categories':
                $billBE->getPublicFeeCategories();
                break;
            case 'upload-student':
                $studentBE->formCreateStudent();
                break;
            case 'upload-students-bulk':
                $studentBE->importStudentsFromXLSX();
                break;
            case 'update-students-bulk':
                $studentBE->bulkUpdateStudentsFromXLSX();
                break;
            case 'get-student-detail':
                $studentBE->getStudentDetail();
                break;
            case 'update-student':
                $studentBE->updateStudent();
                break;
            case 'import-payment':
                $paymentBE->importPaymentsFromXLSX();
                break;
            case 'export-invoice':
                $paymentBE->exportPublicInvoice($segments);
                break;
            case 'send-otp':
                $authBE->sendOTP($segments);
                break;
            case 'verify-otp':
                $authBE->verifyOTP($segments);
                break;
            case 'update-password':
                $authBE->updatePassword($segments);
                break;
            case 'reduction':
                $reductionBE->create($segments);
                break;
            case 'import-fee':
                $studentBE->importAdditionalFeeFromXLSX($segments);
                break;
            default:
                ApiResponse::error('Invalid API endpoint', 404);
                break;
        }
    }
}
