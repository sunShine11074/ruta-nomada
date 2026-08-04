<?php
// ============================================================
//  includes/plan_auth.php — Guardián y utilidades de la API
//  del Planificador | Ruta Nómada
//
//  Uso en un endpoint:
//      require_once __DIR__ . '/../includes/plan_auth.php';
//      $acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
//      // $acc = ['rol' => ..., 'plan' => fila de planes, 'user_id' => ...]
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ── Respuestas JSON ──────────────────────────────────────────
function apiJson(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiFail(string $error, int $code = 400): void
{
    apiJson(['ok' => false, 'error' => $error], $code);
}

// Cuerpo JSON del POST (o $_POST como respaldo)
function apiBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    return $_POST;
}

// Fecha 'YYYY-MM-DD' válida de verdad (checkdate) o null
function validDate($s): ?string
{
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) return null;
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
    return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
}

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_plan'])) {
        $_SESSION['csrf_plan'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_plan'];
}

function csrfCheck(array $in): void
{
    $tok = $_SERVER['HTTP_X_CSRF'] ?? ($in['csrf'] ?? '');
    if (empty($_SESSION['csrf_plan']) || !hash_equals($_SESSION['csrf_plan'], (string)$tok)) {
        apiFail('Sesión inválida. Recarga la página.', 403);
    }
}

// ── Acceso al plan ───────────────────────────────────────────
// $need: 'lector' (cualquier miembro) | 'editor' | 'propietario'
function planAccess(int $planId, string $need = 'lector'): array
{
    if (empty($_SESSION['user'])) {
        apiFail('Debes iniciar sesión.', 401);
    }
    if ($planId <= 0) {
        apiFail('Plan no especificado.');
    }
    $userId = (int)$_SESSION['user']['id'];
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT p.*, m.rol
           FROM planes p
           JOIN plan_miembros m ON m.plan_id = p.id AND m.usuario_id = ?
          WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$userId, $planId]);
    $row = $stmt->fetch();
    if (!$row) {
        apiFail('No tienes acceso a este plan.', 403);
    }

    $rango = ['lector' => 1, 'editor' => 2, 'propietario' => 3];
    if (($rango[$row['rol']] ?? 0) < ($rango[$need] ?? 3)) {
        apiFail('No tienes permisos suficientes en este plan.', 403);
    }

    $rol = $row['rol'];
    unset($row['rol']);
    return ['rol' => $rol, 'plan' => $row, 'user_id' => $userId];
}

// ── Armar el JSON completo del plan (carga inicial) ──────────
function planFullJson(int $planId, array $acc): array
{
    $db = getDB();
    $plan = $acc['plan'];

    // Miembros
    $stmt = $db->prepare(
        'SELECT m.usuario_id, m.rol, u.nombre, u.apellidos, u.foto_perfil
           FROM plan_miembros m JOIN usuarios u ON u.id = m.usuario_id
          WHERE m.plan_id = ? ORDER BY FIELD(m.rol,"propietario","editor","lector"), m.joined_at'
    );
    $stmt->execute([$planId]);
    $miembros = $stmt->fetchAll();

    // Items por día (dia 0 = guardados sin asignar)
    $stmt = $db->prepare(
        'SELECT id, dia, orden, nombre, categoria, hora, hora_fin, duracion,
                precio, nota, place_id, lat, lng, imagen_url
           FROM plan_items WHERE plan_id = ? ORDER BY dia, orden, id'
    );
    $stmt->execute([$planId]);
    $items = $stmt->fetchAll();

    // Reacciones agregadas por lugar (una por persona; 'mine' marca la propia)
    $stmt = $db->prepare(
        'SELECT r.item_id, r.emoji, COUNT(*) AS n, MAX(r.usuario_id = ?) AS mine
           FROM plan_item_reacciones r
           JOIN plan_items i ON i.id = r.item_id
          WHERE i.plan_id = ?
          GROUP BY r.item_id, r.emoji
          ORDER BY n DESC, MIN(r.id)'
    );
    $stmt->execute([(int)$acc['user_id'], $planId]);
    $porItem = [];
    foreach ($stmt->fetchAll() as $r) {
        $porItem[(int)$r['item_id']][] = ['e' => $r['emoji'], 'n' => (int)$r['n'], 'mine' => (bool)$r['mine']];
    }
    foreach ($items as &$it) {
        $it['reacts'] = $porItem[(int)$it['id']] ?? [];
    }
    unset($it);

    // Gastos
    $stmt = $db->prepare(
        'SELECT id, concepto, monto, categoria, dia, fecha, created_at
           FROM plan_gastos WHERE plan_id = ? ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([$planId]);
    $gastos = $stmt->fetchAll();

    // Listas + items
    $stmt = $db->prepare('SELECT id, titulo, tipo, texto, orden FROM plan_listas WHERE plan_id = ? ORDER BY orden, id');
    $stmt->execute([$planId]);
    $listas = $stmt->fetchAll();
    if ($listas) {
        $ids = array_column($listas, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id, lista_id, texto, hecho, orden FROM plan_lista_items WHERE lista_id IN ($ph) ORDER BY orden, id");
        $stmt->execute($ids);
        $byList = [];
        foreach ($stmt->fetchAll() as $li) {
            $byList[$li['lista_id']][] = $li;
        }
        foreach ($listas as &$l) {
            $l['items'] = $byList[$l['id']] ?? [];
        }
        unset($l);
    }

    $plan['dia_subtitulos'] = json_decode($plan['dia_subtitulos'] ?? 'null', true) ?: [];

    return [
        'ok'       => true,
        'rol'      => $acc['rol'],
        'plan'     => $plan,
        'miembros' => $miembros,
        'items'    => $items,
        'gastos'   => $gastos,
        'listas'   => $listas,
    ];
}
