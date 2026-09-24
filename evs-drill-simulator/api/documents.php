<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$client_id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row)
            send(['error' => 'Document not found'], 404);
        send($row);
    }

    if ($client_id) {
        $stmt = $pdo->prepare('SELECT * FROM documents WHERE client_id = ? ORDER BY category ASC, created_at DESC');
        $stmt->execute([$client_id]);
        send($stmt->fetchAll());
    }

    // Get all documents grouped by client
    $stmt = $pdo->prepare('
        SELECT d.*, c.name as client_name 
        FROM documents d
        JOIN clients c ON d.client_id = c.id
        ORDER BY c.name ASC, d.category ASC, d.created_at DESC
    ');
    $stmt->execute();
    send($stmt->fetchAll());
}

if ($method === 'POST') {
    $b = read_json_body();
    if (empty($b['client_id']) || empty($b['title'])) {
        send(['error' => 'Client ID and title are required'], 400);
    }

    $doc_type = $b['doc_type'] ?? 'external_url';
    if (!in_array($doc_type, ['google_drive', 'uploaded', 'external_url'])) {
        send(['error' => 'Invalid document type'], 400);
    }

    $stmt = $pdo->prepare('
        INSERT INTO documents (client_id, title, description, doc_type, url, file_path, category, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $b['client_id'],
        $b['title'],
        $b['description'] ?? null,
        $doc_type,
        $b['url'] ?? null,
        $b['file_path'] ?? null,
        $b['category'] ?? null,
        $b['uploaded_by'] ?? null,
    ]);
    $newId = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
    $stmt->execute([$newId]);
    send($stmt->fetch(), 201);
}

if ($method === 'PUT') {
    if (!$id)
        send(['error' => 'Missing id'], 400);
    $b = read_json_body();
    if (empty($b['title'])) {
        send(['error' => 'Title is required'], 400);
    }

    $doc_type = $b['doc_type'] ?? 'external_url';
    if (!in_array($doc_type, ['google_drive', 'uploaded', 'external_url'])) {
        send(['error' => 'Invalid document type'], 400);
    }

    $stmt = $pdo->prepare('
        UPDATE documents 
        SET title=?, description=?, doc_type=?, url=?, file_path=?, category=?
        WHERE id=?
    ');
    $stmt->execute([
        $b['title'],
        $b['description'] ?? null,
        $doc_type,
        $b['url'] ?? null,
        $b['file_path'] ?? null,
        $b['category'] ?? null,
        $id,
    ]);
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
    $stmt->execute([$id]);
    send($stmt->fetch());
}

if ($method === 'DELETE') {
    if (!$id)
        send(['error' => 'Missing id'], 400);
    $stmt = $pdo->prepare('DELETE FROM documents WHERE id = ?');
    $stmt->execute([$id]);
    send(['success' => true]);
}

send(['error' => 'Unsupported method'], 405);
