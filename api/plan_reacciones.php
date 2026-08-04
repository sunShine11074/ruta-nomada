<?php
// ============================================================
//  api/plan_reacciones.php — Reaccionar con un emoji | Ruta Nómada
//  POST {plan_id, item_id, emoji}
//
//  Regla: una reacción por persona y por lugar.
//   · emoji distinto al actual → lo reemplaza
//   · el mismo emoji otra vez  → la quita (alternar)
//  Devuelve el recuento agregado del lugar: [{e, n, mine}]
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];
$userId = (int)$acc['user_id'];
$db = getDB();

// El lugar debe pertenecer a este plan
$itemId = (int)($in['item_id'] ?? 0);
$stmt = $db->prepare('SELECT id FROM plan_items WHERE id = ? AND plan_id = ? LIMIT 1');
$stmt->execute([$itemId, $planId]);
if (!$stmt->fetch()) apiFail('El lugar no existe en este plan.', 404);

$emoji = trim((string)($in['emoji'] ?? ''));
if ($emoji === '' || mb_strlen($emoji) > 8) apiFail('Emoji inválido.');

// ¿Qué tiene puesto ya esta persona?
$stmt = $db->prepare('SELECT emoji FROM plan_item_reacciones WHERE item_id = ? AND usuario_id = ? LIMIT 1');
$stmt->execute([$itemId, $userId]);
$actual = $stmt->fetchColumn();

if ($actual !== false && $actual === $emoji) {
    // Mismo emoji: se quita
    $db->prepare('DELETE FROM plan_item_reacciones WHERE item_id = ? AND usuario_id = ?')
       ->execute([$itemId, $userId]);
} else {
    // Nuevo o distinto: el índice UNIQUE (item_id, usuario_id) hace que
    // esto reemplace la reacción anterior en lugar de añadir otra
    $db->prepare(
        'INSERT INTO plan_item_reacciones (item_id, usuario_id, emoji) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE emoji = VALUES(emoji)'
    )->execute([$itemId, $userId, $emoji]);
}

// Recuento agregado del lugar
$stmt = $db->prepare(
    'SELECT emoji, COUNT(*) AS n, MAX(usuario_id = ?) AS mine
       FROM plan_item_reacciones WHERE item_id = ?
      GROUP BY emoji ORDER BY n DESC, MIN(id)'
);
$stmt->execute([$userId, $itemId]);
$reacts = array_map(function ($r) {
    return ['e' => $r['emoji'], 'n' => (int)$r['n'], 'mine' => (bool)$r['mine']];
}, $stmt->fetchAll());

apiJson(['ok' => true, 'item_id' => $itemId, 'reacts' => $reacts]);
