<?php
// ============================================================
//   includes/currency.php — Divisa global | Ruta Nómada
//   Convierte precios (base MXN) a la divisa del usuario.
//   Tasas desde open.er-api.com (gratis, sin key, cubre COP/ARS),
//   cacheadas una vez al día en /cache/rates.json.
// ============================================================

if (!function_exists('rnCurrencies')) {
    /** Divisas soportadas: código => [símbolo, nombre, iso2 país (flagcdn), sin decimales?] */
    function rnCurrencies(): array
    {
        return [
            'MXN' => ['sym' => '$',  'name' => 'Peso mexicano',        'cc' => 'mx', 'nodec' => false],
            'USD' => ['sym' => '$',  'name' => 'Dólar estadounidense', 'cc' => 'us', 'nodec' => false],
            'EUR' => ['sym' => '€',  'name' => 'Euro',                 'cc' => 'eu', 'nodec' => false],
            'GBP' => ['sym' => '£',  'name' => 'Libra esterlina',      'cc' => 'gb', 'nodec' => false],
            'CAD' => ['sym' => 'C$', 'name' => 'Dólar canadiense',     'cc' => 'ca', 'nodec' => false],
            'JPY' => ['sym' => '¥',  'name' => 'Yen japonés',          'cc' => 'jp', 'nodec' => true],
            'BRL' => ['sym' => 'R$', 'name' => 'Real brasileño',       'cc' => 'br', 'nodec' => false],
            'COP' => ['sym' => '$',  'name' => 'Peso colombiano',      'cc' => 'co', 'nodec' => true],
            'ARS' => ['sym' => '$',  'name' => 'Peso argentino',       'cc' => 'ar', 'nodec' => true],
        ];
    }
}

if (!function_exists('isValidCurrency')) {
    function isValidCurrency(?string $code): bool
    {
        return $code !== null && array_key_exists($code, rnCurrencies());
    }
}

if (!function_exists('userCurrency')) {
    /** Divisa del usuario logueado (por defecto MXN). */
    function userCurrency(): string
    {
        $c = $_SESSION['user']['divisa'] ?? 'MXN';
        return isValidCurrency($c) ? $c : 'MXN';
    }
}

if (!function_exists('mxnRates')) {
    /** Tasas MXN → * (incluye 'MXN' => 1). Cacheadas por día. [] si falla. */
    function mxnRates(): array
    {
        static $rates = null;
        if ($rates !== null) {
            return $rates;
        }

        $cacheDir  = dirname(__DIR__) . '/cache';
        $cacheFile = $cacheDir . '/rates.json';
        $today     = date('Y-m-d');

        // 1) Caché del día
        if (is_file($cacheFile)) {
            $cached = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($cached) && ($cached['date'] ?? '') === $today && !empty($cached['rates'])) {
                return $rates = $cached['rates'];
            }
        }

        // 2) Descargar tasas (base MXN)
        $fetched = [];
        $ctx = stream_context_create(['http' => ['timeout' => 4]]);
        $raw = @file_get_contents('https://open.er-api.com/v6/latest/MXN', false, $ctx);
        if ($raw !== false) {
            $data = json_decode($raw, true);
            if (($data['result'] ?? '') === 'success' && !empty($data['rates'])) {
                foreach (rnCurrencies() as $code => $_) {
                    if (isset($data['rates'][$code])) {
                        $fetched[$code] = (float) $data['rates'][$code];
                    }
                }
            }
        }
        $fetched['MXN'] = 1.0;

        // 3) Guardar caché (si obtuvimos algo más que MXN)
        if (count($fetched) > 1) {
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            @file_put_contents($cacheFile, json_encode(['date' => $today, 'rates' => $fetched]));
        }

        return $rates = $fetched;
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney(float $amount, string $code): string
    {
        $cur = rnCurrencies()[$code] ?? rnCurrencies()['MXN'];
        $dec = $cur['nodec'] ? 0 : 2;
        return $cur['sym'] . number_format($amount, $dec, '.', ',') . ' ' . $code;
    }
}

if (!function_exists('priceInUserCurrency')) {
    /**
     * Convierte un precio en MXN a la divisa del usuario y lo formatea.
     * Si la divisa es MXN o no hay tasa disponible, muestra MXN.
     */
    function priceInUserCurrency($mxn): string
    {
        $mxn   = (float) $mxn;
        $code  = userCurrency();
        if ($code === 'MXN') {
            return formatMoney($mxn, 'MXN');
        }
        $rates = mxnRates();
        if (empty($rates[$code])) {
            // Sin tasa (p. ej. API caída) → mostrar MXN
            return formatMoney($mxn, 'MXN');
        }
        return formatMoney($mxn * $rates[$code], $code);
    }
}
