<?php

namespace App\Helpers;
require_once dirname(dirname(__DIR__)) . '/config/constants.php';

class Call
{
    public static function denda()
    {
        return 10000;
    }
    public static function adminVA()
    {
        return 2000;
    }
    public static function timestamp()
    {
        date_default_timezone_set('Asia/Jakarta');
        return date(TIMESTAMP_FORMAT);
    }
    public static function date($date = null)
    {
        if ($date === null) {
            return date(DATE_FORMAT);
        }
        return date(DATE_FORMAT, strtotime($date));
    }
    public static function splitDate($date = null)
    {
        list($year, $month, $day) = explode('-', self::date($date));
        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'day' => (int)$day,
        ];
    }
    public static function year($format = YEAR_FOUR_DIGIT_FORMAT, $date = null)
    {
        $splitDate = self::splitDate($date);
        switch ($format) {
            case YEAR_FOUR_DIGIT_FORMAT:
                return $splitDate['year'];
            case YEAR_TWO_DIGIT_FORMAT:
                return $splitDate['year']%100;
            default:
                return $splitDate['year'];
        }
    }
    public static function semester($date = null)
    {
        $date = self::splitDate($date);
        $isFirstHalf = ($date['month'] >= 7);
        return $isFirstHalf ? FIRST_SEMESTER : SECOND_SEMESTER;
    }
    public static function academicYear($format = ACADEMIC_YEAR_EIGHT_SLASH_FORMAT, $attr = [])
    {
        $semester = $attr['semester'] ?? self::semester();
        $date = $attr['date'] ?? self::splitDate();

        $startYear = $semester == FIRST_SEMESTER ? (int)$date['year'] : (int)$date['year']-1;
        $endYear = $startYear + 1;
        
        switch ($format) {
            case ACADEMIC_YEAR_EIGHT_SLASH_FORMAT:
                return "$startYear/$endYear";
            case ACADEMIC_YEAR_EIGHT_FORMAT:
                return "$startYear$endYear";
            case ACADEMIC_YEAR_FOUR_SLASH_FORMAT:
                $startYear = $startYear%100;
                return "$startYear/$endYear";
            case ACADEMIC_YEAR_FOUR_FORMAT:
                $startYear = $startYear%100;
                return "$startYear$endYear";
            case ACADEMIC_YEAR_AKT_FORMAT:
                return "$startYear - $endYear";
            default:
                return "$startYear/$endYear";
        }
    }

    public static function monthSemester($date = null)
    {
        $semester = self::semester($date);

        return $semester == FIRST_SEMESTER ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
    }

    public static function monthNameSemester($semester = null)
    {
        $semester = isset($semester) ? $semester : self::semester();
        return $semester == SECOND_SEMESTER ? [
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni'
        ] : [
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
    }

    public static function statusColor($status)
    {
        switch($status){
            case BILL_STATUS_PAID:
                return "text-green-700";
            case BILL_STATUS_LATE:
                return "text-amber-700";
            case BILL_STATUS_ACTIVE:
                return "text-blue-700 bg-blue-50";
            case BILL_STATUS_UNPAID:
                return "text-red-700";
            case BILL_STATUS_INACTIVE:
                return "text-slate-400";
            case BILL_STATUS_DISABLED:
                return "text-slate-200";
            default:
                return "";
        }
    }

    public static function getFirstDay($details = null, $type = FIRST_DAY_FROM_ACADEMIC_YEAR_DETAILS)
    {
        switch($type){
            case FIRST_DAY_FROM_ACADEMIC_YEAR_DETAILS:
                return self::firstDayFromAcademicYear($details);
            default:
                return NULL_VALUE;
        }
    }

    private static function firstDayFromAcademicYear($details = [])
    {
        if(empty($details)){
            $now = Call::splitDate();
            $details = [
                'year' => Call::academicYear(),
                'semester' => Call::semester() == FIRST_SEMESTER ? 1 : 2,
                'month' => $now['month']
            ];
        }
        $academic_year = $details['year'];
        $semester = $details['semester'];
        $month = sprintf('%02d', $details['month']);

        $years = explode("/", $academic_year);
        $year = $years[intval($semester) - 1];

        $firstDay = "$year-$month-01";

        return $firstDay;
    }

    public static function uuidv4()
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}