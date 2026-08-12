<?php
// ============================================================
//  api/imagenes.php — Buscar fotos en la web | Ruta Nómada
//  POST {plan_id, q}  →  {ok, fotos: [...]}
//
//  QUÉ HACE
//  Pregunta a Pexels por fotos que encajen con lo que se escribió en
//  la ventana «Cambiar foto» y devuelve sólo lo que la rejilla
//  necesita para pintarse.
//
//  POR QUÉ ES UN INTERMEDIARIO Y NO UNA LLAMADA DESDE EL NAVEGADOR
//  Porque la clave de Pexels no puede salir al navegador: quien abra
//  el inspector la vería y podría gastarse la cuota de la cuenta. El
//  mismo criterio que se aplicó con Gemini en api/plan_ai.php.
//
//  POR QUÉ PEXELS Y NO LAS FOTOS DE GOOGLE PLACES
//  Porque sus URLs no caducan, y esta URL se guarda en
//  planes.portada_url para pintarla meses después. El razonamiento
//  completo está en Reportes_md/PLAN_cambiar_foto.md
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);

// Cambiar la portada es editar el plan, así que se exige lo mismo que
// para guardarla. Además evita que una cuenta cualquiera use nuestra
// cuota de Pexels como buscador de imágenes gratis.
planAccess((int)($in['plan_id'] ?? 0), 'editor');

$q = trim((string)($in['q'] ?? ''));
if ($q === '')            apiFail('Escribe qué foto buscas.');
if (mb_strlen($q) > 80)   apiFail('La búsqueda es demasiado larga.');

// ── Configuración ────────────────────────────────────────────
$ruta = __DIR__ . '/../includes/pexels_config.php';
if (!is_file($ruta)) {
    // Le pasará a quien clone el repositorio y no haya creado su
    // archivo de configuración. Se le dice qué hacer, no sólo que falla.
    apiFail('Falta includes/pexels_config.php. Copia pexels_config.sample.php y pon tu clave de Pexels.', 500);
}
$cfg = include $ruta;
$clave = (string)($cfg['api_key'] ?? '');
if ($clave === '' || strpos($clave, 'PON-AQUI') === 0) {
    apiFail('La clave de Pexels no está configurada en includes/pexels_config.php.', 500);
}

$porPagina = max(3, min(30, (int)($cfg['por_pagina'] ?? 12)));
$idioma    = (string)($cfg['idioma'] ?? 'es-ES');

// ── Llamada a Pexels ─────────────────────────────────────────
$url = 'https://api.pexels.com/v1/search?' . http_build_query([
    'query'    => $q,
    'per_page' => $porPagina,
    'locale'   => $idioma,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: ' . $clave],
    // Cortos a propósito: esto ocurre con la ventana abierta y alguien
    // esperando. Más vale decir que no se pudo que dejar el modal
    // congelado medio minuto.
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 12,
]);
$respuesta = curl_exec($ch);
$http      = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errCurl   = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    error_log('imagenes.php cURL: ' . $errCurl);
    apiFail('No se pudo conectar con el buscador de fotos. Revisa tu conexión.', 502);
}
if ($http === 401 || $http === 403) {
    error_log('imagenes.php: Pexels rechazó la clave (HTTP ' . $http . ')');
    apiFail('La clave de Pexels no es válida.', 502);
}
if ($http === 429) {
    apiFail('Se agotaron las búsquedas de fotos por ahora. Inténtalo más tarde.', 429);
}
if ($http !== 200) {
    error_log('imagenes.php: Pexels devolvió HTTP ' . $http);
    apiFail('El buscador de fotos no respondió bien. Inténtalo de nuevo.', 502);
}

$j = json_decode($respuesta, true);
if (!is_array($j) || !isset($j['photos'])) {
    apiFail('Respuesta inesperada del buscador de fotos.', 502);
}

// ── Sólo lo que la rejilla necesita ──────────────────────────
// No se reenvía el JSON de Pexels tal cual: trae dos docenas de campos
// que no se usan y que sólo servirían para engordar la respuesta.
$fotos = [];
foreach ($j['photos'] as $p) {
    if (empty($p['src']['large']) || empty($p['src']['medium'])) continue;
    $fotos[] = [
        'id'     => (int)($p['id'] ?? 0),
        // La miniatura de la rejilla y la que se guardará como portada.
        'mini'   => (string)$p['src']['medium'],
        'grande' => (string)$p['src']['large'],
        // Atribución: Pexels pide nombrar a quien hizo la foto y poder
        // llegar al original. Es la condición de uso de su API gratuita.
        'autor'  => (string)($p['photographer'] ?? ''),
        'pagina' => (string)($p['url'] ?? ''),
        'alt'    => (string)($p['alt'] ?? ''),
    ];
}

apiJson(['ok' => true, 'fotos' => $fotos, 'total' => (int)($j['total_results'] ?? count($fotos))]);
