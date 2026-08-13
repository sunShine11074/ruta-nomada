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
// Sin nombre se usa el destino, recortando lo que va tras la primera
// coma igual que hace js/plan_nuevo.js: si no, este respaldo daba
// «Viaje a Madrid, España» y el cliente «Viaje a Madrid».
if ($nombre === '') $nombre = 'Viaje a ' . trim(explode(',', $destino)[0]);
$nombre = mb_substr($nombre, 0, 200);

$lat = isset($in['lat']) && $in['lat'] !== '' ? (float)$in['lat'] : null;
$lng = isset($in['lng']) && $in['lng'] !== '' ? (float)$in['lng'] : null;

$fi = validDate($in['fecha_inicio'] ?? null);
$ff = validDate($in['fecha_fin'] ?? null);
if ($fi && $ff && $ff < $fi) { $t = $fi; $fi = $ff; $ff = $t; }

$priv = in_array($in['privacidad'] ?? '', ['solo', 'amigos', 'publico'], true) ? $in['privacidad'] : 'solo';

$db = getDB();

// El plan y su fila de propietario los crea sp_crear_plan
// (basedatos/rutinas.sql) dentro de una transacción PROPIA: no puede
// existir un plan sin dueño ni llamando al SQL a mano. Por eso aquí ya
// no se abre transacción alrededor —el procedimiento hace COMMIT—.
try {
    $stmt = $db->prepare('CALL sp_crear_plan(?,?,?,?,?,?,?,?)');
    $stmt->execute([$userId, $nombre, $destino, $lat, $lng, $fi, $ff, $priv]);
    $planId = (int)($stmt->fetch()['id'] ?? 0);
    $stmt->closeCursor();
    if ($planId <= 0) throw new RuntimeException('sp_crear_plan no devolvió id');
} catch (Throwable $e) {
    error_log('plan_create: ' . $e->getMessage());
    apiFail('No se pudo crear el plan.', 500);
}

// Importación opcional del borrador del planificador anterior
// (localStorage). Va en SU PROPIA transacción, después del plan: si el
// borrador viene corrupto, el plan sobrevive vacío y el error queda en
// el log — mejor que perder también el plan, como pasaba cuando todo
// compartía transacción.
try {
    if (!empty($in['draft']['days']) && is_array($in['draft']['days'])) {
        $db->beginTransaction();
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
        $db->commit();
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('plan_create (borrador): ' . $e->getMessage());
    // El plan ya existe; se sigue sin los items del borrador.
}

// ── Portada automática ──────────────────────────────────────
// Un viaje recién creado no tenía foto, y plan.php caía en
// picsum.photos: una imagen ALEATORIA sembrada con el nombre del
// destino. Estable, sí, pero sin ninguna relación con la ciudad.
// Ahora se busca una foto real del destino en Pexels.
//
// Va en su propio try, después del plan y sin transacción: si Pexels
// tarda o falla, el viaje se crea igual y simplemente se queda sin
// portada, con el mismo respaldo de antes. Espera 6 segundos como
// mucho; la consulta suele tardar menos de uno.
try {
    require_once __DIR__ . '/../includes/pexels_lib.php';
    $portada = pexelsPortadaDeDestino($destino);
    if ($portada !== null) {
        $db->prepare('UPDATE planes SET portada_url = ? WHERE id = ?')
           ->execute([mb_substr($portada, 0, 500), $planId]);
    }
} catch (Throwable $e) {
    error_log('plan_create (portada): ' . $e->getMessage());
    // El plan ya existe; se sigue sin portada.
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
