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

    // Admin-entered accuracy per voicemail tool replaces the auto-graded
    // hit count for that tool, then script/overall/points are recomputed
    // using the same formula the operator saw when submitting the drill.
    $voicemailAccuracy = isset($b['voicemail_accuracy']) && is_array($b['voicemail_accuracy'])
        ? $b['voicemail_accuracy']
        : [];

    $perTool = is_array($detail['perTool'] ?? null) ? $detail['perTool'] : [];
    $totalHits = 0;
    $totalPossible = 0;
    foreach ($perTool as &$tool) {
        $required = is_array($tool['required'] ?? null) ? $tool['required'] : [];
        $requiredCount = max(count($required), 1);
        $toolName = $tool['tool'] ?? '';
        if (isset($voicemailAccuracy[$toolName])) {
            $accuracy = max(0, min(100, (int) $voicemailAccuracy[$toolName]));
            $tool['adminAccuracy'] = $accuracy;
            $totalHits += round(($accuracy / 100) * $requiredCount);
        } else {
            $hits = is_array($tool['hits'] ?? null) ? $tool['hits'] : [];
            $totalHits += count($hits);
        }
        $totalPossible += $requiredCount;
    }
    unset($tool);

    $catPct = (int) ($detail['catPct'] ?? 0);
    $toolPct = (int) ($detail['toolPct'] ?? 0);
    $timePct = (int) ($detail['timePct'] ?? 0);
    $scriptPct = $totalPossible > 0
        ? (int) round(($totalHits / $totalPossible) * 100)
        : (int) ($detail['scriptPct'] ?? 0);
    $overall = (int) round(($catPct + $toolPct + $scriptPct + $timePct) / 4);

    $infoPenalty = (int) ($detail['infoPenalty'] ?? 0);
    $equipmentPenalty = (int) ($detail['equipmentPenalty'] ?? 0);
    $addressPenalty = (int) ($detail['addressPenalty'] ?? 0);
    $policeDepartmentPenalty = (int) ($detail['policeDepartmentPenalty'] ?? 0);
    $voicemailPenalty = (int) ($detail['voicemailPenalty'] ?? 0);
    $timeAdjustment = (int) ($detail['timeAdjustment'] ?? 0);
    $policeDepartmentCorrect = !empty($detail['policeDepartmentCorrect']);

    $performanceBonus = $overall >= 90 ? 10 : ($overall >= 80 ? 5 : 0);
    $revealFreeBonus = ($infoPenalty === 0 && $policeDepartmentCorrect) ? 10 : 0;
    $grossPoints = $overall + $performanceBonus + $revealFreeBonus + $timeAdjustment;
    $computedPoints = max(0, $grossPoints
        - $infoPenalty - $equipmentPenalty - $addressPenalty
        - $policeDepartmentPenalty - $voicemailPenalty);

    $pointsAwarded = isset($b['points_awarded']) && $b['points_awarded'] !== ''
        ? (int) $b['points_awarded']
        : $computedPoints;

    $detail['perTool'] = $perTool;
    $detail['scriptPct'] = $scriptPct;
    $detail['overall'] = $overall;
    $detail['points'] = $pointsAwarded;
    $detail['adminReview'] = [
        'voicemailAccuracy' => $voicemailAccuracy,
        'computedPoints' => $computedPoints,
        'pointsAwarded' => $pointsAwarded,
        'notes' => trim((string) ($b['notes'] ?? '')),
        'reviewedAt' => date('Y-m-d H:i:s'),
    ];

    $stmt = $pdo->prepare('UPDATE drill_history SET script_pct = ?, overall_pct = ?, detail_json = ? WHERE id = ?');
    $stmt->execute([$scriptPct, $overall, json_encode($detail, JSON_UNESCAPED_SLASHES), $id]);
    send(['success' => true, 'detail' => $detail, 'script_pct' => $scriptPct, 'overall_pct' => $overall]);
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
