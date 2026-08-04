<?php
// ============================================================
//  api/lugar_wiki.php — Nivel 2 del "Acerca de" | Ruta Nómada
//  POST {nombre, lat, lng} → {ok:true, extracto, titulo, url}
//                          → {ok:true, extracto:null}  si no hay match fiable
//
//  Busca en Wikipedia en español un artículo que corresponda a ESTE lugar.
//  Vive en el servidor por dos razones:
//    1. Wikimedia exige un User-Agent descriptivo con forma de contacto,
//       y el navegador no puede fijar esa cabecera.
//    2. El texto de Wikipedia es CC BY-SA, así que SÍ se puede guardar en
//       caché indefinidamente — al revés que todo lo de Google Places.
//
//  Principio de diseño: MEJOR VACÍO QUE FALSO. Un artículo equivocado
//  ("Ensenada" el municipio en la ficha de un restaurante, o la cadena en
//  vez de la sucursal) es peor que no mostrar nada, porque el usuario no
//  tiene forma de saber que está mal. Por eso hay dos filtros duros:
//  distancia real y parecido del nombre.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

// ⚠ Cambia esto por tu correo antes de publicar el proyecto: la política de
//   Wikimedia pide poder contactar a quien hace las peticiones.
const WIKI_CONTACTO = 'cambia-esto@ejemplo.com';

const WIKI_RADIO_M   = 150;   // el artículo geoetiquetado debe estar a menos de esto
const WIKI_PARECIDO  = 78.0;  // % mínimo de parecido cuando hay prueba de distancia
// Sin coordenadas no hay prueba de distancia, así que el nombre tiene que
// parecerse mucho más antes de aceptar el artículo.
const WIKI_PARECIDO_BUSQUEDA = 88.0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
if (empty($_SESSION['user']))              apiFail('Debes iniciar sesión.', 401);

$in = apiBody();
csrfCheck($in);

$nombre = trim((string)($in['nombre'] ?? ''));
$ciudad = trim((string)($in['ciudad'] ?? ''));
$lat    = isset($in['lat']) ? (float)$in['lat'] : null;
$lng    = isset($in['lng']) ? (float)$in['lng'] : null;

if ($nombre === '' || $lat === null || $lng === null
    || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    apiFail('Faltan datos del lugar.');
}

// ── Normalización para comparar nombres ──────────────────────
/**
 * "Museo de Historia de Ensenada" y "MUSEO DE HISTORIA, Ensenada" tienen que
 * parecerse. Se quitan acentos, artículos y los sustantivos genéricos que
 * Google mete en el nombre comercial pero Wikipedia no usa en el título.
 */
function wikiNormalizar(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c',
    ]);
    $s = preg_replace('/\([^)]*\)/u', ' ', $s);          // "(sucursal centro)"
    $s = preg_replace('/[^a-z0-9\s]/u', ' ', $s);
    $vacias = ['el','la','los','las','de','del','y','en','a','museo','parque','iglesia',
               'catedral','templo','playa','restaurante','hotel','cafe','bar','centro',
               'plaza','mercado','galeria','teatro','biblioteca','estadio','zoologico'];
    $palabras = array_values(array_filter(preg_split('/\s+/u', $s), function ($p) use ($vacias) {
        return $p !== '' && !in_array($p, $vacias, true);
    }));
    return implode(' ', $palabras);
}

// ── Petición a la API de Wikimedia ───────────────────────────
function wikiGet(array $params): ?array
{
    $url = 'https://es.wikipedia.org/w/api.php?' . http_build_query($params + ['format' => 'json', 'formatversion' => 2]);
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            // Política de Wikimedia: identificarse y dejar un contacto.
            'User-Agent: RutaNomada/1.0 (proyecto escolar; ' . WIKI_CONTACTO . ')',
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $res  = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $code !== 200) return null;
    $j = json_decode($res, true);
    return is_array($j) ? $j : null;
}

// ── Caché en disco (Wikipedia es CC BY-SA: se puede guardar) ──
// La clave NO incluye coordenadas exactas a propósito: se redondean a ~11 m
// para que el archivo se reutilice, y así tampoco se conserva la posición
// precisa que devolvió Google.
$clave   = substr(sha1(wikiNormalizar($nombre) . '|' . wikiNormalizar($ciudad) . '|' . round($lat, 4) . '|' . round($lng, 4)), 0, 24);
$dirCache = dirname(__DIR__) . '/cache/wiki';
$archivo  = $dirCache . '/' . $clave . '.json';

if (is_file($archivo)) {
    $c = json_decode((string)@file_get_contents($archivo), true);
    if (is_array($c)) apiJson($c + ['ok' => true, 'cache' => true]);
}

$respuesta = ['ok' => true, 'extracto' => null];

$objetivo = wikiNormalizar($nombre);
if ($objetivo === '') apiJson($respuesta);

/** Parecido entre el nombre del lugar y el título del artículo. */
function wikiParecido(string $objetivo, string $titulo): float
{
    $t = wikiNormalizar($titulo);
    if ($t === '') return 0.0;
    $pct = 0.0;
    similar_text($objetivo, $t, $pct);
    // La contención cubre "Bufadora" vs "La Bufadora"; similar_text cubre
    // erratas y orden distinto de las palabras.
    if (strpos($t, $objetivo) !== false || strpos($objetivo, $t) !== false) {
        $pct = max($pct, 92.0);
    }
    return $pct;
}

// ── 1. ¿Qué artículos hay geoetiquetados cerca? ──────────────
// Es la vía más segura (la distancia real es una prueba dura), pero cubre
// poco: medido sobre lugares de Baja California y Sonora, sólo ~3 de cada
// 10 artículos que existen traen coordenadas. Por eso hay un plan B.
$geo = wikiGet([
    'action'   => 'query',
    'list'     => 'geosearch',
    'gscoord'  => $lat . '|' . $lng,
    'gsradius' => WIKI_RADIO_M,
    'gslimit'  => 10,
]);

$mejor    = null;
$mejorPct = 0.0;
$via      = '';

// El artículo de la CIUDAD nunca describe un lugar dentro de ella. Sin este
// filtro, un "Restaurante Ensenada" en el centro normaliza a "ensenada",
// empata al 100% con el artículo del municipio y la ficha acabaría contando
// la historia de la ciudad como si fuera la del restaurante.
$ciudadNorm = wikiNormalizar($ciudad);
$esArticuloDeLaCiudad = static function (string $titulo) use ($ciudadNorm): bool {
    if ($ciudadNorm === '') return false;
    return wikiNormalizar($titulo) === $ciudadNorm;
};

foreach (($geo['query']['geosearch'] ?? []) as $c) {
    $titulo = (string)($c['title'] ?? '');
    if ($esArticuloDeLaCiudad($titulo)) continue;
    $pct = wikiParecido($objetivo, $titulo);
    if ($pct > $mejorPct) { $mejorPct = $pct; $mejor = $c; $via = 'geo'; }
}
if ($mejor && $mejorPct < WIKI_PARECIDO) { $mejor = null; $mejorPct = 0.0; }

// ── 2. Plan B: buscar por título ─────────────────────────────
// Sin coordenadas se pierde la prueba de distancia, así que la sustituyen
// DOS pruebas independientes: el nombre tiene que parecerse mucho más, y
// el propio artículo tiene que mencionar la ciudad. Un restaurante de
// barrio no supera ninguna de las dos; La Bufadora supera las dos.
if (!$mejor && $ciudad !== '') {
    $bus = wikiGet([
        'action'   => 'query',
        'list'     => 'search',
        'srsearch' => '"' . $nombre . '" ' . $ciudad,
        'srlimit'  => 5,
    ]);
    foreach (($bus['query']['search'] ?? []) as $c) {
        $titulo = (string)($c['title'] ?? '');
        if ($esArticuloDeLaCiudad($titulo)) continue;
        $pct = wikiParecido($objetivo, $titulo);
        if ($pct > $mejorPct) { $mejorPct = $pct; $mejor = $c; $via = 'busqueda'; }
    }
    if ($mejor && $mejorPct < WIKI_PARECIDO_BUSQUEDA) { $mejor = null; $mejorPct = 0.0; }
}

// ── 3. Sólo si supera el umbral, se pide el resumen ──────────
if ($mejor) {
    $ext = wikiGet([
        'action'      => 'query',
        'prop'        => 'extracts',
        'exintro'     => 1,
        'explaintext' => 1,
        'exsentences' => 3,
        'pageids'     => (int)$mejor['pageid'],
    ]);
    $pag = $ext['query']['pages'][0] ?? null;
    $txt = trim((string)($pag['extract'] ?? ''));

    // Las páginas de desambiguación empiezan con "puede referirse a" y no
    // describen nada; ya nos pasó con "Ensenada" en destino.php.
    $esDesambiguacion = $txt !== '' && preg_match('/puede (referirse|hacer referencia)/iu', $txt);

    // Segunda prueba del plan B: si llegamos por búsqueda de título, el
    // artículo tiene que hablar de la ciudad. Es lo que impide traer el
    // artículo de una cadena nacional o de un homónimo de otro estado.
    $mencionaCiudad = true;
    if ($via === 'busqueda') {
        $cn = wikiNormalizar($ciudad);
        $mencionaCiudad = $cn !== '' && strpos(wikiNormalizar($txt), $cn) !== false;
    }

    if ($txt !== '' && !$esDesambiguacion && $mencionaCiudad && mb_strlen($txt) > 40) {
        $respuesta = [
            'ok'       => true,
            'extracto' => $txt,
            'titulo'   => (string)$mejor['title'],
            // CC BY-SA obliga a enlazar al artículo original.
            'url'      => 'https://es.wikipedia.org/?curid=' . (int)$mejor['pageid'],
            'via'      => $via,
            'metros'   => isset($mejor['dist']) ? (int)round((float)$mejor['dist']) : null,
            'parecido' => round($mejorPct, 1),
        ];
    }
}

if (!is_dir($dirCache)) @mkdir($dirCache, 0755, true);
@file_put_contents($archivo, json_encode($respuesta, JSON_UNESCAPED_UNICODE));

apiJson($respuesta);
