<?php
// ============================================================
//  includes/maps_lib.php — Clave de Google Maps | Ruta Nómada
//
//  Antes la clave estaba escrita a mano en plan.php, resultados.php
//  y crear_plan.php. Con el proyecto en un repo público eso significa
//  publicarla: los bots rastrean GitHub y una clave sin restringir se
//  factura a la cuenta de quien la creó. Ahora vive en
//  includes/maps_config.php, que está en .gitignore.
// ============================================================

if (!function_exists('mapsKey')) {
    /** Clave de Google Maps, o '' si no está configurada. Nunca lanza. */
    function mapsKey(): string
    {
        static $k;
        if ($k === null) {
            $c = @include __DIR__ . '/maps_config.php';
            $k = is_array($c) ? trim((string)($c['maps_key'] ?? '')) : '';
            if ($k === 'PON_AQUI_TU_KEY') $k = '';
        }
        return $k;
    }
}

if (!function_exists('mapsScriptUrl')) {
    /**
     * URL del script de Maps con los parámetros que usa el proyecto.
     * $callback: la función global que Google llama al terminar de cargar.
     * Devuelve '' si no hay clave, para poder avisar en vez de pedir a
     * Google una URL con "key=" vacío (que responde un error feo).
     */
    function mapsScriptUrl(string $callback): string
    {
        $k = mapsKey();
        if ($k === '') return '';
        return 'https://maps.googleapis.com/maps/api/js?' . http_build_query([
            'key'       => $k,
            'libraries' => 'places',
            // Sin esto Places responde en inglés aunque el usuario sea de México.
            'language'  => 'es',
            'region'    => 'MX',
            'loading'   => 'async',
            'callback'  => $callback,
        ], '', '&');
    }
}
