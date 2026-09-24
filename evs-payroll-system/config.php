<?php
/**
 * Configuration file for Elite Vision Security Invoice System
 */

// Database configuration
define('DB_PATH', __DIR__ . '/invoices.db');

// Site configuration
define('SITE_NAME', 'Elite Vision Security Invoice System');
define('SITE_URL', getenv('EVS_SITE_URL') ?: '/evs-payroll-system/');

// Email configuration (Update these with your actual SMTP settings)
define('SMTP_SERVER', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', getenv('EVS_SMTP_USERNAME') ?: 'your-email@gmail.com');
define('SMTP_PASSWORD', getenv('EVS_SMTP_PASSWORD') ?: 'your-app-password');
define('SMTP_FROM_EMAIL', getenv('EVS_SMTP_FROM_EMAIL') ?: 'your-email@gmail.com');
define('SMTP_FROM_NAME', getenv('EVS_SMTP_FROM_NAME') ?: 'Elite Vision Security');

// Company information
define('COMPANY_NAME', 'Elite Vision Security');
define('COMPANY_EMAIL', 'elitevisionph@gmail.com');
define('COMPANY_PHONE', '09549852635');
define('COMPANY_ADDRESS', '168 Zacarias St.,Pagas,Cabanatuan City,Nueva Ecija,Philippines');

// Default tax rate
define('DEFAULT_TAX_RATE', 0.0);

// PDF settings
define('PDF_FOLDER', __DIR__ . '/pdfs/');

// Logging
define('LOG_FOLDER', __DIR__ . '/logs/');
define('LOG_ERRORS', true);

// Timezone
date_default_timezone_set('America/New_York');

// Initialize directories
if (!file_exists(PDF_FOLDER)) {
    mkdir(PDF_FOLDER, 0755, true);
}
if (!file_exists(LOG_FOLDER)) {
    mkdir(LOG_FOLDER, 0755, true);
}

// Function to log messages
function logMessage($message, $type = 'info')
{
    if (!LOG_ERRORS)
        return;

    $timestamp = date('Y-m-d H:i:s');
    $log_file = LOG_FOLDER . date('Y-m-d') . '.log';
    $log_entry = "[{$timestamp}] [{$type}] {$message}\n";

    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Error handling
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    logMessage("Error {$errno}: {$errstr} in {$errfile}:{$errline}", 'error');
});

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Log errors but don't display them
?>