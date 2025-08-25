<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
$fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'notify@ardentwebservices.com';

echo json_encode([
    'debug_info' => [
        'api_key_exists' => !empty($apiKey),
        'api_key_length' => strlen($apiKey),
        'from_email' => $fromEmail,
        'env_vars' => [
            'SENDGRID_API_KEY' => !empty($apiKey) ? 'SET' : 'MISSING',
            'SENDGRID_FROM_EMAIL' => !empty($fromEmail) ? 'SET' : 'MISSING'
        ]
    ]
]);

if (empty($apiKey)) {
    echo json_encode([
        'success' => false,
        'error' => 'SendGrid API key not configured'
    ]);
    exit;
}

// Test email sending
$testEmail = $_GET['email'] ?? 'test@example.com';
$subject = 'Test Email - Ardent POS Debug';
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Test Email</h1>
        </div>
        <div class='content'>
            <h2>Hello!</h2>
            <p>This is a test email from Ardent POS to verify that the email notification system is working correctly.</p>
            <p>If you received this email, it means:</p>
            <ul>
                <li>SendGrid is properly configured</li>
                <li>Email templates are working</li>
                <li>Notification system is functional</li>
            </ul>
            <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
        </div>
        <div class='footer'>
            <p>© 2024 Ardent POS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";

$data = [
    'personalizations' => [
        [
            'to' => [['email' => $testEmail]]
        ]
    ],
    'from' => ['email' => $fromEmail],
    'subject' => $subject,
    'content' => [
        [
            'type' => 'text/html',
            'value' => $htmlContent
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$result = [
    'success' => $httpCode >= 200 && $httpCode < 300,
    'http_code' => $httpCode,
    'response' => $response,
    'curl_error' => $curlError,
    'request_data' => $data
];

if ($result['success']) {
    $result['message'] = 'Test email sent successfully';
} else {
    $result['error'] = 'Failed to send test email: HTTP ' . $httpCode;
    if ($curlError) {
        $result['error'] .= ' (CURL Error: ' . $curlError . ')';
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
