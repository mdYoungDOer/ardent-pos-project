<?php
header('Content-Type: application/json');

// Log all request information
$requestData = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'UNKNOWN',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'UNKNOWN',
    'php_input' => file_get_contents('php://input'),
    'http_raw_post_data' => $GLOBALS['HTTP_RAW_POST_DATA'] ?? 'NOT_SET',
    'post_data' => $_POST,
    'get_data' => $_GET,
    'headers' => getallheaders(),
    'server_vars' => $_SERVER
];

echo json_encode($requestData, JSON_PRETTY_PRINT);
