<?php

namespace App\Helpers;
require_once dirname(dirname(__DIR__)) . '/config/constants.php';

class Fonnte{
    public static function sendMessage($request)
    {
        $curl = curl_init();
        $config = require __DIR__ . '/../../config/fonnte.php';
        $token = $config['fonnte_token'];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => ["Authorization: $token"],
        ]);
    
        $response = curl_exec($curl);
    
        curl_close($curl);
    
        return $response;
    }
}