<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

function decode_incident(array $row): array {
    $row['ideal_tools'] = json_decode($row['ideal_tools'] ?? '[]', true) ?: [];
    $row['extra_keywords'] = json_decode($row['extra_keywords'] ?? '[]', true) ?: [];
    $row['stage2_ideal_tools'] = json_decode($row['stage2_ideal_tools'] ?? '[]', true) ?: [];
    $row['stage2_extra_keywords'] = json_decode($row['stage2_extra_keywords'] ?? '[]', true) ?: [];
    $row['is_complex'] = (bool)$row['is_complex'];
    $row['client_has_speaker'] = (bool)$row['client_has_speaker'];
    $row['client_has_siren'] = (bool)$row['client_has_siren'];
    return $row;
}

$baseSelect = "SELECT i.*, c.name AS client_name, c.address AS client_address,
               c.has_speaker AS client_has_speaker, c.has_siren AS client_has_siren,
               c.police_department, c.police_hotline
               FROM incidents i JOIN clients c ON c.id = i.client_id";

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare($baseSelect . ' WHERE i.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) send(['error' => 'Drill not found'], 404);
        send(decode_incident($row));
    }
    $rows = $pdo->query($baseSelect . ' ORDER BY i.created_at DESC')->fetchAll();
    send(array_map('decode_incident', $rows));
}

if ($method === 'POST' || $method === 'PUT') {
    $b = read_json_body();
    if (empty($b['title']) || empty($b['client_id']) || empty($b['narrative']) || empty($b['ideal_tools'])) {
        send(['error' => 'Title, client, narrative and at least one ideal tool are required'], 400);
    }

    $params = [
        $b['title'],
        $b['category'] ?? 'orange',
        (int)$b['client_id'],
        $b['time_label'] ?? '',
        $b['narrative'],
        json_encode($b['ideal_tools']),
        json_encode($b['extra_keywords'] ?? []),
        !empty($b['is_complex']) ? 1 : 0,
        $b['stage2_branch_good'] ?? null,
        $b['stage2_branch_bad'] ?? null,
        $b['stage2_bad_cat'] ?? null,
        json_encode($b['stage2_ideal_tools'] ?? []),
        json_encode($b['stage2_extra_keywords'] ?? []),
    ];

    if ($method === 'POST') {
        $stmt = $pdo->prepare('INSERT INTO incidents
            (title, category, client_id, time_label, narrative, ideal_tools, extra_keywords,
             is_complex, stage2_branch_good, stage2_branch_bad, stage2_bad_cat, stage2_ideal_tools, stage2_extra_keywords)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute($params);
        $newId = $pdo->lastInsertId();
        $code = 201;
    } else {
        if (!$id) send(['error' => 'Missing id'], 400);
        $params[] = $id;
        $stmt = $pdo->prepare('UPDATE incidents SET
            title=?, category=?, client_id=?, time_label=?, narrative=?, ideal_tools=?, extra_keywords=?,
            is_complex=?, stage2_branch_good=?, stage2_branch_bad=?, stage2_bad_cat=?, stage2_ideal_tools=?, stage2_extra_keywords=?
            WHERE id=?');
        $stmt->execute($params);
        $newId = $id;
        $code = 200;
    }

    $stmt = $pdo->prepare($baseSelect . ' WHERE i.id = ?');
    $stmt->execute([$newId]);
    send(decode_incident($stmt->fetch()), $code);
}

if ($method === 'DELETE') {
    if (!$id) send(['error' => 'Missing id'], 400);
    $stmt = $pdo->prepare('DELETE FROM incidents WHERE id = ?');
    $stmt->execute([$id]);
    send(['success' => true]);
}

send(['error' => 'Unsupported method'], 405);
