<?php

namespace ArdentPOS\Middleware;

use ArdentPOS\Core\Config;

class CorsMiddleware
{
    public static function handle(): void
    {
        // Always allow CORS for Digital Ocean deployment
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant-ID, Accept, Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
