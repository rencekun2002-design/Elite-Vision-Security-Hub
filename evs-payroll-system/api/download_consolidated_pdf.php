<?php
/**
 * Download Consolidated PDF
 * Serves consolidated invoices PDF file
 */

require_once __DIR__ . '/../config.php';

$filepath = $_GET['path'] ?? '';

// Validate path to prevent directory traversal
if (!$filepath || !file_exists($filepath) || strpos(realpath($filepath), realpath(PDF_FOLDER)) !== 0) {
    http_response_code(403);
    die('Access denied');
}

if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Get filename from path
$filename = basename($filepath);

// Serve PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
?>