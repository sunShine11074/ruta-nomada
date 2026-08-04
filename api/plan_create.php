<?php
// ============================================================
//  api/plan_create.php — Crear un plan de viaje | Ruta Nómada
//  POST {nombre?, destino, lat?, lng?, fecha_inicio?, fecha_fin?,
//        privacidad?, invitados?: [email,...], draft?: {...}}
//  Crea el plan + membresía de propietario (+ invitaciones) y
//  opcionalmente importa el borrador de localStorage del
//  planificador anterior. Devuelve {ok, id}.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if (empty($_SESSION['user'])) apiFail('Debes iniciar sesión.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);

$userId  = (int)$_SESSION['user']['id'];
$destino = trim((string)($in['destino'] ?? ''));
if ($destino === '' || mb_strlen($destino) > 120) apiFail('Indica el destino del viaje.');

$nombre = trim((string)($in['nombre'] ?? ''));
if ($nombre === '') $nombre = 'Nuestro viaje a ' . $destino;
$nombre = mb_substr($nombre, 0, 200);

$lat = isset($in['lat']) && $in['lat'] !== '' ? (float)$in['lat'] : null;
$lng = isset($in['lng']) && $in['lng'] !== '' ? (float)$in['lng'] : null;

$fi = validDate($in['fecha_inicio'] ?? null);
$ff = validDate($in['fecha_fin'] ?? null);
if ($fi && $ff && $ff < $fi) { $t = $fi; $fi = $ff; $ff = $t; }

$priv = in_array($in['privacidad'] ?? '', ['solo', 'amigos', 'publico'], true) ? $in['privacidad'] : 'solo';

$db = getDB();
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO planes (usuario_id, nombre, destino, lat, lng, fecha_inicio, fecha_fin, privacidad, estado)
         VALUES (?,?,?,?,?,?,?,?, "activo")'
    );
    $stmt->execute([$userId, $nombre, $destino, $lat, $lng, $fi, $ff, $priv]);
    $planId = (int)$db->lastInsertId();

    $db->prepare('INSERT INTO plan_miembros (plan_id, usuario_id, rol) VALUES (?,?, "propietario")')
       ->execute([$planId, $userId]);

    // Importación opcional del borrador del planificador anterior (localStorage)
    if (!empty($in['draft']['days']) && is_array($in['draft']['days'])) {
        $ins = $db->prepare(
            'INSERT INTO plan_items (plan_id, dia, orden, nombre, categoria, hora, precio, nota)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $dia = 1;
        foreach (array_slice($in['draft']['days'], 0, 30) as $day) {
            $orden = 0;
            foreach (array_slice(is_array($day) ? $day : [], 0, 50) as $it) {
                $nom = mb_substr(trim((string)($it['name'] ?? '')), 0, 255);
                if ($nom === '') continue;
                $cat = in_array($it['cat'] ?? '', ['hacer', 'rest', 'hotel', 'custom'], true) ? $it['cat'] : 'custom';
                $hora = preg_match('/^\d{1,2}:\d{2}$/', (string)($it['hora'] ?? '')) ? $it['hora'] : null;
                $precio = isset($it['precio']) && $it['precio'] !== '' ? (float)$it['precio'] : null;
                $ins->execute([$planId, $dia, $orden++, $nom, $cat, $hora, $precio,
                               mb_substr((string)($it['nota'] ?? ''), 0, 5000) ?: null]);
            }
            $dia++;
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('plan_create: ' . $e->getMessage());
    apiFail('No se pudo crear el plan.', 500);
}

// Invitaciones por correo (fuera de la transacción; el correo puede fallar sin abortar)
$invitados = array_slice(array_filter(array_map('trim', (array)($in['invitados'] ?? []))), 0, 10);
$enviadas = 0;
if ($invitados) {
    require_once __DIR__ . '/../includes/plan_invite_lib.php';
    foreach ($invitados as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && planInviteCreate($planId, 'editor', $email)) {
            $enviadas++;
        }
    }
}

apiJson(['ok' => true, 'id' => $planId, 'invitaciones' => $enviadas]);
