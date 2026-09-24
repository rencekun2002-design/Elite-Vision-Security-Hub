<?php
// Shared database connection for the EVS Drill Simulator API.
// Default XAMPP credentials: user "root", empty password. Production values come from EVS_DB_* environment variables.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DB_HOST = getenv('EVS_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('EVS_DB_NAME') ?: 'evs_drills';
$DB_USER = getenv('EVS_DB_USER') ?: 'root';
$DB_PASS = getenv('EVS_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Could not connect to the database. Make sure MySQL is running and that database2.sql has been imported into phpMyAdmin.'
    ]);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function send($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}
