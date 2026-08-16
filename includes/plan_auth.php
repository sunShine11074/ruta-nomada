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
//
// TODA respuesta correcta de un endpoint que trabaje sobre un plan
// lleva `rev`, el testigo de cambio del viaje después de lo que se
// acaba de hacer.
//
// ⚠ NO ES UN ADORNO: sin esto, quien edita se avisa a sí mismo.
// El sondeo de api/plan_pulso.php compara el rev del servidor con el
// que el navegador tiene guardado. Como los disparadores suben `rev`
// con CUALQUIER cambio -incluidos los tuyos-, en cuanto alguien
// añadiera un lugar su propio pulso vería el número distinto y le
// anunciaría «hay novedades» por algo que acababa de hacer él.
//
// Devolviéndolo aquí, _sync() en el cliente adopta el número nuevo en
// el mismo momento de escribir, y no queda ninguna ventana de carrera:
// no hace falta un segundo viaje que otra persona pudiera colarse.
//
// Se pone en UN solo sitio a propósito. Hacerlo endpoint por endpoint
// serían más de veinte llamadas a apiJson() repartidas por seis
// archivos, y bastaría olvidar una para que reapareciera el falso
// aviso justo en la acción olvidada.
function apiJson(array $data, int $code = 200): void
{
    if (!empty($GLOBALS['rn_plan_id']) && !isset($data['rev']) && !empty($data['ok'])) {
        try {
            $st = getDB()->prepare('SELECT rev FROM planes WHERE id = ? LIMIT 1');
            $st->execute([(int)$GLOBALS['rn_plan_id']]);
            $r = $st->fetchColumn();
            if ($r !== false) $data['rev'] = (int)$r;
        } catch (Throwable $e) {
            // Que no se pueda leer el testigo no es motivo para tumbar
            // una respuesta que por lo demás salió bien.
            error_log('apiJson rev: ' . $e->getMessage());
        }
    }
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

// ── Cuántos días puede durar un viaje: de 1 a 30 ─────────────
//
// Antes no había regla en ninguna parte, y el cliente TRUNCABA EN
// SILENCIO: js/plan_logic.js generaba los días con
//     while (cur <= d1 && out.length < 30)
// así que un viaje de 40 días se guardaba entero en la base y la pantalla
// dibujaba 30. Los días 31 a 40 existían para la base y no para la
// persona: sin aviso y sin error, y cualquier lugar puesto ahí quedaba
// invisible.
//
// El tope de 30 tiene motivo: los pines del mapa se colorean con SIETE
// colores en ciclo (js/plan_logic.js:235), así que pasado el mes el color
// deja de identificar el día —el 8 repite el del 1— y la barra de días se
// vuelve impracticable.
//
// La comprobación vive AQUÍ y no en cada endpoint porque son dos —crear y
// actualizar— y en este proyecto ya se pagó el precio de duplicar una
// regla: la lista de dominios de correo estaba copiada en login.php y
// register.php, las dos copias se separaron, y se podían crear cuentas
// con las que después no se podía entrar. Ver includes/email_dominio.php.
const PLAN_DIAS_MIN = 1;
const PLAN_DIAS_MAX = 30;

/**
 * Días que dura el viaje, contando los dos extremos: del 1 al 1 es un día.
 * Devuelve null si el rango no está completo, y eso NO es un error: un
 * plan sin fechas es legítimo —se crean así a menudo y se rellenan
 * después—, simplemente no hay nada que validar todavía.
 */
function planDias(?string $fi, ?string $ff): ?int
{
    if (!$fi || !$ff || $fi === '0000-00-00' || $ff === '0000-00-00') return null;
    try {
        $a = new DateTimeImmutable($fi);
        $b = new DateTimeImmutable($ff);
    } catch (Throwable $e) {
        return null;
    }
    return (int)$a->diff($b)->days + 1;
}

/**
 * El mensaje de rechazo, o null si el rango vale. Se devuelve el texto ya
 * redactado para que crear y actualizar digan exactamente lo mismo, y
 * para que el día que cambie el tope no haya que tocar dos frases.
 */
function planRangoError(?string $fi, ?string $ff): ?string
{
    $d = planDias($fi, $ff);
    if ($d === null) return null;              // sin fechas: nada que validar
    if ($d < PLAN_DIAS_MIN) {
        return 'El viaje tiene que durar al menos ' . PLAN_DIAS_MIN . ' día.';
    }
    if ($d > PLAN_DIAS_MAX) {
        return 'Un viaje no puede durar más de ' . PLAN_DIAS_MAX . ' días, y ése dura ' . $d . '. '
             . 'Acorta las fechas o divídelo en varios planes.';
    }
    return null;
}

// ── CSRF ─────────────────────────────────────────────────────
// csrfToken(), csrfCampo() y csrfValido() viven en includes/csrf.php,
// que también usan los formularios HTML de sesión. El token es el
// mismo; aquí sólo queda la variante que corta con JSON, que es lo que
// espera la API del planificador.
require_once __DIR__ . '/csrf.php';

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

    // Se anota sobre qué plan trabaja esta petición para que apiJson()
    // pueda devolver su `rev` sin que cada endpoint tenga que acordarse.
    // Se anota DESPUÉS de comprobar el acceso: si no eres miembro, la
    // línea de arriba ya cortó y aquí no se llega.
    $GLOBALS['rn_plan_id'] = $planId;

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
        'SELECT id, ver, dia, orden, nombre, categoria, hora, hora_fin, duracion,
                precio, moneda, gasto_cat, gasto_desc, gasto_modo,
                nota, place_id, lat, lng, imagen_url, modo_viaje
           FROM plan_items WHERE plan_id = ? ORDER BY dia, orden, id'
    );
    $stmt->execute([$planId]);
    $items = $stmt->fetchAll();

    // Cómo se reparte el coste de cada lugar entre los compañeros
    $stmt = $db->prepare(
        'SELECT g.item_id, g.usuario_id, g.monto, g.color
           FROM plan_item_gasto g JOIN plan_items i ON i.id = g.item_id
          WHERE i.plan_id = ? ORDER BY g.id'
    );
    $stmt->execute([$planId]);
    $reparto = [];
    foreach ($stmt->fetchAll() as $r) {
        $reparto[(int)$r['item_id']][] = [
            'uid' => (int)$r['usuario_id'], 'monto' => (float)$r['monto'], 'color' => $r['color'],
        ];
    }

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
        $it['reacts']  = $porItem[(int)$it['id']] ?? [];
        $it['reparto'] = $reparto[(int)$it['id']] ?? [];
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
