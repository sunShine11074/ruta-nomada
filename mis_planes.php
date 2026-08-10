<?php
// ============================================================
//  mis_planes.php — Planes de viaje del usuario | Ruta Nómada
//
//  Esta pantalla LEE y BORRA. Crear y editar viven en plan.php.
//
//  Tres vistas del mismo conjunto —tabla, tarjetas y mapa— y un panel
//  fijo a la derecha con la ficha del plan seleccionado. La selección es
//  una sola: pulsar una fila, una tarjeta o un pin del mapa actualiza
//  ese panel, y el paginador de arriba recorre la lista en el orden
//  vigente.
//
//  Los datos se pintan TODOS de una vez y el cambio de vista es puro
//  CSS: así el conmutador es instantáneo y la selección no se pierde al
//  saltar de una vista a otra. Con seis u once planes esto sobra; si
//  algún día la lista crece de verdad, el punto por donde partirla es
//  planesDeUsuario() en includes/planes_lib.php.
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
require_once __DIR__ . '/includes/user_topbar.php';
require_once __DIR__ . '/includes/planes_lib.php';
require_once __DIR__ . '/includes/iconos_planes.php';
require_once __DIR__ . '/includes/plan_auth.php';   // csrfToken()
require_once __DIR__ . '/includes/maps_lib.php';    // mapsScriptUrl()

$ORDENES = ['proximos' => 'Más próximos', 'recientes' => 'Más nuevos', 'alfabetico' => 'A – Z'];
$orden = isset($_GET['orden']) && isset($ORDENES[$_GET['orden']]) ? $_GET['orden'] : 'proximos';

$planes   = [];
$error_db = null;
$db_check = checkDBConnection();
if ($db_check['ok']) {
    $db = getDB();
    $planes = planesDeUsuario($db, (int)$user['id'], $orden);
} else {
    $error_db = $db_check['error'];
}

$csrf   = csrfToken();
$paleta = planPaleta();

/** Escape corto. */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** «08.09.2026 → 10.09.2026», o el aviso de que aún no hay fechas. */
function mpFechas(?string $ini, ?string $fin): string
{
    if (!$ini) return 'Fechas por definir';
    $a = date('d.m.Y', strtotime($ini));
    if (!$fin || $fin === $ini) return $a;
    return $a . ' → ' . date('d.m.Y', strtotime($fin));
}
function mpFecha(?string $f): string { return $f ? date('d.m.Y', strtotime($f)) : '—'; }
// Los frames escriben la última modificación con guiones (10-08-2026) y
// todo lo demás con puntos. Se respeta tal cual.
function mpFechaGuion(?string $f): string { return $f ? date('d-m-Y', strtotime($f)) : '—'; }
function mpDinero($n): string { return number_format((float)$n, 0, '.', ',') . ' MXN'; }

/**
 * Portada del plan. Ninguno tiene portada_url todavía, así que se cae a
 * la foto del primer lugar añadido; si tampoco la hay, a un degradado
 * con la inicial del destino. No se usa la API de mapas estáticos a
 * propósito: se cobra por petición y aquí serían tantas como planes.
 */
function mpPortada(array $p): array
{
    if (!empty($p['portada_url'])) return ['img', $p['portada_url']];
    if (!empty($p['_foto_item']))  return ['img', $p['_foto_item']];
    $d = trim((string)($p['destino'] ?: $p['nombre']));
    return ['grad', mb_strtoupper(mb_substr($d, 0, 1) ?: 'R')];
}

// Foto del primer lugar de cada plan, para las portadas que faltan.
if ($planes) {
    $ids = array_column($planes, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $st  = $db->prepare(
        "SELECT plan_id, imagen_url FROM plan_items
          WHERE plan_id IN ($ph) AND imagen_url IS NOT NULL AND imagen_url <> ''
          ORDER BY plan_id, dia, orden, id"
    );
    $st->execute($ids);
    $fotos = [];
    foreach ($st->fetchAll() as $f) {
        if (!isset($fotos[$f['plan_id']])) $fotos[$f['plan_id']] = $f['imagen_url'];
    }
    foreach ($planes as &$p) $p['_foto_item'] = $fotos[$p['id']] ?? null;
    unset($p);
}

/** Avatares apilados, con un «+N» cuando no caben. */
function mpAvatares(array $miembros, int $tope = 4): string
{
    $o = '<span class="mp-avatares">';
    foreach (array_slice($miembros, 0, $tope) as $m) {
        $ini = mb_strtoupper(mb_substr(trim((string)$m['nombre']), 0, 1) ?: '?');
        $o .= '<span class="mp-av" title="' . h($m['nombre']) . '">';
        $o .= !empty($m['foto_perfil'])
            ? '<img src="' . h($m['foto_perfil']) . '" alt="">'
            : h($ini);
        $o .= '</span>';
    }
    // «1+», no «+1»: es como está escrito en los frames.
    $resto = count($miembros) - $tope;
    if ($resto > 0) $o .= '<span class="mp-av mp-av--mas">' . $resto . '+</span>';
    return $o . '</span>';
}

$tituloPagina = 'Mis planes — Ruta Nómada';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($tituloPagina) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="topbar.css">
  <!-- ?v= con la fecha del archivo: al cambiar el CSS o el JS, el
       navegador se baja la versión nueva en vez de servir la vieja de su
       caché. Sin esto, tras un git pull se ven estilos antiguos y parece
       que el cambio no llegó. -->
  <link rel="stylesheet" href="mis_planes.css?v=<?= @filemtime(__DIR__ . '/mis_planes.css') ?: 1 ?>">
</head>
<body class="dashboard-page">
<?php $topbar_active = 'misplanes'; include __DIR__ . '/includes/topbar.php'; ?>

<div class="mp">
  <?php if ($error_db): ?>
    <div class="mp-empty">
      <p><strong>No se pudo conectar con la base de datos.</strong></p>
      <p><?= h($error_db) ?></p>
    </div>
  <?php else: ?>

  <!-- ═══ Cabecera ═══ -->
  <div class="mp-head">
    <h1 class="mp-title">Mis planes</h1>
    <div class="mp-tools">
      <!-- Buscador y orden: colocados, sin funcionalidad todavía -->
      <button type="button" class="mp-iconbtn" id="mpBuscar" aria-label="Buscar un plan"><?= ico('lupa', 17) ?></button>
      <button type="button" class="mp-orden" id="mpOrden" aria-haspopup="listbox">
        <?= h($ORDENES[$orden]) ?><?= ico('ordenar', 15) ?>
      </button>
      <div class="mp-views" role="tablist" aria-label="Forma de ver los planes">
        <span class="mp-views__slider" id="mpSlider"></span>
        <button type="button" class="mp-views__btn" role="tab" data-vista="tabla"  aria-selected="true"  aria-label="Ver como lista"><?= ico('lista', 16) ?></button>
        <button type="button" class="mp-views__btn" role="tab" data-vista="rejilla" aria-selected="false" aria-label="Ver como tarjetas"><?= ico('rejilla', 16) ?></button>
        <button type="button" class="mp-views__btn" role="tab" data-vista="mapa"   aria-selected="false" aria-label="Ver en el mapa"><?= ico('chincheta', 16) ?></button>
      </div>
    </div>
  </div>

  <div class="mp-wrap">
    <div class="mp-main">
      <?php if (!$planes): ?>
        <div class="mp-empty">
          <p style="font-size:17px;font-weight:600;color:#0D1F27">Todavía no tienes ningún plan</p>
          <p>Crea tu primer viaje y aparecerá aquí.</p>
          <p style="margin-top:18px"><a class="mp-btn" style="display:inline-flex" href="plan.php">Crear plan de viaje</a></p>
        </div>
      <?php else: ?>

      <!-- ═══ Vista tabla ═══ -->
      <div class="mp-vista" data-vista="tabla">
        <div class="mp-tablawrap">
        <table class="mp-tabla">
          <thead>
            <tr>
              <th>Nombre del plan</th><th>Destino</th><th>Fechas de inicio y fin</th>
              <th>Estado</th><th>Presupuesto</th><th>Invitados</th><th>Fecha de creación</th><th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($planes as $i => $p): ?>
            <tr data-idx="<?= $i ?>" tabindex="0" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
              <td class="mp-td-nombre" title="<?= h($p['nombre']) ?>"><?= h($p['nombre']) ?></td>
              <td><?= h($p['destino'] ?: '—') ?></td>
              <td><?= h(mpFechas($p['fecha_inicio'], $p['fecha_fin'])) ?></td>
              <!-- En la tabla el estado va en texto llano, sin color ni
                   punto: el color vive en el panel de la derecha. -->
              <td><?= h($p['est_texto']) ?></td>
              <td><?= $p['presupuesto'] !== null ? h(mpDinero($p['presupuesto'])) : '<span class="mp-vacio">Sin asignar</span>' ?></td>
              <td><?= (int)$p['n_miembros'] ?></td>
              <td><?= h(mpFecha($p['creado_en'])) ?></td>
              <!-- Puntos en HORIZONTAL aquí y en vertical en las tarjetas,
                   como en los frames. Es el mismo icono girado. -->
              <td style="text-align:right"><span class="mp-puntos-h"><?= ico('puntos', 15) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <!-- ═══ Vista tarjetas ═══ -->
      <div class="mp-vista mp-oculto" data-vista="rejilla">
        <div class="mp-grid">
        <?php foreach ($planes as $i => $p):
            $c = $paleta[$i % 10];   // la 11.ª repite la dupla de la 1.ª
            $esProp = $p['rol'] === 'propietario';
        ?>
          <article class="mp-card" data-idx="<?= $i ?>" tabindex="0" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                   style="background:<?= h($c['bg']) ?>">
            <span class="mp-card__deco" style="background:<?= h($c['deco']) ?>"></span>
            <button type="button" class="mp-card__menu" aria-label="Más opciones de <?= h($p['nombre']) ?>"><?= icoAlto('puntos', 12) ?></button>
            <h3 class="mp-card__t" title="<?= h($p['nombre']) ?>"><?= h($p['nombre']) ?></h3>
            <!-- Alturas de dibujo medidas del frame: calendario 11, cerdito
                 y lápiz 13, pin 10, corona 7. -->
            <span class="mp-card__linea"><?= icoAlto('calendario', 11) ?><?= h(mpFechas($p['fecha_inicio'], $p['fecha_fin'])) ?></span>
            <div class="mp-card__pills">
              <!-- El pin va SIEMPRE en rojo, como en los frames: no sigue
                   el color del estado temporal. -->
              <span class="mp-card__pill"><?= icoAlto('ubicacion', 10, '#FA003F') ?><?= (int)$p['lugares'] ?> sitios</span>
              <span class="mp-card__rol"><?= icoAlto($esProp ? 'propietario' : 'invitado', 7, '#ffffff') ?><?= $esProp ? 'Propietario' : 'Invitado' ?></span>
            </div>
            <div class="mp-rotulo">Participantes:</div>
            <?= mpAvatares($p['miembros'], 3) ?>
            <div class="mp-rotulo">Presupuesto asignado:</div>
            <span class="mp-card__linea mp-card__val"><?= icoAlto('cerdito', 13) ?><?= $p['presupuesto'] !== null ? h(mpDinero($p['presupuesto'])) : 'Sin asignar' ?></span>
            <div class="mp-rotulo">Última modificación:</div>
            <span class="mp-card__linea mp-card__val"><?= icoAlto('lapiz', 13) ?><?= h(mpFechaGuion($p['updated_at'])) ?></span>
          </article>
        <?php endforeach; ?>
        </div>
      </div>

      <!-- ═══ Vista mapa ═══ -->
      <div class="mp-vista mp-oculto" data-vista="mapa">
        <div class="mp-mapa">
          <div class="mp-mapa__lienzo" id="mpMapa"></div>
          <div class="mp-zoom">
            <button type="button" id="mpZoomIn"  aria-label="Acercar">+</button>
            <button type="button" id="mpZoomOut" aria-label="Alejar">−</button>
          </div>
        </div>
      </div>

      <?php endif; ?>
    </div>

    <!-- ═══ Panel derecho ═══ -->
    <?php if ($planes): ?>
    <aside class="mp-side" id="mpPanel" aria-live="polite">
      <div class="mp-pager">
        <button type="button" id="mpPrev" aria-label="Plan anterior"><?= icoAlto('chevronIzq', 11, '#0B0B0B') ?></button>
        <span class="mp-pager__n" id="mpPagerN">1 de <?= count($planes) ?></span>
        <button type="button" id="mpNext" aria-label="Plan siguiente"><?= icoAlto('chevronDer', 11, '#0B0B0B') ?></button>
      </div>
      <div class="mp-ficha" id="mpFicha"></div>
    </aside>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- El asistente NO se incluye aquí: ya lo trae includes/topbar.php al
     final. Incluirlo otra vez duplicaba la burbuja y sus ids, y
     js/asistente.js se enganchaba a la primera copia mientras la de
     arriba era la que se veía, así que no abría con el clic. -->

<script>
window.MP_PLANES = <?= json_encode(array_map(function ($p) {
    [$tipo, $val] = mpPortada($p);
    return [
        'id'       => (int)$p['id'],
        'nombre'   => $p['nombre'],
        'destino'  => $p['destino'] ?: '',
        'lat'      => $p['lat'] !== null ? (float)$p['lat'] : null,
        'lng'      => $p['lng'] !== null ? (float)$p['lng'] : null,
        'fechas'   => mpFechas($p['fecha_inicio'], $p['fecha_fin']),
        'estTexto' => $p['est_texto'],
        'estColor' => $p['est_color'],
        'lugares'  => (int)$p['lugares'],
        'rol'      => $p['rol'],
        'presup'   => $p['presupuesto'] !== null ? mpDinero($p['presupuesto']) : null,
        'nGastos'  => (int)$p['n_gastos'],
        'totGastos'=> mpDinero($p['total_gastos']),
        'modif'    => mpFecha($p['updated_at']),
        'modifGuion' => mpFechaGuion($p['updated_at']),
        'portTipo' => $tipo,
        'portVal'  => $val,
        'avatares' => mpAvatares($p['miembros'], 5),
    ];
}, $planes), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.MP_CSRF = <?= json_encode($csrf) ?>;
// Medidos del frame: 14 px de alto todos menos el de la transferencia,
// que va a 13. Por eso icoAlto() y no ico(): lo que el diseño mide es el
// dibujo, y el lienzo cuadrado de 640 no dice nada de él.
window.MP_ICONOS = <?= json_encode([
    'chincheta'   => icoAlto('chincheta', 14),
    'brujula'     => icoAlto('brujula', 14),
    'sitios'      => icoAlto('ubicacion', 14, '#FA003F'),
    'calendario'  => icoAlto('calendario', 14),
    'cerdito'     => icoAlto('cerdito', 14, '#1B4FD8'),
    'recibo'      => icoAlto('recibo', 14, '#1B4FD8'),
    'transferir'  => icoAlto('transferir', 13, '#1B4FD8'),
    'lapiz'       => icoAlto('lapiz', 14, '#1B4FD8'),
    'lapizBlanco' => icoAlto('lapiz', 16, '#ffffff'),
    'papelera'    => icoAlto('papelera', 14, '#ffffff'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script>
// El script de Maps se descarga siempre, pero el mapa NO se crea hasta
// que se abre su vista: Google factura por mapa instanciado, no por
// cargar la librería, así que quien no use la vista de mapa no gasta.
window.mpMapsReady = new Promise(function (res) { window.__mpMapReady = res; });
</script>
<?php $_mapsUrl = mapsScriptUrl('__mpMapReady'); ?>
<?php if ($_mapsUrl !== ''): ?>
<script async src="<?= h($_mapsUrl) ?>"></script>
<?php else: ?>
<script>window.__mpMapReady(); console.warn('[Ruta Nómada] Falta includes/maps_config.php: la vista de mapa no se va a cargar.');</script>
<?php endif; ?>
<script src="js/topbar.js"></script>
<script src="js/mis_planes.js?v=<?= @filemtime(__DIR__ . '/js/mis_planes.js') ?: 1 ?>"></script>
</body>
</html>
