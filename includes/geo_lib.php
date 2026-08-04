<?php
// ============================================================
//  includes/geo_lib.php — País/Estado/Ciudad (CountryStateCity) | Ruta Nómada
//  Funciones con caché en disco, reutilizadas por geo.php (dropdowns)
//  y por profile.php (validación en el servidor).
// ============================================================

if (!function_exists('cscKey')) {
    function cscKey(): string
    {
        static $k;
        if ($k === null) {
            $cfg = @include __DIR__ . '/geo_config.php';
            $k = is_array($cfg) ? (string) ($cfg['csc_key'] ?? '') : '';
        }
        return $k;
    }
}

if (!function_exists('cscGet')) {
    /**
     * GET a la CSC API con caché en disco (los datos geográficos casi no cambian).
     * $cacheName: nombre de archivo de caché. Devuelve array o null si falla.
     */
    function cscGet(string $path, string $cacheName): ?array
    {
        $cacheDir = dirname(__DIR__) . '/cache/geo';
        $cacheF   = $cacheDir . '/' . $cacheName . '.json';

        if (is_file($cacheF)) {
            $c = json_decode((string) @file_get_contents($cacheF), true);
            if (is_array($c)) {
                return $c;
            }
        }

        $key = cscKey();
        if ($key === '' || $key === 'PON_AQUI_TU_KEY_CSC') {
            return null; // sin key configurada
        }

        $ch = curl_init('https://api.countrystatecity.in/v1' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['X-CSCAPI-KEY: ' . $key],
            CURLOPT_TIMEOUT        => 8,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || $code !== 200) {
            return null;
        }
        $data = json_decode($res, true);
        if (!is_array($data)) {
            return null;
        }

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheF, json_encode($data));
        return $data;
    }
}

if (!function_exists('cscCountries')) {
    /** [ ['name'=>..., 'iso2'=>...], ... ] ordenado por nombre. */
    function cscCountries(): array
    {
        $raw = cscGet('/countries', 'countries');
        if (!$raw) {
            return [];
        }
        $out = [];
        foreach ($raw as $c) {
            if (!empty($c['name']) && !empty($c['iso2'])) {
                $out[] = ['name' => $c['name'], 'iso2' => strtoupper($c['iso2'])];
            }
        }
        usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }
}

if (!function_exists('cscStates')) {
    function cscStates(string $ciso): array
    {
        $ciso = strtoupper(preg_replace('/[^A-Za-z]/', '', $ciso));
        if ($ciso === '') {
            return [];
        }
        $raw = cscGet("/countries/{$ciso}/states", 'states_' . $ciso);
        if (!$raw) {
            return [];
        }
        $out = [];
        foreach ($raw as $s) {
            if (!empty($s['name']) && !empty($s['iso2'])) {
                $out[] = ['name' => $s['name'], 'iso2' => $s['iso2']];
            }
        }
        usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }
}

if (!function_exists('cscCities')) {
    function cscCities(string $ciso, string $siso): array
    {
        $ciso = strtoupper(preg_replace('/[^A-Za-z]/', '', $ciso));
        $siso = preg_replace('/[^A-Za-z0-9]/', '', $siso);
        if ($ciso === '' || $siso === '') {
            return [];
        }
        $raw = cscGet("/countries/{$ciso}/states/{$siso}/cities", 'cities_' . $ciso . '_' . $siso);
        if (!$raw) {
            return [];
        }
        $out = [];
        foreach ($raw as $c) {
            if (!empty($c['name'])) {
                $out[] = ['name' => $c['name']];
            }
        }
        usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }
}

if (!function_exists('geoValidate')) {
    /**
     * Valida que (país, estado, ciudad) sean reales y consistentes.
     * Recibe los ISO (para buscar) y los nombres (para confirmar coincidencia).
     * Devuelve ['ok'=>bool, 'field'=>?, 'error'=>?].
     */
    function geoValidate(string $paisIso, string $paisNombre, string $estadoIso, string $estadoNombre, string $ciudadNombre): array
    {
        $countries = cscCountries();
        if (!$countries) {
            // Sin datos (key no configurada / API caída): no bloquear el guardado por esto.
            return ['ok' => true, 'degraded' => true];
        }
        $paisIso = strtoupper($paisIso);
        $country = null;
        foreach ($countries as $c) {
            if ($c['iso2'] === $paisIso && $c['name'] === $paisNombre) { $country = $c; break; }
        }
        if (!$country) {
            return ['ok' => false, 'field' => 'nacionalidad', 'error' => 'Selecciona una nacionalidad válida de la lista.'];
        }

        $states = cscStates($paisIso);
        $state = null;
        foreach ($states as $s) {
            if ($s['iso2'] === $estadoIso && $s['name'] === $estadoNombre) { $state = $s; break; }
        }
        if (!$state) {
            return ['ok' => false, 'field' => 'estado', 'error' => 'Selecciona un estado válido del país elegido.'];
        }

        $cities = cscCities($paisIso, $estadoIso);
        foreach ($cities as $ct) {
            if ($ct['name'] === $ciudadNombre) {
                return ['ok' => true];
            }
        }
        return ['ok' => false, 'field' => 'ciudad', 'error' => 'Selecciona una ciudad válida del estado elegido.'];
    }
}
