<?php
// ============================================================
//  api/plan_update.php — Campos sueltos del plan | Ruta Nómada
//  POST {plan_id, campo: valor, ...}
//  Campos permitidos: nombre, fecha_inicio, fecha_fin, privacidad,
//  presupuesto, lat, lng, portada_url, dia_subtitulos (array)
//
//  CÓMO SE REPARTEN EL TRABAJO PHP Y LA BASE
//  La escritura la hace sp_actualizar_plan (basedatos/rutinas.sql),
//  que recibe la fila COMPLETA. Aquí se valida cada campo recibido y
//  se fusiona sobre la fila actual —planAccess() ya la trae entera—,
//  de modo que NULL significa NULL de verdad: se puede quitar una
//  fecha o una portada. Un COALESCE en el procedimiento no sabría
//  distinguir «no tocar» de «poner a NULL»; la fusión aquí sí.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$planId = (int)$in['plan_id'];

// La fila actual: todo lo que no venga en la petición se conserva.
$fila   = $acc['plan'];
$tocado = false;

if (array_key_exists('nombre', $in)) {
    $n = mb_substr(trim((string)$in['nombre']), 0, 200);
    if ($n === '') apiFail('El nombre no puede quedar vacío.');
    $fila['nombre'] = $n;
    $tocado = true;
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
        $fila[$f] = $v;
        $tocado = true;
    }
}
if (array_key_exists('privacidad', $in)) {
    if (!in_array($in['privacidad'], ['solo', 'amigos', 'publico'], true)) apiFail('Privacidad inválida.');
    $fila['privacidad'] = $in['privacidad'];
    $tocado = true;
}
if (array_key_exists('presupuesto', $in)) {
    $p = $in['presupuesto'];
    if ($p !== null && $p !== '' && (!is_numeric($p) || $p < 0 || $p > 99999999)) apiFail('Presupuesto inválido.');
    $fila['presupuesto'] = ($p === '' || $p === null) ? null : (float)$p;
    $tocado = true;
}
foreach (['lat' => 90, 'lng' => 180] as $f => $max) {
    if (array_key_exists($f, $in)) {
        $v = $in[$f];
        if ($v !== null && $v !== '' && (!is_numeric($v) || abs($v) > $max)) apiFail('Coordenada inválida.');
        $fila[$f] = ($v === '' || $v === null) ? null : (float)$v;
        $tocado = true;
    }
}
if (array_key_exists('portada_url', $in)) {
    $fila['portada_url'] = mb_substr((string)$in['portada_url'], 0, 500) ?: null;
    $tocado = true;
}
if (array_key_exists('dia_subtitulos', $in)) {
    $arr = is_array($in['dia_subtitulos']) ? array_map(fn($s) => mb_substr((string)$s, 0, 200), $in['dia_subtitulos']) : [];
    $fila['dia_subtitulos'] = json_encode(array_slice($arr, 0, 60), JSON_UNESCAPED_UNICODE);
    $tocado = true;
}

if (!$tocado) apiFail('Nada que actualizar.');

$st = getDB()->prepare('CALL sp_actualizar_plan(?,?,?,?,?,?,?,?,?,?)');
$st->execute([
    $planId,
    $fila['nombre'],
    $fila['fecha_inicio'],
    $fila['fecha_fin'],
    $fila['privacidad'],
    $fila['presupuesto'],
    $fila['lat'],
    $fila['lng'],
    $fila['portada_url'],
    $fila['dia_subtitulos'],
]);
$st->closeCursor();
apiJson(['ok' => true]);
