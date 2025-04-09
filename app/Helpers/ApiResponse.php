<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data, $message = 'Success', $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
        exit; 
    }

    public static function error($message, $code = 500, $errors = [])
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ]);
        exit; 
    }

    public static function dd($data)
    {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}