<?php
// ============================================================
//  includes/planes_lib.php — Datos de Mis planes | Ruta Nómada
//
//  Los datos los sirve sp_planes_usuario (basedatos/rutinas.sql) en un
//  solo CALL con DOS result sets: los planes con sus agregados —estado
//  temporal, sitios y total de gastos, calculados por las funciones
//  fn_estado_plan, fn_sitios_plan y fn_total_gastos— y los miembros
//  para los avatares. Aquí queda lo que es presentación: los colores
//  del estado, el cruce de miembros y el orden elegido.
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
    if (!$ini) return planEstadoDatos('por');
    $hoy = date('Y-m-d');
    $fin = $fin ?: $ini;
    if ($hoy < $ini) return planEstadoDatos('por');
    if ($hoy > $fin) return planEstadoDatos('expirado');
    return planEstadoDatos('sucediendo');
}

/**
 * Texto y color de una clave de estado. La clave la calcula la base
 * (fn_estado_plan); el texto y el color son presentación y viven aquí.
 */
function planEstadoDatos(string $clave): array
{
    return match ($clave) {
        'sucediendo' => ['sucediendo', 'Sucediendo',  '#FFA500'],
        'expirado'   => ['expirado',   'Expirado',    '#FA003F'],
        default      => ['por',        'Por suceder', '#36BE3B'],
    };
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
    // Un CALL, dos result sets. El primero trae los planes con el rol
    // y los agregados que calculan las funciones de la base; el
    // segundo, los miembros de todos esos planes con el propietario
    // primero. nextRowset() salta del uno al otro y closeCursor()
    // suelta el resultado del CALL antes de la siguiente consulta.
    $st = $db->prepare('CALL sp_planes_usuario(?)');
    $st->execute([$userId]);
    $planes = $st->fetchAll();
    $st->nextRowset();
    $filasMiembros = $st->fetchAll();
    $st->closeCursor();

    if (!$planes) return [];

    $miembros = [];
    foreach ($filasMiembros as $m) $miembros[$m['plan_id']][] = $m;

    foreach ($planes as &$p) {
        $p['lugares']      = (int)$p['lugares'];
        $p['n_gastos']     = (int)$p['n_gastos'];
        $p['total_gastos'] = (float)$p['total_gastos'];
        $p['miembros']     = $miembros[(int)$p['id']] ?? [];
        $p['n_miembros']   = count($p['miembros']);
        // La clave viene de fn_estado_plan; aquí sólo se viste.
        [$p['est_clave'], $p['est_texto'], $p['est_color']] =
            planEstadoDatos((string)$p['est_clave']);
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
