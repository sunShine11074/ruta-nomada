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

// La IP de quien pide, como la ve Apache.
//
// A propósito NO se mira X-Forwarded-For ni X-Real-IP: son cabeceras
// que envía el propio cliente y se pueden inventar. Si el freno por IP
// se fiara de ellas, saltárselo sería tan fácil como mandar una
// cabecera distinta en cada intento. El día que esto viva detrás de un
// proxy de verdad habrá que leerlas, pero SOLO confiando en el proxy.
function ipCliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return mb_substr((string)$ip, 0, 45);
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
