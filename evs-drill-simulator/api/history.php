<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    $rows = $pdo->query('SELECT * FROM drill_history ORDER BY created_at DESC LIMIT 100')->fetchAll();
    foreach ($rows as &$row) {
        $row['detail'] = !empty($row['detail_json'])
            ? json_decode($row['detail_json'], true)
            : null;
        unset($row['detail_json']);
    }
    unset($row);
    send($rows);
}

if ($method === 'POST') {
    $b = read_json_body();
    $detail = isset($b['detail']) && is_array($b['detail']) ? $b['detail'] : null;
    $detailJson = $detail !== null ? json_encode($detail, JSON_UNESCAPED_SLASHES) : null;
    $stmt = $pdo->prepare('INSERT INTO drill_history (operator_name, client_name, category, tool_pct, script_pct, overall_pct, detail_json) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $b['operator_name'] ?? '',
        $b['client_name'] ?? '',
        $b['category'] ?? '',
        (int)($b['tool_pct'] ?? 0),
        (int)($b['script_pct'] ?? 0),
        (int)($b['overall_pct'] ?? 0),
        $detailJson,
    ]);
    send(['success' => true, 'id' => $pdo->lastInsertId()], 201);
}

if ($method === 'PUT' && $id) {
    $b = read_json_body();
    $stmt = $pdo->prepare('SELECT detail_json FROM drill_history WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        send(['error' => 'History entry not found'], 404);
    }

    $detail = !empty($row['detail_json'])
        ? json_decode($row['detail_json'], true)
        : [];
    $detail['adminReview'] = [
        'voicemailPoints' => (int)($b['voicemail_points'] ?? 0),
        'pointsAwarded' => (int)($b['points_awarded'] ?? 0),
        'notes' => trim((string)($b['notes'] ?? '')),
        'reviewedAt' => date('Y-m-d H:i:s'),
    ];

    $stmt = $pdo->prepare('UPDATE drill_history SET detail_json = ? WHERE id = ?');
    $stmt->execute([json_encode($detail, JSON_UNESCAPED_SLASHES), $id]);
    send(['success' => true, 'detail' => $detail]);
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
