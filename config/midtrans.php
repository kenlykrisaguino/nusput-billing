<?php

use Config\Config;

return [
    'MIDTRANS_SERVER_KEY'       => Config::getConfig('MIDTRANS_SERVER_KEY'),
    'MIDTRANS_IS_PRODUCTION'    => Config::getConfig('MIDTRANS_IS_PRODUCTION'),
    'MIDTRANS_IS_SANITIZED'     => Config::getConfig('MIDTRANS_IS_SANITIZED'),
    'MIDTRANS_IS_3DS'           => Config::getConfig('MIDTRANS_IS_3DS')
];