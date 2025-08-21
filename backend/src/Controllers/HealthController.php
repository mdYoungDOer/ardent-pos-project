<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;

class HealthController
{
    public function check(): void
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'checks' => []
        ];

        // Database check
        try {
            Database::query('SELECT 1');
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'error';
            $health['status'] = 'error';
        }

        // Configuration check
        $health['checks']['config'] = Config::get('app.env') ? 'ok' : 'error';

        echo json_encode($health);
    }
}
