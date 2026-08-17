<?php
// ============================================================
//  creditos.php — Atribuciones | Ruta Nómada
//
//  POR QUÉ EXISTE ESTA PÁGINA, y no es documentación interna:
//  la licencia gratuita de Flaticon OBLIGA a dar crédito al autor de
//  cada icono, y el crédito tiene que estar donde el usuario pueda
//  llegar. Un comentario en el código no cumple.
//
//  La tabla de iconos NO se escribe aquí: se lee de
//  img/iconos/CREDITOS.md, que es donde el equipo añade cada icono
//  nuevo. Así sólo hay una lista y no dos que acaben divergiendo —el
//  mismo motivo por el que la lista de dominios de correo se unificó
//  en includes/email_dominio.php.
//
//  Sin sesión a propósito: las atribuciones tienen que verse aunque
//  no hayas entrado.
// ============================================================
require_once __DIR__ . '/db.php';

// Se leen las filas de la tabla del .md. Si el archivo no está, la
// página sigue saliendo con el resto de créditos en vez de romperse.
$iconos = [];
$md = @file_get_contents(__DIR__ . '/img/iconos/CREDITOS.md');
if ($md !== false) {
    foreach (preg_split('/\R/', $md) as $l) {
        $l = trim($l);
        if ($l === '' || $l[0] !== '|') continue;
        $c = array_map('trim', array_slice(explode('|', $l), 1, -1));
        if (count($c) < 5) continue;
        if ($c[0] === 'Archivo' || strpos($c[0], '---') === 0) continue;
        $iconos[] = $c;
    }
}
// Los enlaces en Markdown, [texto](url), pasan a <a>. Se escapa ANTES
// de meter la etiqueta: si no, un texto con < en el .md se colaría
// como HTML en esta página.
$md2html = function (string $s): string {
    $e = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    return preg_replace_callback(
        '/\[([^\]]+)\]\(([^)]+)\)/',
        function ($m) {
            $u = filter_var(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_URL);
            if (!$u || !preg_match('#^https?://#i', $u)) return $m[1];
            return '<a href="' . htmlspecialchars($u, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        },
        $e
    );
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Créditos — Ruta Nómada</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{margin:0;background:#F7F8F9;font-family:Poppins,system-ui,sans-serif;color:#212529}
  .w{max-width:820px;margin:0 auto;padding:40px 24px 70px}
  h1{font-size:27px;font-weight:700;margin:0 0 6px}
  .sub{color:#6C757D;font-size:14px;margin:0 0 34px}
  h2{font-size:17px;font-weight:700;margin:34px 0 12px}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(13,31,39,.08)}
  th,td{text-align:left;padding:11px 14px;font-size:13.5px;border-bottom:1px solid #EDF1F3}
  th{background:#F1F3F5;font-weight:600;font-size:12.5px;color:#495057}
  tr:last-child td{border-bottom:none}
  p{font-size:13.8px;line-height:1.65;color:#33454e}
  a{color:#1A73C8}
  .avi{background:#FFF6E5;border-left:3px solid #F5B93F;padding:12px 15px;border-radius:6px;font-size:13.2px;line-height:1.6}
  img.ic{width:18px;height:18px;vertical-align:middle;margin-right:7px}
</style>
</head>
<body>
<div class="w">
  <h1>Créditos</h1>
  <p class="sub">Recursos de terceros que usa Ruta Nómada, con su atribución.</p>

  <h2>Iconos</h2>
  <?php if ($iconos): ?>
    <table>
      <tr><th>Icono</th><th>Qué es</th><th>Autor</th><th>Fuente</th></tr>
      <?php foreach ($iconos as $f): ?>
        <tr>
          <td>
            <?php $ruta = 'img/iconos/' . $f[0];
                  if (is_file(__DIR__ . '/' . $ruta)): ?>
              <img class="ic" src="<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <?php endif; ?>
            <code><?= htmlspecialchars($f[0], ENT_QUOTES, 'UTF-8') ?></code>
          </td>
          <td><?= $md2html($f[1]) ?></td>
          <td><?= $md2html($f[2]) ?></td>
          <td><?= $md2html($f[3]) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php
    // Si algún autor sigue sin rellenar, se dice aquí y no sólo en el
    // .md: es el único sitio donde alguien lo va a ver.
    $sinAutor = array_filter($iconos, fn($f) => stripos($f[2], 'pendiente') !== false || $f[2] === '');
    if ($sinAutor): ?>
      <p class="avi"><strong>Atribución incompleta.</strong>
        <?= count($sinAutor) ?> icono<?= count($sinAutor) === 1 ? '' : 's' ?> sin nombre de autor.
        La licencia gratuita de Flaticon obliga a nombrarlo, así que hay que rellenarlo
        en <code>img/iconos/CREDITOS.md</code> antes de publicar el proyecto.</p>
    <?php endif; ?>
  <?php else: ?>
    <p>No se pudo leer <code>img/iconos/CREDITOS.md</code>.</p>
  <?php endif; ?>

  <h2>Tipografía e iconografía de interfaz</h2>
  <p>
    <strong>Mona Sans</strong>, de GitHub, con licencia SIL Open Font License 1.1
    (<code>fonts/OFL.txt</code>).<br>
    <strong>Font Awesome Free 7.3.1</strong>, con licencia
    <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">CC BY 4.0</a>.
    Son los iconos que van dibujados en el propio HTML; su detalle está en
    <code>includes/iconos_planes.php</code>.<br>
    <strong>Poppins</strong>, de Indian Type Foundry, con licencia SIL Open Font License 1.1.
  </p>

  <h2>Datos y contenidos</h2>
  <p>
    Mapas, fichas de lugares, reseñas y fotos de sitios: <strong>Google Maps Platform</strong>.<br>
    Textos enciclopédicos: <strong>Wikipedia</strong>, con licencia
    <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer">CC BY-SA</a>;
    cada ficha enlaza al artículo del que salió.<br>
    Fotos de portada de los viajes: <strong>Pexels</strong>.<br>
    Países, estados y ciudades: <strong>CountryStateCity API</strong>.<br>
    Redacción de los consejos de «Saber antes de ir»: <strong>Google Gemini</strong>.
    Son consejos generales del destino, no del negocio concreto.
  </p>
</div>
</body>
</html>
