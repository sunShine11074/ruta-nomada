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

// ⚠ UN SOLO VOCABULARIO DE CATEGORIAS, y aqui estan las doce.
//
// Antes esto era un ENUM de siete capitalizadas ('Alojamiento',
// 'Comida', 'Actividades'…) mientras plan_items.gasto_cat guardaba
// slugs en minuscula de la ventana ('comida', 'supermercado'…). Con dos
// vocabularios, el eje de la grafica del desglose —que ya tenia las
// doce— tenia CINCO BARRAS QUE NUNCA PODIAN VALER MAS QUE CERO: se
// dibujo para unos datos y se cableo a otros.
//
// La lista tiene que casar con _gcats() de js/plan_logic.js. Si se
// añade una categoria alli, va tambien aqui o el guardado la rechaza.
const CATS_GASTO = [
    'actividad', 'comida', 'bebidas', 'alojamiento', 'compras', 'supermercado',
    'coche', 'gasolina', 'vuelo', 'tren', 'cultura', 'otro',
];
// Las nueve divisas de la ventana. Se valida contra lista cerrada y no
// con un LIKE de tres letras: 'XXX' no es una moneda y acabaria en la
// base como si lo fuera.
const MONEDAS_GASTO = ['MXN','USD','EUR','GBP','CAD','JPY','BRL','COP','ARS'];

// ── El reparto de un gasto suelto ───────────────────────────
// Gemela de la de api/plan_items.php, con la misma disciplina: solo
// entran usuarios que SON miembros del plan, el color se valida como
// hex, y el borrado y la insercion van en una transaccion para que no
// quede un reparto a medias si algo falla por el camino.
function guardarRepartoGasto(PDO $db, int $planId, int $gastoId, array $reparto): void
{
    $st = $db->prepare('SELECT usuario_id FROM plan_miembros WHERE plan_id = ?');
    $st->execute([$planId]);
    $miembros = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM plan_gasto_reparto WHERE gasto_id = ?')->execute([$gastoId]);
        $ins = $db->prepare('INSERT INTO plan_gasto_reparto (gasto_id, usuario_id, monto, color) VALUES (?,?,?,?)');
        foreach ($reparto as $r) {
            $uid = (int)($r['usuario_id'] ?? 0);
            if (!isset($miembros[$uid])) continue;          // no es del plan: fuera
            $m = $r['monto'] ?? 0;
            if (!is_numeric($m) || $m < 0) continue;
            $col = (string)($r['color'] ?? '');
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $col)) $col = null;
            $ins->execute([$gastoId, $uid, (float)$m, $col]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('plan_gastos reparto: ' . $e->getMessage());
    }
}
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
        $cat = in_array($in['categoria'] ?? '', CATS_GASTO, true) ? $in['categoria'] : 'otro';
        $fecha = (isset($in['fecha']) && preg_match($reDate, (string)$in['fecha'])) ? $in['fecha'] : date('Y-m-d');
        // Lo que trae la ventana «Añadir gasto» y el formulario viejo no tenia.
        $mon  = in_array($in['moneda'] ?? '', MONEDAS_GASTO, true) ? $in['moneda'] : 'MXN';
        $desc = mb_substr(trim((string)($in['descripcion'] ?? '')), 0, 500) ?: null;
        $modo = in_array($in['modo'] ?? '', ['no','todos','individuos'], true) ? $in['modo'] : 'no';

        $db->prepare('INSERT INTO plan_gastos (plan_id, concepto, monto, moneda, categoria, descripcion, modo, fecha) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$planId, $concepto, (float)$monto, $mon, $cat, $desc, $modo, $fecha]);
        $gid = (int)$db->lastInsertId();
        if (is_array($in['reparto'] ?? null)) guardarRepartoGasto($db, $planId, $gid, $in['reparto']);
        apiJson(['ok' => true, 'id' => $gid, 'fecha' => $fecha]);
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
        if (array_key_exists('moneda', $in)) {
            if (!in_array($in['moneda'], MONEDAS_GASTO, true)) apiFail('Moneda inválida.');
            $sets[] = 'moneda = ?'; $vals[] = $in['moneda'];
        }
        if (array_key_exists('descripcion', $in)) {
            $d = mb_substr(trim((string)$in['descripcion']), 0, 500);
            $sets[] = 'descripcion = ?'; $vals[] = $d === '' ? null : $d;
        }
        if (array_key_exists('modo', $in)) {
            if (!in_array($in['modo'], ['no','todos','individuos'], true)) apiFail('Modo de reparto inválido.');
            $sets[] = 'modo = ?'; $vals[] = $in['modo'];
        }
        // El reparto va en su propia tabla, asi que se guarda aparte de
        // los SET. Se reescribe entero: es lo que manda la ventana.
        if (is_array($in['reparto'] ?? null)) guardarRepartoGasto($db, $planId, (int)$g['id'], $in['reparto']);
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
