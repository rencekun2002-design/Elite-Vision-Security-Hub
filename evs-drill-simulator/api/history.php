<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    send($pdo->query('SELECT * FROM drill_history ORDER BY created_at DESC LIMIT 100')->fetchAll());
}

if ($method === 'POST') {
    $b = read_json_body();
    $stmt = $pdo->prepare('INSERT INTO drill_history (operator_name, client_name, category, tool_pct, script_pct, overall_pct) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $b['operator_name'] ?? '',
        $b['client_name'] ?? '',
        $b['category'] ?? '',
        (int)($b['tool_pct'] ?? 0),
        (int)($b['script_pct'] ?? 0),
        (int)($b['overall_pct'] ?? 0),
    ]);
    send(['success' => true, 'id' => $pdo->lastInsertId()], 201);
}

if ($method === 'DELETE') {
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM drill_history WHERE id = ?');
        $stmt->execute([$id]);
    } else {
        $pdo->exec('DELETE FROM drill_history');
    }
    send(['success' => true]);
}

send(['error' => 'Unsupported method'], 405);
