<?php
// ============================================================
//  api/plan_gastos.php — Gastos del plan | Ruta Nómada
//  POST {plan_id, action: add|update|del, ...}
//    add:    {concepto, monto, categoria?, fecha?}
//    update: {id, concepto?, monto?, categoria?, fecha?}
//    del:    {id}
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];
$db = getDB();

const CATS_GASTO = ['Alojamiento', 'Comida', 'Actividades', 'Transporte', 'Compras', 'Gasolina', 'Otro'];
$reDate = '/^\d{4}-\d{2}-\d{2}$/';

function ownGasto(PDO $db, int $planId, int $id): array
{
    $stmt = $db->prepare('SELECT * FROM plan_gastos WHERE id = ? AND plan_id = ? LIMIT 1');
    $stmt->execute([$id, $planId]);
    $g = $stmt->fetch();
    if (!$g) apiFail('El gasto no existe en este plan.', 404);
    return $g;
}

switch ($in['action'] ?? '') {
    case 'add': {
        $concepto = mb_substr(trim((string)($in['concepto'] ?? '')), 0, 255);
        if ($concepto === '') apiFail('Indica el concepto del gasto.');
        $monto = $in['monto'] ?? null;
        if (!is_numeric($monto) || $monto < 0 || $monto > 99999999) apiFail('Monto inválido.');
        $cat = in_array($in['categoria'] ?? '', CATS_GASTO, true) ? $in['categoria'] : 'Otro';
        $fecha = (isset($in['fecha']) && preg_match($reDate, (string)$in['fecha'])) ? $in['fecha'] : date('Y-m-d');

        $db->prepare('INSERT INTO plan_gastos (plan_id, concepto, monto, categoria, fecha) VALUES (?,?,?,?,?)')
           ->execute([$planId, $concepto, (float)$monto, $cat, $fecha]);
        apiJson(['ok' => true, 'id' => (int)$db->lastInsertId(), 'fecha' => $fecha]);
    }

    case 'update': {
        $g = ownGasto($db, $planId, (int)($in['id'] ?? 0));
        $sets = []; $vals = [];
        if (array_key_exists('concepto', $in)) {
            $c = mb_substr(trim((string)$in['concepto']), 0, 255);
            if ($c === '') apiFail('El concepto no puede quedar vacío.');
            $sets[] = 'concepto = ?'; $vals[] = $c;
        }
        if (array_key_exists('monto', $in)) {
            if (!is_numeric($in['monto']) || $in['monto'] < 0) apiFail('Monto inválido.');
            $sets[] = 'monto = ?'; $vals[] = (float)$in['monto'];
        }
        if (array_key_exists('categoria', $in)) {
            if (!in_array($in['categoria'], CATS_GASTO, true)) apiFail('Categoría inválida.');
            $sets[] = 'categoria = ?'; $vals[] = $in['categoria'];
        }
        if (array_key_exists('fecha', $in)) {
            if ($in['fecha'] !== '' && !preg_match($reDate, (string)$in['fecha'])) apiFail('Fecha inválida.');
            $sets[] = 'fecha = ?'; $vals[] = $in['fecha'] === '' ? null : $in['fecha'];
        }
        if (!$sets) apiFail('Nada que actualizar.');
        $vals[] = $g['id'];
        $db->prepare('UPDATE plan_gastos SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        apiJson(['ok' => true]);
    }

    case 'del': {
        $g = ownGasto($db, $planId, (int)($in['id'] ?? 0));
        $db->prepare('DELETE FROM plan_gastos WHERE id = ?')->execute([$g['id']]);
        apiJson(['ok' => true]);
    }

    default:
        apiFail('Acción desconocida.');
}
