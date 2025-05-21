<?php

namespace App\Helpers;
require_once dirname(dirname(__DIR__)) . '/config/constants.php';

class FormatHelper
{
    public static function formatRupiah($amount = 0)
    {
        return 'Rp ' . number_format((float)$amount, 0, ',', '.');
    }

    public static function formatVA($level, $nis, $date = null)
    {
        $current_date = Call::date($date) ?? Call::date();
        $current_year = Call::year(YEAR_TWO_DIGIT_FORMAT, $current_date);
        $semester     = Call::semester($current_date);
        $academic_year = $semester == FIRST_SEMESTER ? "$current_year" . $current_year+1 : $current_year-1 . "$current_year";
        return $_ENV['BANK_CODE'] . $level . $academic_year . $nis;
    }

    public static function formatSystemLog(String $type, Array $attr = [])
    {   
        switch ($type) {
            case LOG_CREATE_BILLS:
                $year           = $attr['year'] ?? Call::year();
                $semester       = $attr['semester'] ?? Call::semester();

                $name = LOG_CREATE_BILLS."-$semester-$year";
                $desc = "System Created Billing for the $year $semester.";
                return ['log_name' => $name, 'description' => $desc];
            case LOG_CHECK_BILLS:
                $data = [
                    'semester' => $attr['semester'] ?? null,
                    'year' => $attr['year'] ?? null,
                    'month' => $attr['month']?? null,
                ];
                $name = LOG_CHECK_BILLS."-$data[semester]-$data[year]-$data[month]";
                $desc = "System Checked Billing for the month $data[month]/$data[year].";
                return ['log_name' => $name, 'description' => $desc];
            default: 
                return ['log_name' => $attr['name'], 'description' => $attr['description']];
        }
    }

    public static function FormatTransactionCode($level, $va, $month)
    {
        $year = Call::year(YEAR_FOUR_DIGIT_FORMAT);
        $semester = Call::semester();
        $semester_code = $semester == FIRST_SEMESTER ? "1" : "2";
        $month = (($month)%6);

        $nis = substr($va, -4);

        $trx = "$level/$year/$semester_code/$month/$nis";

        return $trx;
    }

    public static function formatMonthNameInBahasa(int|string $input): int|string|null
    {
        $months = [
            1  => "Januari",
            2  => "Februari",
            3  => "Maret",
            4  => "April",
            5  => "Mei",
            6  => "Juni",
            7  => "Juli",
            8  => "Agustus",
            9  => "September",
            10 => "Oktober",
            11 => "November",
            12 => "Desember"
        ];
    
        if (is_int($input)) {
            return $months[$input] ?? null;
        }
    
        if (is_string($input)) {
            $key = array_search(ucfirst(strtolower($input)), $months, true);
            return $key !== false ? $key : null;
        }
    
        return null;
    }
}