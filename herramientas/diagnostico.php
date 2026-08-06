<?php
// ============================================================
//  herramientas/diagnostico.php — ¿Por qué no funciona? | Ruta Nómada
//
//  Ejecutar desde la terminal, NO desde el navegador:
//      php herramientas/diagnostico.php
//
//  Revisa, en orden, todo lo que hace falta para que la aplicación
//  arranque en una copia recién descargada: los cuatro archivos de
//  configuración, la conexión a la base de datos y las tablas.
//  Dice qué falta y qué copiar exactamente. No toca nada.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta herramienta es sólo para la terminal:  php herramientas/diagnostico.php\n");
}

$raiz = dirname(__DIR__);
$ok = 0; $avisos = 0; $fallos = 0;

function linea(string $estado, string $txt, string $ayuda = ''): void
{
    global $ok, $avisos, $fallos;
    $marca = ['ok' => '  [ok]  ', 'aviso' => '  [!]   ', 'falla' => '  [X]   '][$estado];
    if ($estado === 'ok') $ok++; elseif ($estado === 'aviso') $avisos++; else $fallos++;
    echo $marca . $txt . PHP_EOL;
    if ($ayuda !== '') foreach (explode("\n", $ayuda) as $l) echo '          ' . $l . PHP_EOL;
}

echo PHP_EOL . 'Ruta Nómada — diagnóstico de instalación' . PHP_EOL;
echo str_repeat('=', 52) . PHP_EOL . PHP_EOL;

// ── 1. Archivos de configuración ────────────────────────────
echo '1. Claves y configuración' . PHP_EOL;
$configs = [
    'maps_config.php' => ['clave' => 'maps_key', 'que' => 'Google Maps', 'rompe' => 'El mapa, las rutas y la búsqueda de lugares no funcionan.'],
    'ai_config.php'   => ['clave' => 'gemini_key', 'que' => 'Gemini (asistente)', 'rompe' => 'El asistente contesta que no está configurado.'],
    'geo_config.php'  => ['clave' => 'csc_key',  'que' => 'CountryStateCity', 'rompe' => 'Las listas de país / estado / ciudad salen vacías.'],
    'mail_config.php' => ['clave' => 'password', 'que' => 'Correo (SMTP)', 'rompe' => 'No se envían invitaciones ni recuperación de contraseña.'],
];
foreach ($configs as $archivo => $info) {
    $ruta = $raiz . '/includes/' . $archivo;
    $sample = $raiz . '/includes/' . str_replace('.php', '.sample.php', $archivo);
    if (!is_file($ruta)) {
        $ayuda = 'Falta. Cópialo de la plantilla:' . "\n"
               . '    copy includes\\' . basename($sample) . ' includes\\' . $archivo . "\n"
               . 'y pega la clave dentro. ' . $info['rompe'];
        linea(is_file($sample) ? 'falla' : 'aviso', $info['que'] . ' — includes/' . $archivo, $ayuda);
        continue;
    }
    $cfg = @include $ruta;
    $val = is_array($cfg) ? trim((string)($cfg[$info['clave']] ?? '')) : '';
    if ($val === '' || strpos($val, 'PON_AQUI') === 0) {
        linea('falla', $info['que'] . ' — includes/' . $archivo,
            'El archivo existe pero la clave sigue sin rellenar. ' . $info['rompe']);
    } else {
        linea('ok', $info['que'] . ' — includes/' . $archivo . '  (clave de ' . strlen($val) . ' caracteres)');
    }
}

// ── 2. Base de datos ────────────────────────────────────────
echo PHP_EOL . '2. Base de datos' . PHP_EOL;
$pdo = null;
try {
    require_once $raiz . '/db.php';
    $pdo = getDB();
    linea('ok', 'Conexión establecida');
} catch (Throwable $e) {
    linea('falla', 'No se pudo conectar', 'Revisa db.php y que MySQL esté encendido en el panel de XAMPP.' . "\n" . $e->getMessage());
}

if ($pdo) {
    $esperadas = ['usuarios', 'destinos', 'planes', 'plan_miembros', 'plan_items',
                  'plan_item_gasto', 'plan_item_reacciones', 'plan_gastos',
                  'plan_listas', 'plan_lista_items', 'plan_invitaciones'];
    $hay = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $faltan = array_values(array_diff($esperadas, $hay));
    if ($faltan) {
        linea('falla', 'Faltan tablas: ' . implode(', ', $faltan),
            'Importa la base completa:' . "\n" . '    mysql -u root ruta_nomada < basedatos/instalar.sql');
    } else {
        linea('ok', 'Están las ' . count($esperadas) . ' tablas principales');
    }

    // Columnas que llegaron por migración y suelen faltar en copias viejas
    if (in_array('plan_items', $hay, true)) {
        $cols = $pdo->query('SHOW COLUMNS FROM plan_items')->fetchAll(PDO::FETCH_COLUMN);
        $nuevas = ['modo_viaje', 'moneda', 'gasto_cat', 'gasto_desc', 'gasto_modo'];
        $sin = array_values(array_diff($nuevas, $cols));
        if ($sin) {
            linea('falla', 'plan_items sin las columnas: ' . implode(', ', $sin),
                'Tu base es de una versión anterior. Aplica:' . "\n"
                . '    mysql -u root ruta_nomada < basedatos/migrate_gasto_sitio.sql');
        } else {
            linea('ok', 'plan_items tiene las columnas de horario y gasto');
        }
    }
}

// ── 3. Archivos que tienen que viajar en el repo ────────────
echo PHP_EOL . '3. Archivos necesarios' . PHP_EOL;
foreach (['libs/dc/support.js' => 'Sin esto la vista del plan sale en blanco.',
          'libs/PHPMailer/PHPMailer.php' => 'Sin esto falla el envío de correo.',
          'js/emojis.js' => 'Sin esto el selector de emojis sale vacío.',
          'basedatos/instalar.sql' => 'Es el archivo que crea la base de datos.'] as $f => $porque) {
    is_file($raiz . '/' . $f) ? linea('ok', $f) : linea('falla', $f . ' — NO ESTÁ', $porque);
}

// ── Resumen ─────────────────────────────────────────────────
echo PHP_EOL . str_repeat('=', 52) . PHP_EOL;
printf('  %d correctos · %d avisos · %d fallos%s', $ok, $avisos, $fallos, PHP_EOL);
echo $fallos === 0
    ? '  Todo listo. Abre  http://localhost/Ruta Nómada (v1)/' . PHP_EOL
    : '  Corrige los [X] y vuelve a ejecutar esta herramienta.' . PHP_EOL;
echo PHP_EOL;
