<?php
// ============================================================
//  api/plan_update.php — Campos sueltos del plan | Ruta Nómada
//  POST {plan_id, campo: valor, ...}
//  Campos permitidos: nombre, fecha_inicio, fecha_fin, privacidad,
//  presupuesto, portada_url, dia_subtitulos (array)
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];

$sets = [];
$vals = [];
$reDate = '/^\d{4}-\d{2}-\d{2}$/';

if (array_key_exists('nombre', $in)) {
    $n = mb_substr(trim((string)$in['nombre']), 0, 200);
    if ($n === '') apiFail('El nombre no puede quedar vacío.');
    $sets[] = 'nombre = ?';
    $vals[] = $n;
}
foreach (['fecha_inicio', 'fecha_fin'] as $f) {
    if (array_key_exists($f, $in)) {
        $v = $in[$f];
        if ($v !== null && $v !== '') {
            $v = validDate($v);
            if ($v === null) apiFail('Fecha inválida.');
        } else {
            $v = null;
        }
        $sets[] = "$f = ?";
        $vals[] = $v;
    }
}
if (array_key_exists('privacidad', $in)) {
    if (!in_array($in['privacidad'], ['solo', 'amigos', 'publico'], true)) apiFail('Privacidad inválida.');
    $sets[] = 'privacidad = ?';
    $vals[] = $in['privacidad'];
}
if (array_key_exists('presupuesto', $in)) {
    $p = $in['presupuesto'];
    if ($p !== null && $p !== '' && (!is_numeric($p) || $p < 0 || $p > 99999999)) apiFail('Presupuesto inválido.');
    $sets[] = 'presupuesto = ?';
    $vals[] = ($p === '' || $p === null) ? null : (float)$p;
}
foreach (['lat' => 90, 'lng' => 180] as $f => $max) {
    if (array_key_exists($f, $in)) {
        $v = $in[$f];
        if ($v !== null && $v !== '' && (!is_numeric($v) || abs($v) > $max)) apiFail('Coordenada inválida.');
        $sets[] = "$f = ?";
        $vals[] = ($v === '' || $v === null) ? null : (float)$v;
    }
}
if (array_key_exists('portada_url', $in)) {
    $sets[] = 'portada_url = ?';
    $vals[] = mb_substr((string)$in['portada_url'], 0, 500) ?: null;
}
if (array_key_exists('dia_subtitulos', $in)) {
    $arr = is_array($in['dia_subtitulos']) ? array_map(fn($s) => mb_substr((string)$s, 0, 200), $in['dia_subtitulos']) : [];
    $sets[] = 'dia_subtitulos = ?';
    $vals[] = json_encode(array_slice($arr, 0, 60), JSON_UNESCAPED_UNICODE);
}

if (!$sets) apiFail('Nada que actualizar.');
$vals[] = $planId;
getDB()->prepare('UPDATE planes SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
apiJson(['ok' => true]);
