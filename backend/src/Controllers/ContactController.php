<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;

class ContactController
{
    public function submit()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['subject']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                return;
            }
            
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                return;
            }
            
            // Get client IP and user agent
            $clientIP = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Insert into database
            $sql = "INSERT INTO contact_submissions (first_name, last_name, email, company, subject, message, ip_address, user_agent, status, created_at) 
                    VALUES (:first_name, :last_name, :email, :company, :subject, :message, :ip_address, :user_agent, 'new', NOW())";
            
            $params = [
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'company' => $input['company'] ?? null,
                'subject' => $input['subject'],
                'message' => $input['message'],
                'ip_address' => $clientIP,
                'user_agent' => $userAgent
            ];
            
            Database::query($sql, $params);
            
            // Send success response
            echo json_encode(['success' => true, 'message' => 'Contact form submitted successfully']);
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to submit contact form']);
        }
    }
    
    private function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
