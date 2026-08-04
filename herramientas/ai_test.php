<?php
// ============================================================
//  herramientas/ai_test.php — Diagnóstico del asistente | Ruta Nómada
//
//  Ejecutar desde la terminal, NO desde el navegador:
//      php herramientas/ai_test.php
//
//  Sirve para tres cosas, en este orden:
//    1. Confirmar que la clave de includes/ai_config.php funciona.
//    2. LISTAR LOS MODELOS QUE TU CLAVE ACEPTA DE VERDAD. Los IDs de
//       Gemini se apagan cada pocos meses y la documentación de Google
//       todavía muestra ejemplos con modelos ya retirados, así que
//       copiar un ID de un tutorial es la forma más común de acabar
//       con un 404. Aquí sale la lista real.
//    3. Hacer una llamada de verdad y enseñar el usageMetadata, que
//       dice cuántos tokens gastó pensando (thoughtsTokenCount) frente
//       a cuántos gastó escribiendo. Si el primero se come el
//       presupuesto, hay que subir maxOutputTokens o usar un modelo
//       que no razone.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta herramienta sólo se ejecuta desde la terminal.\n");
}

require_once __DIR__ . '/../includes/ai_lib.php';

function linea(string $t = ''): void { fwrite(STDOUT, $t . PHP_EOL); }
function titulo(string $t): void { linea(); linea('── ' . $t . ' ' . str_repeat('─', max(0, 58 - mb_strlen($t)))); }

linea('Diagnóstico del asistente de IA — Ruta Nómada');

// ── 1. Configuración ─────────────────────────────────────────
titulo('1. Configuración');

if (!is_file(__DIR__ . '/../includes/ai_config.php')) {
    linea('  ✗ No existe includes/ai_config.php');
    linea('    Copia includes/ai_config.sample.php como includes/ai_config.php');
    linea('    y pega tu clave de https://aistudio.google.com/apikey');
    exit(1);
}
$clave = aiKey();
if ($clave === '') {
    linea('  ✗ La clave sigue con el valor de ejemplo (PON_AQUI_TU_KEY_GEMINI).');
    exit(1);
}
linea('  ✓ Clave cargada: ' . mb_substr($clave, 0, 6) . str_repeat('·', 12) . mb_substr($clave, -4));
linea('  · Modelo configurado : ' . aiModelo());
linea('  · Versión de la API  : ' . aiApiVersion());

// ── 2. Modelos que acepta esta clave ─────────────────────────
titulo('2. Modelos disponibles para tu clave');

$ch = curl_init('https://generativelanguage.googleapis.com/' . aiApiVersion() . '/models?pageSize=200');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['x-goog-api-key: ' . $clave],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 20,
]);
$res  = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($res === false) {
    linea('  ✗ Falló la conexión: ' . $cerr);
    exit(1);
}
if ($http !== 200) {
    linea("  ✗ HTTP {$http}");
    linea('  ' . mb_substr((string)$res, 0, 600));
    linea();
    linea('  401/403 → la clave es inválida o está restringida a otra API.');
    linea('            Revisa en Cloud Console que permita "Generative Language API".');
    exit(1);
}

$lista = json_decode((string)$res, true)['models'] ?? [];
$gen   = [];
foreach ($lista as $m) {
    // Sólo los que sirven para chatear.
    if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true)) {
        $gen[] = str_replace('models/', '', (string)($m['name'] ?? ''));
    }
}
sort($gen);
if (!$gen) {
    linea('  ⚠ La API respondió pero no listó modelos con generateContent.');
} else {
    linea('  ' . count($gen) . ' modelos aceptan generateContent:');
    foreach ($gen as $g) {
        linea('    ' . ($g === aiModelo() ? '→ ' : '  ') . $g);
    }
    if (!in_array(aiModelo(), $gen, true)) {
        linea();
        linea('  ⚠ OJO: "' . aiModelo() . '" NO está en la lista.');
        linea('    Cambia la clave "modelo" de includes/ai_config.php por uno de arriba,');
        linea('    o la llamada real fallará con 404.');
    }
}

// ── 3. Llamada real ──────────────────────────────────────────
titulo('3. Llamada real al modelo configurado');

$payload = [
    'systemInstruction' => ['parts' => [['text' => aiSystemPrompt(
        "VIAJE: prueba de diagnóstico\nDESTINO: Ensenada, Baja California\nFECHAS: sin definir\nITINERARIO: todavía vacío, el usuario no ha añadido ningún lugar."
    )]]],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => 'Dame 3 ideas para un día en Ensenada.']]],
    ],
    'generationConfig' => [
        'temperature' => 0.7, 'topP' => 0.95,
        'maxOutputTokens' => 800, 'candidateCount' => 1,
        'responseMimeType' => 'text/plain',
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
    ],
];

$url = 'https://generativelanguage.googleapis.com/' . aiApiVersion()
     . '/models/' . rawurlencode(aiModelo()) . ':generateContent';
$t0  = microtime(true);
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $clave],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 30,
]);
$res  = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);
$ms = (int)round((microtime(true) - $t0) * 1000);

linea("  HTTP {$http} en {$ms} ms");
if ($res === false) { linea('  ✗ ' . $cerr); exit(1); }
if ($http !== 200)  { linea('  ✗ ' . mb_substr((string)$res, 0, 800)); exit(1); }

$data  = json_decode((string)$res, true);
$texto = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
$fin   = (string)($data['candidates'][0]['finishReason'] ?? '?');
linea('  finishReason: ' . $fin);

if ($texto === null) {
    linea('  ✗ Vino sin texto. blockReason: ' . ($data['promptFeedback']['blockReason'] ?? '—'));
    exit(1);
}

linea();
linea('  ── Respuesta cruda ───────────────────────────');
foreach (explode("\n", $texto) as $l) linea('  │ ' . $l);
linea();
linea('  ── Después de aiSanitizar() ──────────────────');
$limpio = aiSanitizar($texto);
foreach (explode("\n", $limpio) as $l) linea('  │ ' . $l);

// ── 4. Formato y costo ───────────────────────────────────────
titulo('4. ¿Respeta el mini-formato que sabe pintar richBody()?');

$vinetasMal = preg_match_all('/^\s*[-*]\s+/mu', $texto);
$titulosMd  = preg_match_all('/^\s{0,3}#{1,6}\s/mu', $texto);
$enlacesMd  = preg_match_all('/\[[^\]]+\]\([^)]*\)/u', $texto);
$corchetes  = preg_match_all('/\[([^\]]+)\]/u', $texto, $mm);
$negritas   = substr_count($texto, '**') / 2;

linea('  Lugares entre corchetes : ' . $corchetes . ($corchetes ? '  → ' . implode(', ', array_slice($mm[1], 0, 6)) : ''));
linea('  Negritas **...**        : ' . (int)$negritas);
linea('  Viñetas con • correctas : ' . preg_match_all('/^• /mu', $texto));
linea('  Viñetas con - o * (mal) : ' . $vinetasMal . ($vinetasMal ? '   ← aiSanitizar() las convierte' : ''));
linea('  Títulos con # (mal)     : ' . $titulosMd . ($titulosMd ? '   ← aiSanitizar() los convierte' : ''));
linea('  Enlaces [txt](url) (mal): ' . $enlacesMd . ($enlacesMd ? '   ← aiSanitizar() los recorta' : ''));

$u = $data['usageMetadata'] ?? [];
titulo('5. Costo de esta llamada');
$in  = (int)($u['promptTokenCount'] ?? 0);
$out = (int)($u['candidatesTokenCount'] ?? 0);
$th  = (int)($u['thoughtsTokenCount'] ?? 0);
linea('  Tokens de entrada  : ' . $in);
linea('  Tokens de salida   : ' . $out);
linea('  Tokens de "pensar" : ' . $th . ($th > 0 ? '   ← se facturan como salida' : '   (este modelo no razona)'));
linea('  Total              : ' . (int)($u['totalTokenCount'] ?? 0));
if ($fin === 'MAX_TOKENS') {
    linea();
    linea('  ⚠ Se cortó por MAX_TOKENS. Sube maxOutputTokens en api/plan_ai.php');
    linea('    o usa un modelo que no gaste tokens razonando.');
}

linea();
linea('Listo. Si los 5 pasos salieron bien, el asistente está conectado.');
