<?php
// ============================================================
//  api/plan_listas.php — Listas del Resumen | Ruta Nómada
//  POST {plan_id, action, ...}
//    add:          {tipo: nota|check, titulo?}
//    rename:       {id, titulo}
//    set_texto:    {id, texto}            (solo tipo nota)
//    del:          {id}
//    item_add:     {id, texto}            → devuelve item_id
//    item_toggle:  {item_id, hecho}
//    item_del:     {item_id}
//    clear_done:   {id}                   quita los completados
//    import_plantilla: {id, textos: [..]} añade varios de golpe
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];
$db = getDB();

function ownLista(PDO $db, int $planId, int $id): array
{
    $stmt = $db->prepare('SELECT * FROM plan_listas WHERE id = ? AND plan_id = ? LIMIT 1');
    $stmt->execute([$id, $planId]);
    $l = $stmt->fetch();
    if (!$l) apiFail('La lista no existe en este plan.', 404);
    return $l;
}

function ownListaItem(PDO $db, int $planId, int $itemId): array
{
    $stmt = $db->prepare(
        'SELECT i.* FROM plan_lista_items i JOIN plan_listas l ON l.id = i.lista_id
          WHERE i.id = ? AND l.plan_id = ? LIMIT 1'
    );
    $stmt->execute([$itemId, $planId]);
    $it = $stmt->fetch();
    if (!$it) apiFail('El artículo no existe en este plan.', 404);
    return $it;
}

switch ($in['action'] ?? '') {
    case 'add': {
        $tipo = in_array($in['tipo'] ?? '', ['nota', 'check'], true) ? $in['tipo'] : 'check';
        $titulo = mb_substr(trim((string)($in['titulo'] ?? '')), 0, 255);
        if ($titulo === '') $titulo = $tipo === 'nota' ? 'Notas' : 'Nueva lista';
        $stmt = $db->prepare('SELECT COALESCE(MAX(orden)+1,0) FROM plan_listas WHERE plan_id = ?');
        $stmt->execute([$planId]);
        $orden = (int)$stmt->fetchColumn();
        $db->prepare('INSERT INTO plan_listas (plan_id, titulo, tipo, orden) VALUES (?,?,?,?)')
           ->execute([$planId, $titulo, $tipo, $orden]);
        apiJson(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    }

    case 'rename': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $t = mb_substr(trim((string)($in['titulo'] ?? '')), 0, 255);
        if ($t === '') apiFail('El título no puede quedar vacío.');
        $db->prepare('UPDATE plan_listas SET titulo = ? WHERE id = ?')->execute([$t, $l['id']]);
        apiJson(['ok' => true]);
    }

    case 'set_texto': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $db->prepare('UPDATE plan_listas SET texto = ? WHERE id = ?')
           ->execute([mb_substr((string)($in['texto'] ?? ''), 0, 20000) ?: null, $l['id']]);
        apiJson(['ok' => true]);
    }

    case 'del': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $db->prepare('DELETE FROM plan_listas WHERE id = ?')->execute([$l['id']]);
        apiJson(['ok' => true]);
    }

    case 'item_add': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $texto = mb_substr(trim((string)($in['texto'] ?? '')), 0, 500);
        if ($texto === '') apiFail('Escribe el artículo.');
        $stmt = $db->prepare('SELECT COALESCE(MAX(orden)+1,0) FROM plan_lista_items WHERE lista_id = ?');
        $stmt->execute([$l['id']]);
        $orden = (int)$stmt->fetchColumn();
        $db->prepare('INSERT INTO plan_lista_items (lista_id, texto, orden) VALUES (?,?,?)')
           ->execute([$l['id'], $texto, $orden]);
        apiJson(['ok' => true, 'item_id' => (int)$db->lastInsertId()]);
    }

    case 'item_toggle': {
        $it = ownListaItem($db, $planId, (int)($in['item_id'] ?? 0));
        $db->prepare('UPDATE plan_lista_items SET hecho = ? WHERE id = ?')
           ->execute([!empty($in['hecho']) ? 1 : 0, $it['id']]);
        apiJson(['ok' => true]);
    }

    case 'item_del': {
        $it = ownListaItem($db, $planId, (int)($in['item_id'] ?? 0));
        $db->prepare('DELETE FROM plan_lista_items WHERE id = ?')->execute([$it['id']]);
        apiJson(['ok' => true]);
    }

    case 'item_move': {
        // Reordenar un artículo dentro de su lista (arrastre por el grip)
        $it = ownListaItem($db, $planId, (int)($in['item_id'] ?? 0));
        $orden = max(0, min(500, (int)($in['orden'] ?? 0)));
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE plan_lista_items SET orden = orden - 1 WHERE lista_id = ? AND orden > ?')
               ->execute([(int)$it['lista_id'], (int)$it['orden']]);
            $db->prepare('UPDATE plan_lista_items SET orden = orden + 1 WHERE lista_id = ? AND orden >= ? AND id != ?')
               ->execute([(int)$it['lista_id'], $orden, $it['id']]);
            $db->prepare('UPDATE plan_lista_items SET orden = ? WHERE id = ?')->execute([$orden, $it['id']]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('plan_listas item_move: ' . $e->getMessage());
            apiFail('No se pudo mover el artículo.', 500);
        }
        apiJson(['ok' => true]);
    }

    case 'clear_done': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $db->prepare('DELETE FROM plan_lista_items WHERE lista_id = ? AND hecho = 1')->execute([$l['id']]);
        apiJson(['ok' => true]);
    }

    case 'import_plantilla': {
        $l = ownLista($db, $planId, (int)($in['id'] ?? 0));
        $textos = array_slice(array_filter(array_map(
            fn($t) => mb_substr(trim((string)$t), 0, 500),
            (array)($in['textos'] ?? [])
        )), 0, 100);
        if (!$textos) apiFail('No hay artículos que importar.');
        $stmt = $db->prepare('SELECT COALESCE(MAX(orden)+1,0) FROM plan_lista_items WHERE lista_id = ?');
        $stmt->execute([$l['id']]);
        $orden = (int)$stmt->fetchColumn();
        $ins = $db->prepare('INSERT INTO plan_lista_items (lista_id, texto, orden) VALUES (?,?,?)');
        foreach ($textos as $t) $ins->execute([$l['id'], $t, $orden++]);
        apiJson(['ok' => true, 'agregados' => count($textos)]);
    }

    default:
        apiFail('Acción desconocida.');
}
