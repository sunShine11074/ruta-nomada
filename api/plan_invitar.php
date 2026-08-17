<?php
// ============================================================
//  api/plan_invitar.php — Invitar colaboradores | Ruta Nómada
//
//  POST {plan_id, action:'enlace'}                        → {ok, link}
//  POST {plan_id, action:'correo', emails:[], mensaje?, rol?}
//                                → {ok, resultados:[{email, estado, motivo}]}
//
//  Sólo el propietario. Es lo que ya exigía este endpoint, y por eso
//  la ventana sólo dibuja el botón de invitar para él: un botón que
//  siempre falla es peor que ningún botón.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/plan_invite_lib.php';

// Cuántas invitaciones por correo admite un plan en una hora. No
// existía ningún tope, y cada petición manda un mensaje REAL por
// Gmail: sin límite, un bucle deja la cuenta marcada como spam. Se
// cuenta sobre la propia tabla, que ya registra exactamente esto, en
// vez de inventar un contador aparte.
const INVITAR_POR_HORA = 10;

// EL MISMO TOPE, PERO SUMANDO TODOS LOS PLANES DE LA PERSONA.
//
// El de arriba cuenta `WHERE plan_id = ?`, o sea POR PLAN, y crear
// planes es gratis e ilimitado: quien quisiera mandar cien correos
// creaba diez planes y ya. Nunca fue un problema real mientras cada
// envío costara teclear una dirección a mano y el texto lo
// escribiéramos nosotros. Con varios destinatarios de una tacada y un
// mensaje libre, esta ventana pasa a tener la forma exacta de un
// formulario de spam, y sale desde NUESTRA cuenta de Gmail.
//
// Va más alto que el de plan porque es legítimo repartir invitaciones
// entre dos o tres viajes el mismo día; lo que corta es el bucle.
const INVITAR_POR_HORA_USUARIO = 25;

// Destinatarios por envío. El frame enseña uno, pero la lista es
// abierta y sin tope una pegada de 500 direcciones sale en una sola
// petición.
const INVITAR_DESTINOS_MAX = 10;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
planAccess((int)($in['plan_id'] ?? 0), 'propietario');
$planId = (int)$in['plan_id'];

$action = (string)($in['action'] ?? 'correo');

// ── El enlace para compartir ─────────────────────────────────
// Sin efectos secundarios al repetirse: planEnlaceDePlan() reutiliza
// el que ya haya vigente. La ventana lo pide cada vez que se abre.
if ($action === 'enlace') {
    $link = planEnlaceDePlan($planId);
    if (!$link) apiFail('No se pudo preparar el enlace.', 500);
    apiJson(['ok' => true, 'link' => $link]);
}

// ── Invitaciones dirigidas a uno o varios correos ────────────
$rol = in_array($in['rol'] ?? '', ['editor', 'lector'], true) ? $in['rol'] : 'editor';

// Se admite `emails` (la lista de la ventana nueva) y `email` suelto,
// que es como llamaba la ventana vieja. No cuesta nada y evita que una
// pestaña abierta desde antes del cambio se quede sin poder invitar.
$brutos = $in['emails'] ?? $in['email'] ?? [];
if (!is_array($brutos)) $brutos = [$brutos];

// Limpieza ANTES de mirar el tope, o diez huecos vacíos contarían como
// diez destinatarios. Se quitan repetidas sin distinguir mayúsculas,
// porque para el servidor de correo Ana@ y ana@ son la misma persona y
// recibiría el mismo mensaje dos veces.
$emails = [];
$vistos = [];
foreach ($brutos as $e) {
    $e = trim((string)$e);
    if ($e === '') continue;
    $k = mb_strtolower($e);
    if (isset($vistos[$k])) continue;
    $vistos[$k] = true;
    $emails[] = $e;
}

if (!$emails) apiFail('Escribe el correo de quien quieres invitar.');
if (count($emails) > INVITAR_DESTINOS_MAX) {
    apiFail('Como mucho ' . INVITAR_DESTINOS_MAX . ' personas por envío.');
}

$mensaje = trim((string)($in['mensaje'] ?? ''));
if (mb_strlen($mensaje) > PLAN_INV_MSG_MAX) {
    apiFail('El mensaje no puede pasar de ' . PLAN_INV_MSG_MAX . ' caracteres.');
}

$db = getDB();

// ── Los dos topes, comprobados UNA vez y con el lote entero ──
//
// Se miran antes de mandar nada y contando cuántos se van a añadir, no
// de uno en uno: si quedan 2 huecos y llegan 5 direcciones, se corta
// aquí en vez de mandar dos correos y fallar a mitad del lote.
$recientes = $db->prepare(
    'SELECT COUNT(*) FROM plan_invitaciones
      WHERE plan_id = ? AND email IS NOT NULL
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$recientes->execute([$planId]);
$yaPlan = (int)$recientes->fetchColumn();

$porUsuario = $db->prepare(
    'SELECT COUNT(*) FROM plan_invitaciones i
       JOIN planes p ON p.id = i.plan_id
      WHERE p.usuario_id = ? AND i.email IS NOT NULL
        AND i.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$porUsuario->execute([(int)$_SESSION['user']['id']]);
$yaUsuario = (int)$porUsuario->fetchColumn();

if ($yaPlan + count($emails) > INVITAR_POR_HORA
 || $yaUsuario + count($emails) > INVITAR_POR_HORA_USUARIO) {
    apiFail('Has enviado muchas invitaciones seguidas. Inténtalo dentro de un rato.', 429);
}

require_once __DIR__ . '/../includes/email_dominio.php';

$yaEsta = $db->prepare(
    'SELECT 1 FROM plan_miembros m JOIN usuarios u ON u.id = m.usuario_id
      WHERE m.plan_id = ? AND u.email = ? LIMIT 1'
);

// UNA DIRECCIÓN MALA NO PUEDE TUMBAR EL LOTE.
//
// Antes cada comprobación era un apiFail(), que corta la petición
// entera. Con una sola dirección daba igual; con cinco, que la tercera
// esté repetida no puede cancelar las otras cuatro. Así que cada una se
// resuelve por su cuenta y la ventana enseña qué pasó con cada quien.
//
// Tres estados, y la diferencia entre los dos primeros importa:
//   enviado  la invitación existe Y el correo salió
//   creado   la invitación existe pero el correo NO salió. Es lo que
//            pasa en las máquinas sin includes/mail_config.php, que
//            está en .gitignore. Decir «enviada» ahí sería mentir y la
//            persona esperaría un correo que no existe.
//   error    no se creó nada, y `motivo` dice por qué
$resultados = [];
foreach ($emails as $email) {
    $fallo = null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fallo = 'El correo no es válido.';
    } elseif (!correoConDominioReal($email)) {
        // El dominio tiene que existir de verdad. Con sólo filter_var,
        // una invitación a @fake.com se daba por enviada y no llegaba.
        $fallo = 'Ese dominio de correo no existe.';
    } else {
        // No invitar a quien ya está dentro: la invitación se crearía,
        // el correo saldría y al abrirlo no pasaría nada visible.
        $yaEsta->execute([$planId, $email]);
        if ($yaEsta->fetch()) $fallo = 'Ya está en el viaje.';
    }

    if ($fallo !== null) {
        $resultados[] = ['email' => $email, 'estado' => 'error', 'motivo' => $fallo];
        continue;
    }

    $enviado = false;
    $link = planInviteCreate($planId, $rol, $email, 1, $enviado, $mensaje);
    if (!$link) {
        $resultados[] = ['email' => $email, 'estado' => 'error', 'motivo' => 'No se pudo crear la invitación.'];
        continue;
    }
    // El enlace NO se devuelve: esta invitación es de un solo uso y va
    // dirigida a esa persona. Quien quiera algo para repartir tiene el
    // enlace de compartir, que la ventana enseña arriba.
    $resultados[] = ['email' => $email, 'estado' => $enviado ? 'enviado' : 'creado'];
}

apiJson(['ok' => true, 'resultados' => $resultados]);
