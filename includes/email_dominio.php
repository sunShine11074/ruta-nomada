<?php
// ============================================================
//  includes/email_dominio.php — ¿Ese dominio puede recibir
//  correo? | Ruta Nómada
//
//  QUÉ HACE Y QUÉ NO
//  Pregunta al DNS si el dominio de un correo tiene por dónde
//  recibirlo. Caza erratas —gmial.com, hotmial.com— y dominios
//  inventados. NO comprueba que el buzón exista.
//
//  ESTO NO ES UNA VALIDACIÓN DE SEGURIDAD, ES UN CAZA-ERRATAS
//  Se comprobó el 11/08/2026: fake.com y luis.com, los dos correos
//  "falsos" que aparecieron en la base, tienen MX de verdad
//  (Microsoft 365 y ProtonMail). Esta función los acepta, y hace
//  bien: son dominios que reciben correo. Lo que no existe es el
//  buzón, y eso sólo lo prueba mandar un correo y esperar respuesta.
//
//  Sirve para avisar de la errata en el momento, en vez de dejar a
//  alguien esperando un correo de confirmación que nunca llegará.
//
//  ANTE LA DUDA, ACEPTA
//  Un DNS lento, sin red o una extensión que falta no pueden impedir
//  que alguien se registre. Todas las salidas dudosas devuelven true.
// ============================================================

// El dominio de un correo, en minúsculas y sin el punto final.
// Se busca la ÚLTIMA arroba: la parte local puede llevar arrobas
// entre comillas y es la de más a la derecha la que separa.
function dominioDeCorreo(string $email): string
{
    $at = strrpos($email, '@');
    if ($at === false) return '';
    return rtrim(strtolower(substr($email, $at + 1)), '.');
}

// ¿Hay algún servidor que acepte correo para este dominio?
function dominioRecibeCorreo(string $dominio): bool
{
    static $cache = [];

    $dominio = rtrim(strtolower(trim($dominio)), '.');
    if ($dominio === '') return false;
    if (isset($cache[$dominio])) return $cache[$dominio];

    // Un dominio de correo entregable siempre tiene un punto: nadie
    // recibe correo en "localhost" desde internet. Los literales de
    // IP —juan@[192.168.1.1]— son válidos según el RFC pero el DNS no
    // los resuelve; se dejan pasar por no ser el caso que se persigue.
    if ($dominio[0] === '[' || strpos($dominio, '.') === false) {
        return $cache[$dominio] = ($dominio[0] === '[');
    }

    // Dominios con acentos o eñes. Habría que convertirlos a punycode
    // con idn_to_ascii(), pero la extensión intl NO está cargada en
    // este XAMPP. Sin ella, checkdnsrr() no los resolvería y se
    // rechazarían dominios perfectamente válidos: se aceptan.
    if (preg_match('/[^\x20-\x7E]/', $dominio)) {
        return $cache[$dominio] = true;
    }

    // 1. Lo normal: un registro MX. Se pide el registro entero y no un
    //    simple checkdnsrr() para poder mirar a dónde apunta.
    $mx = @dns_get_record($dominio, DNS_MX);
    if (is_array($mx) && $mx) {
        $reales = 0;
        foreach ($mx as $r) {
            $destino = rtrim((string)($r['target'] ?? ''), '.');
            // "MX ." es el null MX del RFC 7505: el dominio DECLARA que
            // no recibe correo. checkdnsrr() lo daría por bueno porque
            // el registro existe. Visto en yahooo.com el 11/08/2026.
            if ($destino !== '') $reales++;
        }
        // Con al menos un destino de verdad, recibe correo.
        // Si todos son null MX, el propio dominio dice que no: se
        // rechaza sin preguntar por A, porque el null MX manda.
        return $cache[$dominio] = ($reales > 0);
    }

    // 2. Sin MX, el RFC 5321 §5.1 dice que se entregue al host del
    //    registro A (o AAAA). Es raro, pero pasa en dominios pequeños,
    //    y omitirlo rechazaría correo que sí funciona.
    if (@checkdnsrr($dominio, 'A'))    return $cache[$dominio] = true;
    if (@checkdnsrr($dominio, 'AAAA')) return $cache[$dominio] = true;

    // 3. El canario. checkdnsrr() devuelve false por dos motivos que
    //    no sabe distinguir: que el dominio no tenga registros, o que
    //    no haya DNS. Se pregunta por uno que SIEMPRE responde: si
    //    tampoco contesta, el problema es la red, no el dominio, y no
    //    hay derecho a rechazar a nadie.
    if (!@checkdnsrr('gmail.com', 'MX')) return $cache[$dominio] = true;

    return $cache[$dominio] = false;
}

// Atajo para los formularios: recibe el correo entero.
function correoConDominioReal(string $email): bool
{
    $d = dominioDeCorreo($email);
    return $d === '' ? false : dominioRecibeCorreo($d);
}
