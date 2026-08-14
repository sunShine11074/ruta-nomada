<?php
// ============================================================
//  includes/intentos.php — Freno a la fuerza bruta | Ruta Nómada
//
//  Envoltorio fino sobre las dos rutinas de basedatos/rutinas.sql:
//    · fn_login_bloqueado(email, ip)     → ¿hay que frenar?
//    · sp_registrar_intento(email, ip, exito)
//
//  La POLÍTICA (cuántos fallos, en cuánto tiempo) vive en la base, no
//  aquí. Así se cambia en un sitio y vale para cualquiera que consulte,
//  y de paso se puede auditar leyendo el SQL sin abrir el PHP.
// ============================================================

require_once __DIR__ . '/../db.php';

// La IP de quien pide.
//
// EL DÍA QUE ANUNCIABA EL COMENTARIO DE ANTES YA LLEGÓ (13/08/2026).
// Aquí ponía que NO se miraba X-Forwarded-For porque es una cabecera
// que manda el propio cliente y se puede inventar, y que el día que
// esto viviera detrás de un proxy de verdad habría que leerla «pero
// SOLO confiando en el proxy». Ese día es hoy: el equipo trabaja a
// través de «tailscale serve», que termina el TLS y habla con Apache
// por bucle local.
//
// Sin este cambio, Apache ve REMOTE_ADDR = 127.0.0.1 en TODAS las
// peticiones, así que el freno contaba a las cuatro personas como una
// sola: tres contraseñas falladas por cualquiera y quedaban todas
// bloqueadas, sin entender por qué.
//
// LA REGLA DE CONFIANZA, que es lo único que hace esto seguro:
// sólo se hace caso a la cabecera si quien abre la conexión es la
// PROPIA MÁQUINA. Un atacante remoto no puede fingir un REMOTE_ADDR de
// bucle local, y quien ya está dentro de la máquina no necesita
// saltarse este freno para nada. Es el mismo criterio que usa
// planInviteBase() en includes/plan_invite_lib.php para el esquema.
//
// Y SE COGE LA ÚLTIMA DE LA LISTA, no la primera. X-Forwarded-For es
// una cadena «cliente, proxy1, proxy2» y los proxys AÑADEN al final.
// Si el cliente se inventa una, queda a la izquierda y la que escribió
// el proxy queda a la derecha: la última es la única que no ha podido
// falsificar. Coger la primera —el error habitual— sería regalarle el
// salto del freno a quien mande la cabecera que quiera.
function ipCliente(): string
{
    $remota = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    if (in_array($remota, ['127.0.0.1', '::1'], true)) {
        $cadena = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($cadena !== '') {
            $trozos = array_map('trim', explode(',', $cadena));
            $ultima = end($trozos);
            // Los corchetes de un literal IPv6 sobran para guardarla.
            $ultima = trim((string)$ultima, '[]');
            if (filter_var($ultima, FILTER_VALIDATE_IP)) {
                return mb_substr($ultima, 0, 45);
            }
            // Cabecera presente pero ilegible: se ignora y se sigue con
            // la de Apache. Frenar de más es preferible a no frenar.
        }
    }

    return mb_substr($remota, 0, 45);
}

// ¿Se han pasado los intentos para este correo o esta IP?
// Ante cualquier fallo devuelve false: un problema con la base no debe
// dejar a nadie fuera de su cuenta. El freno es una protección, no un
// portero: si se cae, se pasa.
function loginBloqueado(PDO $db, string $email, string $ip): bool
{
    try {
        $st = $db->prepare('SELECT fn_login_bloqueado(?, ?)');
        $st->execute([$email, $ip]);
        return (int)$st->fetchColumn() === 1;
    } catch (PDOException $e) {
        error_log('loginBloqueado: ' . $e->getMessage());
        return false;
    }
}

// Deja constancia del intento. Un acierto borra los fallos previos de
// ese correo; de eso se encarga el procedimiento.
function loginRegistrar(PDO $db, string $email, string $ip, bool $exito): void
{
    try {
        $st = $db->prepare('CALL sp_registrar_intento(?, ?, ?)');
        $st->execute([$email, $ip, $exito ? 1 : 0]);
        $st->closeCursor();
    } catch (PDOException $e) {
        error_log('loginRegistrar: ' . $e->getMessage());
    }
}
