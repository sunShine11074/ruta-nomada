<?php
// ============================================================
//  includes/plan_invite_lib.php — Invitaciones a planes
//
//  DOS TIPOS DE INVITACIÓN, UNA SOLA TABLA
//
//    · Invitación POR CORREO — dirigida a alguien concreto, UN uso.
//      La crean el campo de correo de la ventana «Invita a compañeros
//      de viaje» y api/plan_create.php. En la base sólo se guarda el
//      SHA-256 del token, como los restablecimientos de contraseña.
//
//    · Enlace PARA COMPARTIR — sin destinatario, SIN límite de usos,
//      uno por plan. Es el que enseña la ventana al abrirse, para
//      pegarlo en un grupo. De este SÍ se guarda el token legible en
//      `token_claro`, porque la ventana tiene que poder volver a
//      enseñarlo cada vez que se abre y de un hash no se saca.
//      El razonamiento completo, con las alternativas descartadas,
//      está en basedatos/migrate_invitar.sql.
// ============================================================
require_once __DIR__ . '/../db.php';

// ── ¿Me puedo fiar de este Host? ─────────────────────────────
//
// EL ATAQUE QUE ESTO CIERRA se llama «host header poisoning», y no es
// teórico: cualquiera puede mandar
//     POST /forgot-password.php   Host: servidor-del-atacante.com
//     email=ana@…
// y el correo LEGÍTIMO que le llega a Ana —remitente de siempre, todo
// correcto— lleva dentro
//     http://servidor-del-atacante.com/reset-password.php?token=<token real>
// Ana pincha y entrega su token de restablecimiento. Apache no lo
// impide: httpd.conf pone ServerName localhost:80 pero UseCanonicalName
// no aparece y de fábrica está en Off, así que HTTP_HOST es lo que
// mande el cliente.
//
// Hasta hoy nos salvaba de casualidad el 'base_url' escrito a mano de
// mail_config.php, que pisaba la detección. En cuanto alguien lo deja
// vacío —que es lo que recomienda mail_config.sample.php— el agujero
// se abre. Por eso el cerrojo va aquí y no en quien llama.
//
// LA REGLA, y hay que entenderla bien para no repetir el error de la
// primera versión: sólo pasa un Host que NO PUEDA RESOLVER A UNA
// MÁQUINA DEL ATACANTE. Los rangos privados y los nombres que sólo
// existen en la red local valen, porque quien pincha el enlace acaba
// en su propia red y no en la de nadie. Un dominio público NO vale
// aunque «suene» a nuestro, salvo que esté escrito a mano en la
// configuración.
//
// EL ERROR QUE ESTO CORRIGE (13/08/2026, mismo día)
// La primera versión aceptaba cualquier cosa acabada en '.ts.net'. Se
// razonó «.ts.net es Tailscale, o sea nuestro». Es falso: '.ts.net' es
// el dominio PÚBLICO de Tailscale, cualquiera abre una cuenta gratis y
// se lleva un 'loquesea.sutailnet.ts.net' que resuelve en el DNS
// público y trae certificado de Let's Encrypt. Es decir, el filtro
// dejaba pasar el dominio del atacante, con candado verde, que es
// exactamente el ataque que decía cerrar. Comprobado ejecutándolo:
// 'evil.tail1a2b3c.ts.net' pasaba igual que el nuestro.
//
// Ahora el tailnet propio se nombra a mano en mail_config.php, con
// 'tailnet' o metiendo el nombre entero en 'hosts_permitidos'.
//
// Un Host que no reconozcamos se cae a localhost. Un enlace a
// localhost es inútil, pero inútil es infinitamente mejor que
// apuntando al servidor de quien ataca.
function planHostDeConfianza(string $host, array $extra = [], string $tailnet = ''): bool
{
    // Fuera el puerto para comparar. Los literales IPv6 vienen entre
    // corchetes —[::1]:80— y ahí el separador es el corchete, no el ':'.
    $limpio = strtolower(trim($host));
    if ($limpio !== '' && $limpio[0] === '[') {
        $cierre = strpos($limpio, ']');
        $limpio = $cierre === false ? $limpio : substr($limpio, 1, $cierre - 1);
    } elseif (($dp = strrpos($limpio, ':')) !== false) {
        $limpio = substr($limpio, 0, $dp);
    }
    if ($limpio === '') return false;

    // Lo que el equipo haya añadido a mano en mail_config.php.
    foreach ($extra as $permitido) {
        if ($limpio === strtolower(trim((string)$permitido))) return true;
    }

    // La propia máquina.
    if (in_array($limpio, ['localhost', '127.0.0.1', '::1'], true)) return true;

    // NUESTRO tailnet de Tailscale, nombrado a mano — nunca el comodín.
    // El punto de delante importa: sin él, 'maltail82e9ec.ts.net' —que
    // alguien puede registrar— colaría por 'tail82e9ec.ts.net'.
    if ($tailnet !== '') {
        $sufijo = '.' . ltrim(strtolower(trim($tailnet)), '.');
        if (substr($limpio, -strlen($sufijo)) === $sufijo) return true;
    }

    // Rangos IPv4 que sólo existen dentro de una red privada.
    if (preg_match('/^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $limpio)) return true;
    if (preg_match('/^192\.168\.\d{1,3}\.\d{1,3}$/', $limpio)) return true;
    if (preg_match('/^172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}$/', $limpio)) return true;
    // 100.64.0.0/10: el rango que reparte Tailscale de verdad. Esta
    // máquina tiene la 100.82.212.28. Faltaba, así que entrar por la IP
    // que enseña «tailscale status» daba enlaces a localhost.
    if (preg_match('/^100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\.\d{1,3}\.\d{1,3}$/', $limpio)) return true;
    // 169.254.x.x: enlace local, cuando no hay quien reparta direcciones.
    if (preg_match('/^169\.254\.\d{1,3}\.\d{1,3}$/', $limpio)) return true;

    // IPv6 privado: fc00::/7 son las direcciones locales únicas —ahí cae
    // el fd7a:115c:a1e0::/48 de Tailscale, que es lo que tiene esta
    // máquina— y fe80::/10 el enlace local. Ninguna se enruta por
    // internet, así que nadie de fuera puede estar al otro lado.
    if (preg_match('/^f[cd][0-9a-f]{0,2}:/', $limpio)) return true;
    if (preg_match('/^fe[89ab][0-9a-f]?:/', $limpio)) return true;

    // Un nombre SIN PUNTOS sólo lo resuelve la red local: el nombre de
    // equipo de Windows, «DESKTOP-DE-RAMON». Y los .local son mDNS, que
    // viaja por multidifusión y tampoco sale de la red. Quien pinche un
    // enlace así acaba en su propia red, nunca en la de quien ataca.
    //
    // Los dos puntos tienen que descartarse aparte: UNA IPv6 TAMPOCO
    // TIENE PUNTOS, así que sin esta condición se colaba cualquier IPv6
    // pública. Lo cazó la prueba con 2001:4860:4860::8888, que es el DNS
    // de Google y pasaba el filtro. Las IPv6 privadas ya se admitieron
    // arriba, así que aquí sólo pueden quedar las de internet.
    if (strpos($limpio, '.') === false && strpos($limpio, ':') === false) return true;
    if (substr($limpio, -6) === '.local') return true;

    return false;
}

// ── La dirección base del proyecto ───────────────────────────
// Se deduce de la petición para que funcione se llame como se llame
// la carpeta: antes iba escrita a mano y quien clonaba el repositorio
// en otra carpeta recibía las invitaciones con un enlace roto.
// mail_config.php puede imponer otra con 'base_url', que es lo que
// haría falta el día que esto viva en un dominio de verdad.
//
// ES EL ÚNICO SITIO DEL PROYECTO QUE ARMA UNA URL ABSOLUTA. Si añades
// otro correo con un enlace dentro, llámalo desde aquí y no te montes
// la dirección por tu cuenta: forgot-password.php lo hacía y se le
// olvidó el valor de reserva.
function planInviteBase(): string
{
    $cfg = [];
    if (is_file(__DIR__ . '/mail_config.php')) {
        $leido = @include __DIR__ . '/mail_config.php';
        if (is_array($leido)) $cfg = $leido;
    }

    // Si hay 'base_url' escrito, manda y no hay nada que deducir.
    if (!empty($cfg['base_url'])) return rtrim($cfg['base_url'], '/');

    $base = 'http://localhost';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        if (!planHostDeConfianza(
            $host,
            (array)($cfg['hosts_permitidos'] ?? []),
            (string)($cfg['tailnet'] ?? '')
        )) {
            $host = 'localhost';
        }

        $esq = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $esq = 'https';
        } elseif (
            // Detrás de 'tailscale serve', de ngrok o de cloudflared, quien
            // termina el TLS es ese programa y a Apache le llega HTTP pelado:
            // sin esto los enlaces saldrían con http:// en un sitio https.
            // Sólo nos fiamos de la cabecera si quien conecta es la propia
            // máquina, que es de donde hablan esos programas. Un atacante
            // remoto no puede fingir un REMOTE_ADDR de bucle local.
            strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            && in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true)
        ) {
            $esq = 'https';
        }

        $raiz = str_replace('\\', '/', dirname(__DIR__));
        $doc  = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
        $sub  = ($doc !== '' && strpos($raiz, $doc) === 0) ? trim(substr($raiz, strlen($doc)), '/') : '';
        $ruta = $sub === '' ? '' : '/' . implode('/', array_map('rawurlencode', explode('/', $sub)));
        $base = $esq . '://' . $host . $ruta;
    }
    return $base;
}

function planInviteLink(string $token): string
{
    return planInviteBase() . '/plan_invitacion.php?token=' . $token;
}

// ── Crear una invitación ─────────────────────────────────────
// Devuelve el enlace en éxito o null en fallo.
//
// $usosMax: cuánta gente puede entrar por ella. 1 es lo de siempre;
//           null significa SIN LÍMITE y es lo que usa el enlace de
//           compartir.
// $enviado: sale a true SÓLO si el correo salió de verdad. Importa:
//           includes/mail_config.php está en .gitignore, así que en
//           las máquinas de los compañeros el envío falla siempre, y
//           sin este dato la ventana diría «invitación enviada»
//           mientras no ha salido nada.
function planInviteCreate(int $planId, string $rol, ?string $email = null, ?int $usosMax = 1, ?bool &$enviado = null): ?string
{
    $enviado = false;
    if (!in_array($rol, ['editor', 'lector'], true)) $rol = 'editor';
    $db = getDB();

    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    // Sólo el enlace de compartir guarda el token legible. Una
    // invitación dirigida a una persona no necesita volver a
    // enseñarse, así que sigue siendo únicamente hash.
    $claro = ($email === null) ? $token : null;

    try {
        $stmt = $db->prepare(
            'INSERT INTO plan_invitaciones (plan_id, token_hash, token_claro, rol, email, usos_max, expira_en)
             VALUES (?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        );
        $stmt->execute([$planId, $hash, $claro, $rol, $email ?: null, $usosMax]);
    } catch (PDOException $e) {
        error_log('planInviteCreate: ' . $e->getMessage());
        return null;
    }

    $link = planInviteLink($token);

    // Envío de correo (best-effort)
    if ($email) {
        $stmt = $db->prepare('SELECT nombre, destino FROM planes WHERE id = ? LIMIT 1');
        $stmt->execute([$planId]);
        $plan = $stmt->fetch() ?: ['nombre' => 'un viaje', 'destino' => ''];
        try {
            require_once __DIR__ . '/../mailer.php';
            $titulo   = htmlspecialchars($plan['nombre'], ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $enviado = enviarCorreo(
                $email,
                'Te invitaron a un plan de viaje — Ruta Nómada',
                "<h2 style=\"margin-top:0;\">¡Te invitaron a un viaje!</h2>
                 <p>Te invitaron a colaborar en <strong>{$titulo}</strong> en Ruta Nómada.</p>
                 <p style=\"text-align:center;margin:28px 0;\">
                   <a href=\"{$safeLink}\" style=\"display:inline-block;background:#1b3a40;color:#fff;
                   padding:12px 28px;border-radius:8px;font-weight:600;text-decoration:none\">Unirme al plan</a></p>
                 <p style=\"font-size:13px;color:#5b6b68;\">El enlace expira en <strong>7 días</strong>. Si no
                 esperabas esta invitación puedes ignorar este correo.</p>
                 <p style=\"font-size:12px;color:#8a9794;word-break:break-all;\">Si el botón no funciona,
                 copia y pega esta dirección en tu navegador:<br>{$safeLink}</p>",
                "Te invitaron a colaborar en \"{$plan['nombre']}\" en Ruta Nómada.\nAbre este enlace (expira en 7 días):\n{$link}"
            );
        } catch (Throwable $e) {
            error_log('planInviteCreate mail: ' . $e->getMessage());
            // la invitación queda creada aunque el correo falle
        }
    }
    return $link;
}

// ── El enlace para compartir de un plan ──────────────────────
// Devuelve el que ya existe y sigue vigente, y sólo crea uno si no
// hay ninguno. ESTE ES EL ÚNICO SITIO QUE DECIDE ENTRE REUTILIZAR Y
// CREAR, y por eso importa: la ventana pide el enlace cada vez que se
// abre, así que si aquí se creara uno nuevo sin mirar, cada apertura
// dejaría una credencial permanente más en la tabla.
//
// Caduca a los 7 días como todo lo demás. Cuando eso pase, la
// siguiente apertura crea uno nuevo y el viejo deja de valer: quien
// tenga pegado el enlace antiguo en un chat se queda fuera. Es el
// comportamiento de siempre; el botón de «rehacer enlace» sería el
// paso siguiente.
function planEnlaceDePlan(int $planId): ?string
{
    $db = getDB();
    try {
        $stmt = $db->prepare(
            'SELECT token_claro FROM plan_invitaciones
              WHERE plan_id = ? AND email IS NULL AND usos_max IS NULL
                AND token_claro IS NOT NULL
                AND (expira_en IS NULL OR expira_en > NOW())
              ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$planId]);
        $fila = $stmt->fetch();
        if ($fila && !empty($fila['token_claro'])) {
            return planInviteLink((string)$fila['token_claro']);
        }
    } catch (PDOException $e) {
        error_log('planEnlaceDePlan: ' . $e->getMessage());
        return null;
    }
    return planInviteCreate($planId, 'editor', null, null);
}
