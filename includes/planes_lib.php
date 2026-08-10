<?php
// ============================================================
//  includes/planes_lib.php — Datos de Mis planes | Ruta Nómada
//
//  Reúne en TRES consultas todo lo que piden la tabla, las tarjetas, el
//  mapa y el panel derecho. La versión anterior usaba subconsultas
//  correlacionadas (una por fila para contar lugares, otra para contar
//  miembros): con seis planes daba igual, pero al añadir orden y filtros
//  la lista crece y ese patrón se paga por cada plan. Aquí se agrupa una
//  sola vez por tabla y se cruza en PHP.
//
//  Son tres y no una porque mezclar en la misma consulta varios COUNT
//  sobre tablas distintas multiplica las filas entre sí: contar lugares
//  y gastos a la vez daría lugares × gastos. Agregar por separado es más
//  barato y, sobre todo, correcto.
// ============================================================

/**
 * Estado temporal del viaje respecto a hoy.
 * Devuelve ['clave', 'texto', '#color'].
 *
 * Sin fecha de inicio no se puede situar en el tiempo: se trata como
 * pendiente, que es lo que es un viaje al que aún no le han puesto
 * fechas. La fecha de fin, si falta, se toma igual a la de inicio (un
 * viaje de un solo día).
 */
function planEstadoTemporal(?string $ini, ?string $fin): array
{
    if (!$ini) return ['por', 'Por suceder', '#36BE3B'];
    $hoy = date('Y-m-d');
    $fin = $fin ?: $ini;
    if ($hoy < $ini) return ['por',       'Por suceder', '#36BE3B'];
    if ($hoy > $fin) return ['expirado',  'Expirado',    '#FA003F'];
    return                  ['sucediendo','Sucediendo',  '#FFA500'];
}

/** Las diez duplas de color de las tarjetas, en el orden del diseño. */
function planPaleta(): array
{
    return [
        ['bg' => '#D9F7F2', 'deco' => '#7FE3D4'],  //  1 turquesa
        ['bg' => '#FDDDE6', 'deco' => '#F9A8C0'],  //  2 rosa
        ['bg' => '#D9EAFB', 'deco' => '#9CC9F0'],  //  3 azul
        ['bg' => '#FDF3D0', 'deco' => '#F7DC8A'],  //  4 amarillo
        ['bg' => '#E6DFFA', 'deco' => '#BFAEEE'],  //  5 lila
        ['bg' => '#EADDFB', 'deco' => '#C6A9F0'],  //  6 morado
        ['bg' => '#FDE6D2', 'deco' => '#F7BE8A'],  //  7 naranja
        ['bg' => '#DDE4FB', 'deco' => '#AEBCF0'],  //  8 azul lavanda
        ['bg' => '#FBDEDE', 'deco' => '#F0AEAE'],  //  9 rojo
        ['bg' => '#DDF7DE', 'deco' => '#A6E9A8'],  // 10 verde
    ];
}

/**
 * Todos los planes del usuario, ya cruzados con sus agregados.
 * $orden: 'proximos' (por fecha de viaje) | 'recientes' (por edición) |
 *         'alfabetico'
 */
function planesDeUsuario(PDO $db, int $userId, string $orden = 'proximos'): array
{
    // ── 1. Los planes y su rol ──────────────────────────────
    $st = $db->prepare(
        'SELECT p.id, p.nombre, p.destino, p.lat, p.lng,
                p.fecha_inicio, p.fecha_fin, p.portada_url, p.presupuesto,
                p.creado_en, p.updated_at, m.rol
           FROM planes p
           JOIN plan_miembros m ON m.plan_id = p.id AND m.usuario_id = ?'
    );
    $st->execute([$userId]);
    $planes = $st->fetchAll();
    if (!$planes) return [];

    $ids = array_column($planes, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));

    // ── 2. Cuántos lugares tiene cada plan ──────────────────
    $st = $db->prepare("SELECT plan_id, COUNT(*) n FROM plan_items WHERE plan_id IN ($ph) GROUP BY plan_id");
    $st->execute($ids);
    $lugares = array_column($st->fetchAll(), 'n', 'plan_id');

    // ── 3. Gastos: cuántos y cuánto ─────────────────────────
    $st = $db->prepare("SELECT plan_id, COUNT(*) n, COALESCE(SUM(monto),0) total
                          FROM plan_gastos WHERE plan_id IN ($ph) GROUP BY plan_id");
    $st->execute($ids);
    $gastos = [];
    foreach ($st->fetchAll() as $g) $gastos[$g['plan_id']] = $g;

    // ── 4. Miembros con su foto, para los avatares ──────────
    // Una sola consulta para todos los planes; se agrupa en PHP. El orden
    // pone al propietario primero para que su cara abra la fila.
    $st = $db->prepare(
        "SELECT mm.plan_id, mm.rol, u.id AS uid, u.nombre, u.foto_perfil
           FROM plan_miembros mm
           JOIN usuarios u ON u.id = mm.usuario_id
          WHERE mm.plan_id IN ($ph)
          ORDER BY mm.plan_id, FIELD(mm.rol,'propietario','editor','lector'), u.nombre"
    );
    $st->execute($ids);
    $miembros = [];
    foreach ($st->fetchAll() as $m) $miembros[$m['plan_id']][] = $m;

    // ── 5. Cruce ────────────────────────────────────────────
    foreach ($planes as &$p) {
        $id = (int)$p['id'];
        $p['lugares']  = (int)($lugares[$id] ?? 0);
        $p['n_gastos'] = (int)($gastos[$id]['n'] ?? 0);
        $p['total_gastos'] = (float)($gastos[$id]['total'] ?? 0);
        $p['miembros'] = $miembros[$id] ?? [];
        $p['n_miembros'] = count($p['miembros']);
        [$p['est_clave'], $p['est_texto'], $p['est_color']] =
            planEstadoTemporal($p['fecha_inicio'], $p['fecha_fin']);
    }
    unset($p);

    return planesOrdenar($planes, $orden);
}

/**
 * Ordena la lista ya cargada.
 *
 * 'proximos' es el orden por defecto y ordena por FECHA DE VIAJE, no por
 * fecha de edición: en una pantalla que sirve para encontrar un viaje,
 * "el más próximo primero" es lo que se espera. Los que están sucediendo
 * van arriba del todo, después los que no han empezado (el más cercano
 * primero) y al final los expirados (el más reciente primero). Los que
 * no tienen fechas se quedan con los pendientes.
 */
function planesOrdenar(array $planes, string $orden): array
{
    if ($orden === 'alfabetico') {
        usort($planes, fn($a, $b) => strcasecmp($a['nombre'], $b['nombre']));
        return $planes;
    }
    if ($orden === 'recientes') {
        usort($planes, fn($a, $b) => strcmp((string)$b['updated_at'], (string)$a['updated_at']));
        return $planes;
    }

    $peso = ['sucediendo' => 0, 'por' => 1, 'expirado' => 2];
    usort($planes, function ($a, $b) use ($peso) {
        $pa = $peso[$a['est_clave']]; $pb = $peso[$b['est_clave']];
        if ($pa !== $pb) return $pa <=> $pb;
        // Sin fecha, al final de su grupo.
        if (!$a['fecha_inicio'] && !$b['fecha_inicio']) return strcmp($b['updated_at'], $a['updated_at']);
        if (!$a['fecha_inicio']) return 1;
        if (!$b['fecha_inicio']) return -1;
        // Expirados: el más reciente primero. El resto: el más cercano primero.
        return $a['est_clave'] === 'expirado'
            ? strcmp($b['fecha_inicio'], $a['fecha_inicio'])
            : strcmp($a['fecha_inicio'], $b['fecha_inicio']);
    });
    return $planes;
}
