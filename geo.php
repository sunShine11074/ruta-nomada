<?php
// ============================================================
//  geo.php — Proxy País/Estado/Ciudad (CountryStateCity) | Ruta Nómada
//  Devuelve JSON para los dropdowns del perfil. La API key vive
//  solo en el servidor (includes/geo_config.php).
//  Uso:
//    geo.php?type=countries
//    geo.php?type=states&country=MX
//    geo.php?type=cities&country=MX&state=SLP
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/includes/geo_lib.php';

$type = $_GET['type'] ?? '';

if (cscKey() === '' || cscKey() === 'PON_AQUI_TU_KEY_CSC') {
    echo json_encode(['ok' => false, 'error' => 'Falta configurar la API key de CountryStateCity en includes/geo_config.php', 'data' => []]);
    exit;
}

switch ($type) {
    case 'countries':
        echo json_encode(['ok' => true, 'data' => cscCountries()]);
        break;

    case 'states':
        $country = $_GET['country'] ?? '';
        echo json_encode(['ok' => true, 'data' => cscStates($country)]);
        break;

    case 'cities':
        $country = $_GET['country'] ?? '';
        $state   = $_GET['state'] ?? '';
        echo json_encode(['ok' => true, 'data' => cscCities($country, $state)]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Tipo no reconocido', 'data' => []]);
}
