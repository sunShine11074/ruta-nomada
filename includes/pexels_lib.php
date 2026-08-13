<?php
// ============================================================
//  includes/pexels_lib.php — Buscar fotos en Pexels | Ruta Nómada
//
//  La usan dos sitios:
//    · api/imagenes.php    el buscador de la ventana «Cambiar foto»
//    · api/plan_create.php la portada automática del viaje nuevo
//
//  Está aparte para que la llamada viva en un solo lugar, igual que
//  includes/ai_lib.php hace con Gemini.
// ============================================================

// Configuración, o null si quien clonó el repositorio no la creó.
function pexelsConfig(): ?array
{
    $ruta = __DIR__ . '/pexels_config.php';
    if (!is_file($ruta)) return null;
    $cfg = @include $ruta;
    if (!is_array($cfg)) return null;
    $clave = (string)($cfg['api_key'] ?? '');
    if ($clave === '' || strpos($clave, 'PON-AQUI') === 0) return null;
    return $cfg;
}

// Devuelve un array de fotos normalizadas, o null si algo falló.
// $segundos: tope de espera. Corto a propósito en los dos llamadores;
// nadie debería quedarse mirando una ventana congelada.
function pexelsBuscar(string $q, int $cuantas = 12, int $segundos = 12): ?array
{
    $cfg = pexelsConfig();
    if ($cfg === null || trim($q) === '') return null;

    $url = 'https://api.pexels.com/v1/search?' . http_build_query([
        'query'    => $q,
        'per_page' => max(1, min(80, $cuantas)),
        'locale'   => (string)($cfg['idioma'] ?? 'es-ES'),
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $cfg['api_key']],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => $segundos,
    ]);
    $cuerpo = curl_exec($ch);
    $http   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false) { error_log('pexelsBuscar cURL: ' . $err); return null; }
    if ($http !== 200)     { error_log('pexelsBuscar HTTP ' . $http); return null; }

    $j = json_decode($cuerpo, true);
    if (!is_array($j) || !isset($j['photos'])) return null;

    $fotos = [];
    foreach ($j['photos'] as $p) {
        if (empty($p['src']['large']) || empty($p['src']['medium'])) continue;
        $fotos[] = [
            'id'     => (int)($p['id'] ?? 0),
            'mini'   => (string)$p['src']['medium'],
            'grande' => (string)$p['src']['large'],
            'autor'  => (string)($p['photographer'] ?? ''),
            'pagina' => (string)($p['url'] ?? ''),
            'alt'    => (string)($p['alt'] ?? ''),
        ];
    }
    return $fotos;
}

// ── La portada automática de un viaje nuevo ──────────────────
//
// Buscar el destino tal cual NO funciona, y se comprobó destino a
// destino el 12/08/2026:
//
//   · «Guanajuato, Gto.» devolvía la catedral de LEÓN. El sufijo del
//     estado desvía la búsqueda hacia otra ciudad.
//   · «La Paz» a secas devuelve gente haciendo el signo de la paz:
//     Pexels busca por palabras, no sabe que es una ciudad.
//
// Se corrige con dos reglas: quitar lo que va después de la primera
// coma, y añadir «ciudad». Probado con La Paz, Monterrey, Ensenada,
// Madrid y Guanajuato: los cinco devuelven una foto de la ciudad.
function pexelsConsultaDestino(string $destino): string
{
    $base = trim(explode(',', $destino)[0]);
    if ($base === '') $base = trim($destino);
    return $base . ' ciudad';
}

// La URL de portada para un destino, o null si no se pudo.
// Espera poco: esto ocurre mientras alguien aguarda a que se cree su
// viaje, y quedarse sin foto es mucho mejor que quedarse sin viaje.
function pexelsPortadaDeDestino(string $destino): ?string
{
    $fotos = pexelsBuscar(pexelsConsultaDestino($destino), 1, 6);
    return $fotos ? $fotos[0]['grande'] : null;
}
