<?php
header('Content-Type: application/json');

$debug = [
    'timestamp' => date('c'),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
    'files' => []
];

// Check if assets directory exists and list files
$assetsDir = __DIR__ . '/assets';
if (is_dir($assetsDir)) {
    $files = scandir($assetsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $assetsDir . '/' . $file;
            $debug['files'][] = [
                'name' => $file,
                'size' => filesize($filePath),
                'type' => mime_content_type($filePath),
                'path' => $filePath
            ];
        }
    }
} else {
    $debug['files'] = 'Assets directory not found';
}

// Check if index.html exists
$indexPath = __DIR__ . '/index.html';
if (file_exists($indexPath)) {
    $debug['index_html'] = [
        'exists' => true,
        'size' => filesize($indexPath),
        'path' => $indexPath
    ];
} else {
    $debug['index_html'] = [
        'exists' => false,
        'path' => $indexPath
    ];
}

echo json_encode($debug, JSON_PRETTY_PRINT);
