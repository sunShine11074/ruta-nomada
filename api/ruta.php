<?php
// ============================================================
//  api/ruta.php — Geometría de las rutas del itinerario | Ruta Nómada
//  POST {plan_id, dia:[{pid,lat,lng},...], modo?}
//   →  {ok:true, tramos:[{k, ok, pts:[[lat,lng],...], m, s}, ...],
//       fuente:'cache'|'google'|'recta', restantes:N}
//
//  Por qué esto vive en PHP y no en el navegador:
//   · La clave se queda en el servidor (includes/maps_config.php, fuera
//     del repo). Los servicios web de Google se restringen por IP, no
//     por referente, así que llamarlos desde el cliente obligaría a
//     dejar la clave sin la protección que sí tiene el mapa.
//   · La caché se comparte entre usuarios y planes: si dos personas
//     tienen el mismo restaurante seguido del mismo museo, se pide una vez.
//   · El tope de gasto sólo es real si lo cuenta el servidor.
//
//  COSTO: una petición cubre un día entero (Google factura por petición,
//  no por tramo), y de esa respuesta se guardan TODOS los tramos. El SKU
//  Essentials da 10,000 gratis al mes. Se queda en Essentials mientras
//  no se pasen 10 paradas intermedias ni se pida tráfico en vivo.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/maps_lib.php';

// Essentials admite 10 waypoints intermedios → 12 puntos por petición.
// Con 11 o más, Google cobra la tarifa Pro: el doble, y con la mitad de
// cuota gratis. Un día largo se parte en trozos antes que pagar Pro.
const RUTA_MAX_PUNTOS = 12;
const RUTA_TTL_DIAS   = 25;    // los términos permiten 30; se deja margen
const RUTA_TOPE_MES   = 2000;  // freno propio, muy por debajo de los 10,000 gratis

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
planAccess((int)($in['plan_id'] ?? 0), 'lector');

$MODOS = ['DRIVE', 'WALK', 'BICYCLE'];
$porDefecto = in_array(strtoupper((string)($in['modo'] ?? '')), $MODOS, true)
    ? strtoupper((string)$in['modo']) : 'DRIVE';
$dia = is_array($in['dia'] ?? null) ? array_values($in['dia']) : [];

// ── Puntos utilizables ───────────────────────────────────────
// Cada punto lleva el modo con el que se sale de él hacia el siguiente,
// porque el usuario puede ir a pie a un sitio y en coche al siguiente.
$pts = [];
foreach ($dia as $p) {
    $lat = isset($p['lat']) ? (float)$p['lat'] : null;
    $lng = isset($p['lng']) ? (float)$p['lng'] : null;
    if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) continue;
    $m = strtoupper((string)($p['modo'] ?? ''));
    $pts[] = [
        'pid'  => (string)($p['pid'] ?? ''),
        'lat'  => $lat,
        'lng'  => $lng,
        'modo' => in_array($m, $MODOS, true) ? $m : $porDefecto,
    ];
}
if (count($pts) < 2) apiJson(['ok' => true, 'tramos' => [], 'fuente' => 'recta']);

/** Clave de un tramo. Debe coincidir con _segKey() de js/plan_logic.js. */
function rutaId(array $p): string
{
    return $p['pid'] !== '' ? $p['pid'] : number_format($p['lat'], 5, '.', '') . ',' . number_format($p['lng'], 5, '.', '');
}
/**
 * Clave pública de un tramo, la misma que construye _segKey() en
 * js/plan_logic.js. Lleva el modo delante porque el mismo par de lugares
 * a pie y en coche son dos rutas distintas.
 */
function rutaClave(array $a, array $b, string $modo): string
{
    return $modo . '|' . rutaId($a) . '>' . rutaId($b);
}

$db = getDB();

// ── Lo que ya está en caché ──────────────────────────────────
// El modo forma parte de la clave: el mismo par a pie y en coche son dos
// geometrías distintas y no pueden compartir entrada de caché.
$claves = $hashes = $modosSeg = [];
for ($i = 0; $i + 1 < count($pts); $i++) {
    $modosSeg[] = $pts[$i]['modo'];
    $claves[]   = rutaClave($pts[$i], $pts[$i + 1], $pts[$i]['modo']);
    $hashes[]   = md5($claves[$i]);
}
$cache  = [];
if ($hashes) {
    $ph = implode(',', array_fill(0, count($hashes), '?'));
    $st = $db->prepare("SELECT hash, pts, ok, metros, segundos FROM tramo_cache
                         WHERE hash IN ($ph) AND creado > NOW() - INTERVAL " . RUTA_TTL_DIAS . " DAY");
    $st->execute($hashes);
    foreach ($st->fetchAll() as $r) $cache[$r['hash']] = $r;
}

$salida = static function (array $cache, array $claves, array $hashes, string $fuente) use ($db) {
    $tramos = [];
    foreach ($claves as $i => $k) {
        $c = $cache[$hashes[$i]] ?? null;
        $tramos[] = $c
            ? ['k' => $k, 'ok' => (int)$c['ok'], 'pts' => $c['pts'] ? json_decode($c['pts'], true) : null,
               'm' => $c['metros'] !== null ? (int)$c['metros'] : null,
               's' => $c['segundos'] !== null ? (int)$c['segundos'] : null]
            : ['k' => $k, 'ok' => 0, 'pts' => null, 'm' => null, 's' => null];
    }
    apiJson(['ok' => true, 'tramos' => $tramos, 'fuente' => $fuente]);
};

// Todo cacheado → ni una llamada a Google
if (count($cache) === count($hashes)) $salida($cache, $claves, $hashes, 'cache');

// ── Degradaciones: el mapa nunca se queda sin línea ──────────
if (mapsKey() === '') $salida($cache, $claves, $hashes, 'recta');

$mes = date('Y-m');
$db->prepare('INSERT INTO ruta_uso (mes, n) VALUES (?,0) ON DUPLICATE KEY UPDATE mes = mes')->execute([$mes]);
$usadas = (int)$db->query("SELECT n FROM ruta_uso WHERE mes = '$mes'")->fetchColumn();
if ($usadas >= RUTA_TOPE_MES) {
    error_log('[ruta] tope mensual alcanzado (' . $usadas . '), se sirven rectas');
    $salida($cache, $claves, $hashes, 'recta');
}

// ── Llamada a Routes API ─────────────────────────────────────
/**
 * Un tramo de hasta RUTA_MAX_PUNTOS puntos. Devuelve los legs o null.
 * Google factura por petición, así que pedir el trozo entero cuesta lo
 * mismo que pedir un solo par, y de paso rellena toda la caché.
 */
function rutaComputar(array $trozo, string $modo): ?array
{
    $puntoDe = static fn(array $p) => ['location' => ['latLng' => ['latitude' => $p['lat'], 'longitude' => $p['lng']]]];
    $cuerpo = [
        'origin'      => $puntoDe($trozo[0]),
        'destination' => $puntoDe($trozo[count($trozo) - 1]),
        'travelMode'  => $modo,
        // GeoJSON llega ya decodificado: no hace falta cargar la librería
        // 'geometry' ni escribir un decodificador de polilíneas.
        'polylineEncoding'  => 'GEO_JSON_LINESTRING',
        'languageCode' => 'es-MX',
        'regionCode'   => 'MX',
        'units'        => 'METRIC',
    ];
    // routingPreference SÓLO existe para vehículos: con WALK o BICYCLE la
    // API responde 400 "Routing preference cannot be set for WALK or
    // BICYCLE routing mode". TRAFFIC_AWARE además saltaría al SKU Pro, y
    // para un viaje futuro el tráfico de hoy tampoco dice gran cosa.
    if ($modo === 'DRIVE') $cuerpo['routingPreference'] = 'TRAFFIC_UNAWARE';

    $medio = array_slice($trozo, 1, -1);
    if ($medio) $cuerpo['intermediates'] = array_map($puntoDe, $medio);

    $ch = curl_init('https://routes.googleapis.com/directions/v2:computeRoutes');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . mapsKey(),
            // Sin FieldMask la petición se rechaza. Pedir sólo lo justo
            // también abarata la respuesta.
            'X-Goog-FieldMask: routes.legs.polyline.geoJsonLinestring,routes.legs.distanceMeters,routes.legs.duration',
        ],
        CURLOPT_POSTFIELDS     => json_encode($cuerpo, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res  = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($res === false) { error_log('[ruta] red: ' . $err); return null; }
    if ($http !== 200)  { error_log('[ruta] HTTP ' . $http . ' ' . mb_substr((string)$res, 0, 400)); return null; }

    $j = json_decode((string)$res, true);
    // Sin rutas no es un error de red: es que no hay camino (isla, otro
    // continente). Se distingue con un array vacío para poder cachear el fallo.
    if (!is_array($j) || empty($j['routes'][0]['legs'])) return [];
    return $j['routes'][0]['legs'];
}

$guardar = $db->prepare(
    'INSERT INTO tramo_cache (hash, pts, ok, metros, segundos) VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE pts=VALUES(pts), ok=VALUES(ok), metros=VALUES(metros),
                             segundos=VALUES(segundos), creado=CURRENT_TIMESTAMP'
);

$llamadas = 0;
$fallo    = false;

// Se agrupan tramos CONSECUTIVOS DEL MISMO MODO en una sola petición, y
// dentro de cada grupo se corta a RUTA_MAX_PUNTOS compartiendo el punto
// frontera. Con el modo por defecto en todo el día esto es una única
// llamada; sólo cuando el usuario cambia un tramo a pie se parte en dos.
$grupos = [];
$ini = 0;
for ($i = 0; $i < count($modosSeg); $i++) {
    $fin = $i + 1 < count($modosSeg) && $modosSeg[$i + 1] === $modosSeg[$i];
    if (!$fin) { $grupos[] = ['ini' => $ini, 'fin' => $i + 1, 'modo' => $modosSeg[$i]]; $ini = $i + 1; }
}

foreach ($grupos as $g) {
  $puntosG = array_slice($pts, $g['ini'], $g['fin'] - $g['ini'] + 1);
  for ($off = 0; $off + 1 < count($puntosG); $off += RUTA_MAX_PUNTOS - 1) {
    $trozo = array_slice($puntosG, $off, RUTA_MAX_PUNTOS);
    if (count($trozo) < 2) break;

    $legs = rutaComputar($trozo, $g['modo']);
    $llamadas++;
    if ($legs === null) { $fallo = true; break 2; }   // error transitorio: no se cachea

    for ($i = 0; $i + 1 < count($trozo); $i++) {
        $k = rutaClave($trozo[$i], $trozo[$i + 1], $g['modo']);
        $h = md5($k);
        $leg = $legs[$i] ?? null;
        $linea = $leg['polyline']['geoJsonLinestring']['coordinates'] ?? null;

        if (!$leg || !is_array($linea) || count($linea) < 2) {
            // Sin ruta posible. Se cachea el fallo: si no, un tramo
            // imposible se volvería a pedir en cada carga, para siempre.
            $guardar->execute([$h, null, 0, null, null]);
            $cache[$h] = ['pts' => null, 'ok' => 0, 'metros' => null, 'segundos' => null];
            continue;
        }
        // GeoJSON viene [lng, lat]; el resto del proyecto usa {lat, lng}.
        $pl = [];
        foreach ($linea as $c) {
            if (isset($c[0], $c[1])) $pl[] = [round((float)$c[1], 6), round((float)$c[0], 6)];
        }
        $m = isset($leg['distanceMeters']) ? (int)$leg['distanceMeters'] : null;
        $s = isset($leg['duration']) ? (int)rtrim((string)$leg['duration'], 's') : null;

        $json = json_encode($pl);
        $guardar->execute([$h, $json, 1, $m, $s]);
        $cache[$h] = ['pts' => $json, 'ok' => 1, 'metros' => $m, 'segundos' => $s];
    }
  }
}

if ($llamadas > 0) {
    $db->prepare('UPDATE ruta_uso SET n = n + ? WHERE mes = ?')->execute([$llamadas, $mes]);
}

// Limpieza perezosa de lo caducado (1 de cada 20 peticiones, para no
// pagar el DELETE en todas).
if (random_int(1, 20) === 1) {
    $db->exec('DELETE FROM tramo_cache WHERE creado < NOW() - INTERVAL ' . RUTA_TTL_DIAS . ' DAY');
}

$salida($cache, $claves, $hashes, $fallo ? 'recta' : 'google');
