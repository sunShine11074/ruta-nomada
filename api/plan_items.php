<?php
// ============================================================
//  api/plan_items.php — Items del itinerario | Ruta Nómada
//  POST {plan_id, action: add|update|move|del, ...}
//    add:    {dia, nombre, categoria?, place_id?, lat?, lng?, imagen_url?, precio?}
//    update: {id, nombre?, hora?, hora_fin?, precio?, nota?, duracion?,
//             modo_viaje?, moneda?, gasto_cat?, gasto_desc?, gasto_modo?,
//             reparto?: [{usuario_id, monto, color}, ...]}
//    move:   {id, dia, orden}   → renumera origen y destino en transacción
//    del:    {id}
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];
$db = getDB();
$action = $in['action'] ?? '';

// Item propio del plan (evita tocar items ajenos)
function ownItem(PDO $db, int $planId, int $id): array
{
    $stmt = $db->prepare('SELECT * FROM plan_items WHERE id = ? AND plan_id = ? LIMIT 1');
    $stmt->execute([$id, $planId]);
    $it = $stmt->fetch();
    if (!$it) apiFail('El lugar no existe en este plan.', 404);
    return $it;
}

$reHora = '/^\d{1,2}:\d{2}(:\d{2})?$/';

switch ($action) {
    case 'add': {
        $nombre = mb_substr(trim((string)($in['nombre'] ?? '')), 0, 255);
        if ($nombre === '') apiFail('Indica el nombre del lugar.');
        $dia = max(0, min(60, (int)($in['dia'] ?? 0)));
        $cat = in_array($in['categoria'] ?? '', ['hacer', 'rest', 'hotel', 'custom'], true) ? $in['categoria'] : 'custom';
        $lat = isset($in['lat']) && $in['lat'] !== '' ? (float)$in['lat'] : null;
        $lng = isset($in['lng']) && $in['lng'] !== '' ? (float)$in['lng'] : null;
        $precio = isset($in['precio']) && $in['precio'] !== '' && is_numeric($in['precio']) ? (float)$in['precio'] : null;

        $stmt = $db->prepare('SELECT COALESCE(MAX(orden)+1,0) FROM plan_items WHERE plan_id = ? AND dia = ?');
        $stmt->execute([$planId, $dia]);
        $orden = (int)$stmt->fetchColumn();

        $db->prepare(
            'INSERT INTO plan_items (plan_id, dia, orden, nombre, categoria, precio, place_id, lat, lng, imagen_url)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $planId, $dia, $orden, $nombre, $cat, $precio,
            mb_substr((string)($in['place_id'] ?? ''), 0, 60) ?: null,
            $lat, $lng,
            mb_substr((string)($in['imagen_url'] ?? ''), 0, 500) ?: null,
        ]);
        apiJson(['ok' => true, 'id' => (int)$db->lastInsertId(), 'orden' => $orden]);
    }

    case 'update': {
        $it = ownItem($db, $planId, (int)($in['id'] ?? 0));
        $sets = []; $vals = [];
        if (array_key_exists('nombre', $in)) {
            $n = mb_substr(trim((string)$in['nombre']), 0, 255);
            if ($n === '') apiFail('El nombre no puede quedar vacío.');
            $sets[] = 'nombre = ?'; $vals[] = $n;
        }
        foreach (['hora', 'hora_fin'] as $f) {
            if (array_key_exists($f, $in)) {
                $v = (string)$in[$f];
                if ($v !== '' && !preg_match($reHora, $v)) apiFail('Hora inválida.');
                $sets[] = "$f = ?"; $vals[] = $v === '' ? null : $v;
            }
        }
        if (array_key_exists('precio', $in)) {
            $p = $in['precio'];
            if ($p !== null && $p !== '' && (!is_numeric($p) || $p < 0)) apiFail('Precio inválido.');
            $sets[] = 'precio = ?'; $vals[] = ($p === '' || $p === null) ? null : (float)$p;
        }
        if (array_key_exists('nota', $in)) {
            $sets[] = 'nota = ?'; $vals[] = mb_substr((string)$in['nota'], 0, 5000) ?: null;
        }
        if (array_key_exists('duracion', $in)) {
            $sets[] = 'duracion = ?'; $vals[] = mb_substr((string)$in['duracion'], 0, 20) ?: null;
        }
        if (array_key_exists('modo_viaje', $in)) {
            // Cómo se viaja de este lugar al siguiente. Lista cerrada: son
            // los tres modos que ofrece la interfaz y los únicos que la API
            // de rutas acepta aquí.
            $m = strtoupper(trim((string)$in['modo_viaje']));
            if (!in_array($m, ['DRIVE', 'WALK', 'BICYCLE', ''], true)) apiFail('Modo de viaje inválido.');
            $sets[] = 'modo_viaje = ?'; $vals[] = $m === '' ? null : $m;
        }
        // ── Gasto del lugar: divisa, categoría, descripción y modo ──
        if (array_key_exists('moneda', $in)) {
            require_once __DIR__ . '/../includes/currency.php';
            $mon = strtoupper(trim((string)$in['moneda']));
            if (!isValidCurrency($mon)) apiFail('Divisa no admitida.');
            $sets[] = 'moneda = ?'; $vals[] = $mon;
        }
        if (array_key_exists('gasto_cat', $in)) {
            $c = mb_substr(trim((string)$in['gasto_cat']), 0, 24);
            $sets[] = 'gasto_cat = ?'; $vals[] = $c === '' ? null : $c;
        }
        if (array_key_exists('gasto_desc', $in)) {
            $d = mb_substr((string)$in['gasto_desc'], 0, 500);
            $sets[] = 'gasto_desc = ?'; $vals[] = $d === '' ? null : $d;
        }
        if (array_key_exists('gasto_modo', $in)) {
            $gm = (string)$in['gasto_modo'];
            if (!in_array($gm, ['no', 'todos', 'individuos'], true)) apiFail('Modo de división inválido.');
            $sets[] = 'gasto_modo = ?'; $vals[] = $gm;
        }

        if ($sets) {
            $vals[] = $it['id'];
            $db->prepare('UPDATE plan_items SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        }

        // ── Reparto entre los compañeros de viaje ───────────────
        // Llega la lista completa y se reemplaza entera: con tan pocas
        // filas es más fácil de razonar que ir casando altas y bajas.
        if (array_key_exists('reparto', $in)) {
            if (!is_array($in['reparto'])) apiFail('Reparto inválido.');
            // Sólo se acepta a quien de verdad esté en el plan.
            $st = $db->prepare('SELECT usuario_id FROM plan_miembros WHERE plan_id = ?');
            $st->execute([$planId]);
            $permitidos = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

            $filas = [];
            foreach (array_slice($in['reparto'], 0, 50) as $r) {
                if (!is_array($r)) continue;
                $uid = (int)($r['usuario_id'] ?? 0);
                if (!in_array($uid, $permitidos, true) || isset($filas[$uid])) continue;
                $m = $r['monto'] ?? 0;
                if (!is_numeric($m) || $m < 0 || $m > 99999999) apiFail('Importe del reparto inválido.');
                $col = strtoupper(trim((string)($r['color'] ?? '')));
                if ($col !== '' && !preg_match('/^#[0-9A-F]{6}$/', $col)) $col = '';
                $filas[$uid] = ['monto' => (float)$m, 'color' => $col === '' ? null : $col];
            }

            $db->beginTransaction();
            try {
                $db->prepare('DELETE FROM plan_item_gasto WHERE item_id = ?')->execute([$it['id']]);
                if ($filas) {
                    $ins = $db->prepare('INSERT INTO plan_item_gasto (item_id, usuario_id, monto, color) VALUES (?,?,?,?)');
                    foreach ($filas as $uid => $f) $ins->execute([$it['id'], $uid, $f['monto'], $f['color']]);
                }
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();
                error_log('plan_items reparto: ' . $e->getMessage());
                apiFail('No se pudo guardar el reparto.', 500);
            }
        } elseif (!$sets) {
            apiFail('Nada que actualizar.');
        }
        apiJson(['ok' => true]);
    }

    case 'move': {
        $it = ownItem($db, $planId, (int)($in['id'] ?? 0));
        $dia   = max(0, min(60, (int)($in['dia'] ?? 0)));
        $orden = max(0, min(500, (int)($in['orden'] ?? 0)));
        $db->beginTransaction();
        try {
            // Compactar el día de origen (cerrar el hueco)
            $db->prepare('UPDATE plan_items SET orden = orden - 1 WHERE plan_id = ? AND dia = ? AND orden > ?')
               ->execute([$planId, (int)$it['dia'], (int)$it['orden']]);
            // Abrir hueco en el destino
            $db->prepare('UPDATE plan_items SET orden = orden + 1 WHERE plan_id = ? AND dia = ? AND orden >= ? AND id != ?')
               ->execute([$planId, $dia, $orden, $it['id']]);
            // Colocar el item
            $db->prepare('UPDATE plan_items SET dia = ?, orden = ? WHERE id = ?')
               ->execute([$dia, $orden, $it['id']]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('plan_items move: ' . $e->getMessage());
            apiFail('No se pudo mover el lugar.', 500);
        }
        apiJson(['ok' => true]);
    }

    case 'del': {
        $it = ownItem($db, $planId, (int)($in['id'] ?? 0));
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM plan_items WHERE id = ?')->execute([$it['id']]);
            $db->prepare('UPDATE plan_items SET orden = orden - 1 WHERE plan_id = ? AND dia = ? AND orden > ?')
               ->execute([$planId, (int)$it['dia'], (int)$it['orden']]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            apiFail('No se pudo eliminar el lugar.', 500);
        }
        apiJson(['ok' => true]);
    }

    default:
        apiFail('Acción desconocida.');
}
