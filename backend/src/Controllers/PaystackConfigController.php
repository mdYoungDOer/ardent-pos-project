<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Config;
use ArdentPOS\Middleware\AuthMiddleware;

class PaystackConfigController
{
    public function getConfig(): void
    {
        AuthMiddleware::requireAuthentication();
        
        echo json_encode([
            'public_key' => Config::get('paystack.public_key'),
            'currency' => Config::get('app.default_currency', 'GHS')
        ]);
    }
}
