<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM operators WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) send(['error' => 'Operator not found'], 404);
        send($row);
    }
    send($pdo->query('SELECT * FROM operators ORDER BY name ASC')->fetchAll());
}

if ($method === 'POST') {
    $b = read_json_body();
    if (empty($b['name'])) send(['error' => 'Name is required'], 400);
    $stmt = $pdo->prepare('INSERT INTO operators (name, role) VALUES (?, ?)');
    $stmt->execute([$b['name'], $b['role'] ?? 'Operator']);
    $newId = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM operators WHERE id = ?');
    $stmt->execute([$newId]);
    send($stmt->fetch(), 201);
}

if ($method === 'PUT') {
    if (!$id) send(['error' => 'Missing id'], 400);
    $b = read_json_body();
    if (empty($b['name'])) send(['error' => 'Name is required'], 400);
    $stmt = $pdo->prepare('UPDATE operators SET name=?, role=? WHERE id=?');
    $stmt->execute([$b['name'], $b['role'] ?? 'Operator', $id]);
    $stmt = $pdo->prepare('SELECT * FROM operators WHERE id = ?');
    $stmt->execute([$id]);
    send($stmt->fetch());
}

if ($method === 'DELETE') {
    if (!$id) send(['error' => 'Missing id'], 400);
    $stmt = $pdo->prepare('DELETE FROM operators WHERE id = ?');
    $stmt->execute([$id]);
    send(['success' => true]);
}

send(['error' => 'Unsupported method'], 405);
