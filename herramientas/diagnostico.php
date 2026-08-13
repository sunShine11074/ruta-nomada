<?php
// ============================================================
//  herramientas/diagnostico.php — ¿Por qué no funciona? | Ruta Nómada
//
//  Ejecutar desde la terminal, NO desde el navegador:
//      php herramientas/diagnostico.php
//
//  Sin salir a internet (más rápido, cero peticiones a Google):
//      php herramientas/diagnostico.php --sin-red
//
//  Revisa, en orden, todo lo que hace falta para que la aplicación
//  arranque en una copia recién descargada: PHP y sus extensiones, los
//  cuatro archivos de configuración, la base de datos con TODAS sus
//  tablas y columnas, la salida a internet desde PHP, y una llamada de
//  prueba a cada servicio externo.
//
//  No toca nada: sólo lee y consulta.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta herramienta es sólo para la terminal:  php herramientas/diagnostico.php\n");
}

$raiz  = dirname(__DIR__);
$conRed = !in_array('--sin-red', $argv, true);
$ok = 0; $avisos = 0; $fallos = 0;

function linea(string $estado, string $txt, string $ayuda = ''): void
{
    global $ok, $avisos, $fallos;
    $marca = ['ok' => '  [ok]  ', 'aviso' => '  [!]   ', 'falla' => '  [X]   '][$estado];
    if ($estado === 'ok') $ok++; elseif ($estado === 'aviso') $avisos++; else $fallos++;
    echo $marca . $txt . PHP_EOL;
    if ($ayuda !== '') foreach (explode("\n", $ayuda) as $l) echo '          ' . $l . PHP_EOL;
}

/** GET/POST de prueba. Devuelve [codigoHttp, cuerpo, errorDeRed]. */
function tocar(string $url, array $cab = [], ?string $post = null): array
{
    if (!function_exists('curl_init')) return [0, '', 'la extensión curl de PHP no está activada'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $cab,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $cuerpo = curl_exec($ch);
    $http   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);
    return [$http, (string)$cuerpo, $err];
}

/** El fallo típico de XAMPP recién instalado en Windows. */
function esFalloDeCertificados(string $err): bool
{
    $e = strtolower($err);
    return strpos($e, 'certificate') !== false
        || strpos($e, 'ssl') !== false
        || strpos($e, 'cacert') !== false;
}

const AYUDA_CERTIFICADOS =
    "PHP no puede validar certificados HTTPS: le faltan los certificados raíz.\n"
  . "Es el fallo clásico de un XAMPP recién instalado en Windows, y deja sin\n"
  . "funcionar TODO lo que el servidor pide por internet: las rutas del mapa,\n"
  . "las listas de país/estado/ciudad, el asistente y los correos.\n"
  . "Arreglo (una sola vez):\n"
  . "  1. Baja https://curl.se/ca/cacert.pem\n"
  . "  2. Guárdalo como  C:\\xampp\\php\\extras\\ssl\\cacert.pem\n"
  . "  3. En C:\\xampp\\php\\php.ini, en la sección [curl], deja estas dos líneas\n"
  . "     SIN el punto y coma delante:\n"
  . "       curl.cainfo = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"\n"
  . "       openssl.cafile = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"\n"
  . "  4. Reinicia Apache desde el panel de XAMPP.";

echo PHP_EOL . 'Ruta Nómada — diagnóstico de instalación' . PHP_EOL;
echo str_repeat('=', 58) . PHP_EOL;
if (!$conRed) echo '  (modo --sin-red: no se consulta ningún servicio externo)' . PHP_EOL;
echo PHP_EOL;

// ── 1. PHP y extensiones ────────────────────────────────────
echo '1. PHP y extensiones' . PHP_EOL;
version_compare(PHP_VERSION, '8.0', '>=')
    ? linea('ok', 'PHP ' . PHP_VERSION)
    : linea('falla', 'PHP ' . PHP_VERSION, 'El proyecto necesita PHP 8.0 o superior.');

$exts = [
    'curl'      => 'Sin ella no hay rutas, ni listas de ciudades, ni asistente.',
    'openssl'   => 'Sin ella no se puede hablar por HTTPS con ningún servicio.',
    'pdo_mysql' => 'Sin ella no hay base de datos: la aplicación no arranca.',
    'mbstring'  => 'Sin ella los acentos y las eñes se rompen.',
];
foreach ($exts as $ext => $porque) {
    extension_loaded($ext)
        ? linea('ok', 'extensión ' . $ext)
        : linea('falla', 'extensión ' . $ext . ' — NO está activada',
            $porque . "\nEn C:\\xampp\\php\\php.ini quita el ';' de  extension=" . $ext . "  y reinicia Apache.");
}

// ── 2. Claves y configuración ───────────────────────────────
echo PHP_EOL . '2. Claves y configuración' . PHP_EOL;
$configs = [
    'maps_config.php' => ['clave' => 'maps_key',   'que' => 'Google Maps',      'rompe' => 'El mapa, las rutas y la búsqueda de lugares no funcionan.'],
    'ai_config.php'   => ['clave' => 'gemini_key', 'que' => 'Gemini (asistente)','rompe' => 'El asistente contesta que no está configurado.'],
    'geo_config.php'  => ['clave' => 'csc_key',    'que' => 'CountryStateCity',  'rompe' => 'Las listas de país / estado / ciudad salen vacías.'],
    'mail_config.php' => ['clave' => 'password',   'que' => 'Correo (SMTP)',     'rompe' => 'No se envían invitaciones ni recuperación de contraseña.'],
    'pexels_config.php' => ['clave' => 'api_key',  'que' => 'Pexels (fotos)',    'rompe' => 'La pestaña "Buscar en la web" no devuelve nada y los viajes nuevos nacen sin portada.'],
];
$claves = [];
foreach ($configs as $archivo => $info) {
    $ruta   = $raiz . '/includes/' . $archivo;
    $plantilla = $raiz . '/includes/' . str_replace('.php', '.sample.php', $archivo);

    // Error muy común: pegar la clave DENTRO de la plantilla .sample en vez
    // de crear el archivo de verdad. La aplicación no lee las plantillas.
    $plantillaTocada = false;
    if (is_file($plantilla)) {
        $t = (string)file_get_contents($plantilla);
        $plantillaTocada = strpos($t, 'PON_AQUI') === false;
    }

    if (!is_file($ruta)) {
        $ayuda = 'Falta. Cópialo de la plantilla:' . "\n"
               . '    copy includes\\' . basename($plantilla) . ' includes\\' . $archivo . "\n"
               . 'y pega la clave dentro. ' . $info['rompe'];
        if ($plantillaTocada) {
            $ayuda = 'OJO: parece que pegaste la clave dentro de ' . basename($plantilla) . '.' . "\n"
                   . 'La aplicación NUNCA lee los .sample: son sólo plantillas. Hay que' . "\n"
                   . 'crear el archivo sin ".sample":' . "\n"
                   . '    copy includes\\' . basename($plantilla) . ' includes\\' . $archivo . "\n"
                   . $info['rompe'];
        }
        linea(is_file($plantilla) ? 'falla' : 'aviso', $info['que'] . ' — includes/' . $archivo, $ayuda);
        continue;
    }
    $cfg = @include $ruta;
    $val = is_array($cfg) ? trim((string)($cfg[$info['clave']] ?? '')) : '';
    if ($val === '' || strpos($val, 'PON_AQUI') === 0) {
        linea('falla', $info['que'] . ' — includes/' . $archivo,
            'El archivo existe pero la clave sigue sin rellenar. ' . $info['rompe']);
    } else {
        $claves[$info['clave']] = $val;
        linea('ok', $info['que'] . ' — includes/' . $archivo . '  (clave de ' . strlen($val) . ' caracteres)');
    }
}

// ── 3. Base de datos ────────────────────────────────────────
echo PHP_EOL . '3. Base de datos' . PHP_EOL;
$pdo = null;
try {
    require_once $raiz . '/db.php';
    $pdo = getDB();
    linea('ok', 'Conexión establecida');
} catch (Throwable $e) {
    linea('falla', 'No se pudo conectar',
        'Revisa db.php y que MySQL esté encendido en el panel de XAMPP.' . "\n" . $e->getMessage());
}

if ($pdo) {
    // La lista sale de basedatos/instalar.sql, no escrita a mano: si mañana
    // se añade una tabla, esta comprobación se entera sola. Escribirla a
    // mano fue justamente lo que dejó pasar que faltaran tramo_cache y
    // ruta_uso, y con ellas las rutas reales del itinerario.
    $sql = @file_get_contents($raiz . '/basedatos/instalar.sql');
    $esperadas = [];
    if ($sql !== false) {
        preg_match_all('/CREATE TABLE(?:\s+IF NOT EXISTS)?\s+`?([A-Za-z_][A-Za-z0-9_]*)`?/i', $sql, $m);
        $esperadas = array_values(array_unique($m[1]));
    }
    if (!$esperadas) {
        linea('aviso', 'No se pudo leer basedatos/instalar.sql para saber qué tablas esperar');
    } else {
        $hay    = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $faltan = array_values(array_diff($esperadas, $hay));
        if ($faltan) {
            $rutas = array_intersect($faltan, ['tramo_cache', 'ruta_uso']);
            $extra = $rutas
                ? "\n" . 'Sin tramo_cache y ruta_uso las rutas del itinerario salen PUNTEADAS:'
                       . "\n" . 'api/ruta.php falla antes de llegar a Google y el mapa dibuja rectas.'
                : '';
            linea('falla', 'Faltan ' . count($faltan) . ' tablas: ' . implode(', ', $faltan),
                'Si quieres conservar tus datos:' . "\n"
                . '    mysql -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"' . "\n"
                . 'Si da igual empezar de cero:' . "\n"
                . '    mysql -u root ruta_nomada -e "source basedatos/instalar.sql"' . $extra);
        } else {
            linea('ok', 'Están las ' . count($esperadas) . ' tablas que crea instalar.sql');
        }
    }

    // Columnas que llegaron por migración y suelen faltar en copias viejas
    $hay = $hay ?? $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('plan_items', $hay, true)) {
        $cols   = $pdo->query('SHOW COLUMNS FROM plan_items')->fetchAll(PDO::FETCH_COLUMN);
        $nuevas = ['modo_viaje', 'moneda', 'gasto_cat', 'gasto_desc', 'gasto_modo'];
        $sin    = array_values(array_diff($nuevas, $cols));
        $sin
            ? linea('falla', 'plan_items sin las columnas: ' . implode(', ', $sin),
                'Tu base es de una versión anterior. Ponla al día sin perder datos:' . "\n"
                . '    mysql -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"')
            : linea('ok', 'plan_items tiene las columnas de horario y gasto');
    }

    // Igual con plan_invitaciones. OJO: la comprobación de tablas de
    // arriba NO basta aquí. Esta tabla existe desde hace meses, así que
    // una copia vieja pasa aquel control y aun así falla al invitar,
    // porque lo que le faltan son COLUMNAS.
    if (in_array('plan_invitaciones', $hay, true)) {
        $cols   = $pdo->query('SHOW COLUMNS FROM plan_invitaciones')->fetchAll(PDO::FETCH_COLUMN);
        $nuevas = ['usos', 'usos_max', 'token_claro'];
        $sin    = array_values(array_diff($nuevas, $cols));
        $sin
            ? linea('falla', 'plan_invitaciones sin las columnas: ' . implode(', ', $sin),
                'La ventana "Invita a compañeros de viaje" no podrá dar el enlace.' . "\n"
                . 'Ponla al día sin perder datos:' . "\n"
                . '    mysql -u root ruta_nomada -e "source basedatos/actualizar_bd.sql"')
            : linea('ok', 'plan_invitaciones admite enlaces para compartir (usos, usos_max, token_claro)');
    }

    // ── Rutinas: funciones, procedimientos y triggers ────────
    // Igual que con las tablas, la lista NO va escrita a mano: se lee
    // de basedatos/rutinas.sql, que es el archivo canónico. Si mañana
    // se añade una rutina, esta comprobación se entera sola.
    $sqlRut = @file_get_contents($raiz . '/basedatos/rutinas.sql');
    if ($sqlRut === false) {
        linea('aviso', 'No se pudo leer basedatos/rutinas.sql para saber qué rutinas esperar');
    } else {
        preg_match_all('/CREATE\s+(FUNCTION|PROCEDURE|TRIGGER)\s+`?([A-Za-z_][A-Za-z0-9_]*)`?/i', $sqlRut, $m);
        $espRut = [];
        foreach ($m[1] as $i => $tipo) $espRut[strtoupper($tipo)][] = $m[2][$i];

        $hayRut = $pdo->query(
            "SELECT ROUTINE_TYPE t, ROUTINE_NAME n FROM information_schema.ROUTINES
              WHERE ROUTINE_SCHEMA = DATABASE()
              UNION ALL
             SELECT 'TRIGGER', TRIGGER_NAME FROM information_schema.TRIGGERS
              WHERE TRIGGER_SCHEMA = DATABASE()"
        )->fetchAll();
        $vivas = [];
        foreach ($hayRut as $r) $vivas[strtoupper($r['t'])][] = $r['n'];

        $faltanRut = [];
        foreach ($espRut as $tipo => $nombres) {
            foreach (array_diff($nombres, $vivas[$tipo] ?? []) as $n) $faltanRut[] = strtolower($tipo) . ' ' . $n;
        }
        if ($faltanRut) {
            linea('falla', 'Faltan ' . count($faltanRut) . ' rutinas: ' . implode(', ', $faltanRut),
                'Sin ellas fallan el registro, crear/borrar planes y mis_planes.php.' . "\n"
                . 'Se instalan sin perder datos (o con doble clic en herramientas\actualizar.bat):' . "\n"
                . '    mysql -u root ruta_nomada -e "source basedatos/rutinas.sql"');
        } else {
            $n = count($espRut['FUNCTION'] ?? []) + count($espRut['PROCEDURE'] ?? []) + count($espRut['TRIGGER'] ?? []);
            linea('ok', 'Están las ' . $n . ' rutinas de rutinas.sql (funciones, procedimientos y triggers)');
        }
    }
}

// ── 4. Salida a internet desde PHP ──────────────────────────
echo PHP_EOL . '4. Salida a internet desde PHP' . PHP_EOL;
$redOk = false;
if (!$conRed) {
    linea('aviso', 'Omitido por --sin-red');
} else {
    [$http, , $err] = tocar('https://www.gstatic.com/generate_204');
    if ($err !== '' && esFalloDeCertificados($err)) {
        linea('falla', 'HTTPS bloqueado por los certificados', AYUDA_CERTIFICADOS . "\n(" . $err . ')');
    } elseif ($err !== '') {
        linea('falla', 'Sin salida a internet', 'cURL dijo: ' . $err . "\n"
            . 'Mira si un antivirus, un proxy o el firewall bloquean a PHP.');
    } elseif ($http === 204 || $http === 200) {
        $redOk = true;
        linea('ok', 'PHP puede hablar por HTTPS');
    } else {
        linea('aviso', 'Respuesta inesperada al probar la salida (HTTP ' . $http . ')');
    }
}

// ── 5. Los servicios, uno por uno ───────────────────────────
echo PHP_EOL . '5. Servicios externos' . PHP_EOL;
if (!$conRed) {
    linea('aviso', 'Omitido por --sin-red');
} elseif (!$redOk) {
    linea('aviso', 'No se prueban: PHP no tiene salida a internet (mira el punto 4)');
} else {
    // 5a. Routes API — es EXACTAMENTE lo que decide si las rutas del
    // itinerario salen sólidas o punteadas. Una petición; el SKU
    // Essentials regala 10,000 al mes.
    if (isset($claves['maps_key'])) {
        [$http, $cuerpo, $err] = tocar(
            'https://routes.googleapis.com/directions/v2:computeRoutes',
            ['Content-Type: application/json',
             'X-Goog-Api-Key: ' . $claves['maps_key'],
             'X-Goog-FieldMask: routes.legs.distanceMeters'],
            json_encode([
                'origin'      => ['location' => ['latLng' => ['latitude' => 32.6245, 'longitude' => -115.4523]]],
                'destination' => ['location' => ['latLng' => ['latitude' => 32.6519, 'longitude' => -115.4683]]],
                'travelMode'  => 'DRIVE',
                'routingPreference' => 'TRAFFIC_UNAWARE',
            ])
        );
        if ($http === 200) {
            linea('ok', 'Routes API — las rutas del itinerario se pueden calcular');
        } else {
            $msg = '';
            $j = json_decode($cuerpo, true);
            if (isset($j['error']['message'])) $msg = $j['error']['message'];
            $pista = 'Mientras esto falle, las rutas del mapa salen PUNTEADAS.' . "\n";
            if (stripos($msg, 'not been used') !== false || stripos($msg, 'disabled') !== false || $http === 403) {
                $pista .= 'La API no está habilitada para ese proyecto de Google Cloud.' . "\n"
                        . 'Console → APIs y servicios → Biblioteca → busca "Routes API" → Habilitar.';
            } elseif ($http === 400) {
                $pista .= 'Google rechazó la petición. Suele ser la clave mal copiada' . "\n"
                        . '(sobra un espacio o un salto de línea al final).';
            } else {
                $pista .= 'HTTP ' . $http . ($msg ? ' — ' . $msg : '');
            }
            linea('falla', 'Routes API (HTTP ' . $http . ')', $pista);
        }
    } else {
        linea('aviso', 'Routes API — no se prueba: falta la clave de Maps');
    }

    // 5b. CountryStateCity — las listas de país / estado / ciudad
    if (isset($claves['csc_key'])) {
        [$http, , ] = tocar('https://api.countrystatecity.in/v1/countries',
            ['X-CSCAPI-KEY: ' . $claves['csc_key']]);
        $http === 200
            ? linea('ok', 'CountryStateCity — las listas de país/estado/ciudad responden')
            : linea('falla', 'CountryStateCity (HTTP ' . $http . ')',
                'Las listas de país / estado / ciudad van a salir vacías.' . "\n"
                . ($http === 401 ? 'La clave no es válida o caducó: sácate otra en countrystatecity.in' : ''));
    } else {
        linea('aviso', 'CountryStateCity — no se prueba: falta la clave');
    }

    // 5c. Pexels — las fotos de portada. Se pide UNA sola imagen: la
    // cuota gratuita es de 200 por hora y 25.000 al mes, así que un
    // diagnóstico de vez en cuando no la mueve.
    if (isset($claves['api_key'])) {
        [$http, $cuerpo, ] = tocar('https://api.pexels.com/v1/search?query=oaxaca&per_page=1',
            ['Authorization: ' . $claves['api_key']]);
        if ($http === 200 && strpos((string)$cuerpo, '"photos"') !== false) {
            linea('ok', 'Pexels — las fotos de portada se pueden buscar');
        } elseif ($http === 401) {
            linea('falla', 'Pexels (HTTP 401)',
                'La clave no vale. Revisa que la copiaste entera y sin espacios' . "\n"
                . 'al final, o sácate otra en pexels.com/api');
        } elseif ($http === 429) {
            linea('aviso', 'Pexels (HTTP 429) — cuota agotada por ahora',
                'Son 200 peticiones por hora. Espera un rato y vuelve a probar.');
        } else {
            linea('falla', 'Pexels (HTTP ' . $http . ')',
                'La pestaña "Buscar en la web" no devolverá fotos y los viajes' . "\n"
                . 'nuevos nacerán sin portada.');
        }
    } else {
        linea('aviso', 'Pexels — no se prueba: falta la clave');
    }
}

// ── 5bis. La carpeta donde se guardan las fotos subidas ──────
// api/fotos.php escribe aquí. Si no existe o no se puede escribir, la
// pestaña "Tus fotos" acepta el archivo y luego falla al moverlo.
echo PHP_EOL . '5b. Carpeta de fotos subidas' . PHP_EOL;
$portadas = $raiz . '/img/portadas';
if (!is_dir($portadas)) {
    linea('falla', 'img/portadas/ no existe',
        'Debería venir en el repositorio con un .gitkeep dentro. Créala a mano:' . "\n"
        . '    mkdir img\\portadas');
} elseif (!is_writable($portadas)) {
    linea('falla', 'img/portadas/ existe pero no se puede escribir',
        'Subir una foto desde el dispositivo va a fallar.');
} else {
    linea('ok', 'img/portadas/ existe y se puede escribir');
}

// ── 6. Archivos que tienen que viajar en el repo ────────────
echo PHP_EOL . '6. Archivos necesarios' . PHP_EOL;
foreach (['libs/dc/support.js'          => 'Sin esto la vista del plan sale en blanco.',
          'libs/PHPMailer/PHPMailer.php' => 'Sin esto falla el envío de correo.',
          'js/emojis.js'                 => 'Sin esto el selector de emojis sale vacío.',
          'basedatos/instalar.sql'       => 'Es el archivo que crea la base de datos.'] as $f => $porque) {
    is_file($raiz . '/' . $f) ? linea('ok', $f) : linea('falla', $f . ' — NO ESTÁ', $porque);
}

// ── 7. Lo que sólo se ve en el navegador ────────────────────
// El mapa y la búsqueda de lugares usan la Maps JavaScript API, que corre
// en el navegador. No se puede comprobar desde aquí: una clave restringida
// por referente funciona en el navegador y falla desde la terminal, así
// que probarla aquí daría una falsa alarma. Se explica qué mirar.
echo PHP_EOL . '7. El mapa y la búsqueda de lugares (sólo desde el navegador)' . PHP_EOL;
echo '        Abre el plan, pulsa F12 → pestaña "Console" y busca en rojo:' . PHP_EOL;
echo PHP_EOL;
echo '          ApiNotActivatedMapError   falta habilitar una API en Google Cloud' . PHP_EOL;
echo '                                    (Maps JavaScript API, Places API,' . PHP_EOL;
echo '                                     Places API (New), Geocoding API)' . PHP_EOL;
echo '          RefererNotAllowedMapError la clave está restringida a otro sitio;' . PHP_EOL;
echo '                                    añade  http://localhost/*' . PHP_EOL;
echo '          InvalidKeyMapError        la clave está mal copiada' . PHP_EOL;
echo '          BillingNotEnabledMapError el proyecto no tiene facturación activa' . PHP_EOL;
echo PHP_EOL;
echo '        Si NO sale ninguno y el buscador de destino sigue sin sugerir' . PHP_EOL;
echo '        ciudades, mira la pestaña "Network": las peticiones a' . PHP_EOL;
echo '        maps.googleapis.com dicen qué respondió Google.' . PHP_EOL;

// ── Resumen ─────────────────────────────────────────────────
echo PHP_EOL . str_repeat('=', 58) . PHP_EOL;
printf('  %d correctos · %d avisos · %d fallos%s', $ok, $avisos, $fallos, PHP_EOL);
echo $fallos === 0
    ? '  Todo listo. Abre  http://localhost/' . basename($raiz) . '/' . PHP_EOL
    : '  Corrige los [X] y vuelve a ejecutar esta herramienta.' . PHP_EOL;
echo PHP_EOL;
