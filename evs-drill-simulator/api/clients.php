<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row)
            send(['error' => 'Client not found'], 404);
        send($row);
    }
    send($pdo->query('SELECT * FROM clients ORDER BY name ASC')->fetchAll());
}

if ($method === 'POST') {
    $b = read_json_body();
    if (empty($b['name']) || empty($b['address'])) {
        send(['error' => 'Name and address are required'], 400);
    }
    $stmt = $pdo->prepare('INSERT INTO clients (name, address, state, has_speaker, has_siren, police_department, police_hotline) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $b['name'],
        $b['address'],
        $b['state'] ?? 'NY',
        !empty($b['has_speaker']) ? 1 : 0,
        !empty($b['has_siren']) ? 1 : 0,
        $b['police_department'] ?? null,
        $b['police_hotline'] ?? null,
    ]);
    $newId = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$newId]);
    send($stmt->fetch(), 201);
}

if ($method === 'PUT') {
    if (!$id)
        send(['error' => 'Missing id'], 400);
    $b = read_json_body();
    if (empty($b['name']) || empty($b['address'])) {
        send(['error' => 'Name and address are required'], 400);
    }
    $stmt = $pdo->prepare('UPDATE clients SET name=?, address=?, state=?, has_speaker=?, has_siren=?, police_department=?, police_hotline=? WHERE id=?');
    $stmt->execute([
        $b['name'],
        $b['address'],
        $b['state'] ?? 'NY',
        !empty($b['has_speaker']) ? 1 : 0,
        !empty($b['has_siren']) ? 1 : 0,
        $b['police_department'] ?? null,
        $b['police_hotline'] ?? null,
        $id,
    ]);
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    send($stmt->fetch());
}

if ($method === 'DELETE') {
    if (!$id)
        send(['error' => 'Missing id'], 400);
    $stmt = $pdo->prepare('DELETE FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    send(['success' => true]);
}

send(['error' => 'Unsupported method'], 405);
