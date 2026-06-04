<?php
/* Ruta Nómada — shared UI component functions (translated from components.jsx) */

/* Placeholder tint/icon maps */
$PH_TINTS = [
  'cultura'  => 'var(--neptune-600)',
  'romance'  => 'var(--naples-600)',
  'aventura' => 'var(--olive-600)',
  'desierto' => 'var(--barley-500)',
  'agua'     => 'var(--neptune-500)',
  'bosque'   => 'var(--olive-500)',
  'ciudad'   => 'var(--rino-700)',
];
$PH_ICONS = [
  'cultura'  => 'temple_buddhist', 'romance' => 'wine_bar', 'aventura' => 'landscape',
  'desierto' => 'wb_sunny',        'agua'    => 'sailing',  'bosque'   => 'forest',
  'ciudad'   => 'apartment',       'hotel'   => 'hotel',    'food'     => 'restaurant',
  'map'      => 'map',             'video'   => 'play_circle', 'beach' => 'beach_access',
  'default'  => 'image',           'sailing' => 'sailing',
];

function e($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Brand logo mark — compass rose SVG */
function logoMark($size = 34, $color = 'var(--cta)', $ring = 'var(--barley-400)') {
  return '<svg class="logo-mark" width="'.$size.'" height="'.$size.'" viewBox="0 0 40 40" fill="none" aria-hidden="true">
    <circle cx="20" cy="20" r="18.5" stroke="'.$ring.'" stroke-width="2.2" opacity="0.85"/>
    <circle cx="20" cy="20" r="13" stroke="'.$ring.'" stroke-width="1" opacity="0.4"/>
    <path d="M20 6 L24 20 L20 34 L16 20 Z" fill="'.$color.'"/>
    <path d="M6 20 L20 16 L34 20 L20 24 Z" fill="'.$ring.'" opacity="0.9"/>
    <circle cx="20" cy="20" r="2.4" fill="var(--rino-400)"/>
  </svg>';
}

/** Material icon shorthand */
function ico($name, $fill = false, $className = '', $style = '') {
  $cls = 'material-symbols-outlined' . ($fill ? ' fill' : '') . ($className ? ' ' . $className : '');
  $st = $style ? ' style="' . e($style) . '"' : '';
  return '<span class="' . $cls . '"' . $st . '>' . e($name) . '</span>';
}

/** Generic button */
function btn($text, $options = []) {
  $variant   = $options['variant']   ?? 'cta';
  $size      = $options['size']      ?? '';
  $block     = $options['block']     ?? false;
  $icon      = $options['icon']      ?? '';
  $iconRight = $options['iconRight'] ?? '';
  $type      = $options['type']      ?? 'button';
  $extra     = $options['extra']     ?? '';

  $cls = 'btn btn--' . $variant;
  if ($size === 'sm') $cls .= ' btn--sm';
  if ($block) $cls .= ' btn--block';

  $html = '<button class="' . $cls . '" type="' . $type . '"' . ($extra ? ' ' . $extra : '') . '>';
  if ($icon) $html .= ico($icon);
  $html .= e($text);
  if ($iconRight) $html .= ico($iconRight);
  $html .= '</button>';
  return $html;
}

/** Placeholder image — palette-tinted, iconographic */
function placeholder($tint = 'agua', $icon = '', $label = '') {
  global $PH_TINTS, $PH_ICONS;
  $color = $PH_TINTS[$tint] ?? $PH_TINTS['agua'];
  $icoName = $PH_ICONS[$icon] ?? $PH_ICONS[$tint] ?? $PH_ICONS['default'];

  $html = '<div class="ph" style="--ph-c: ' . $color . '">';
  $html .= '<span class="ph__ico"><span class="material-symbols-outlined">' . e($icoName) . '</span></span>';
  if ($label) {
    $html .= '<span class="ph__tag"><span class="material-symbols-outlined">add_photo_alternate</span>' . e($label) . '</span>';
  }
  $html .= '</div>';
  return $html;
}

/** Destination card */
function destinationCard($d, $index = 0) {
  $favClass = (!empty($d['fav'])) ? ' on' : '';
  $dataJson = e(json_encode($d, JSON_UNESCAPED_UNICODE));

  $html  = '<article class="dcard" data-dest-card data-dest-json=\'' . json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) . '\' data-category="' . e($d['category']) . '">';
  $html .= '  <div class="dcard__media">';
  $html .= '    <span class="dcard__cat">' . e($d['category']) . '</span>';
  $html .= '    <button class="dcard__fav' . $favClass . '" data-fav-btn aria-label="Guardar">' . ico('favorite') . '</button>';
  $html .= placeholder($d['tint'], $d['icon'], $d['name']);
  $html .= '  </div>';
  $html .= '  <div class="dcard__body">';
  $html .= '    <div class="dcard__title">' . e($d['name']) . '</div>';
  $html .= '    <div class="dcard__meta">' . ico('location_on') . e($d['country']) . '</div>';
  $html .= '    <p class="dcard__desc">' . e($d['desc']) . '</p>';
  $html .= '    <div class="dcard__foot">';
  $html .= '      <div class="dcard__price"><span class="lbl">Desde</span>';
  $html .= '        <span class="data" style="font-size:1.05rem;font-weight:600;color:var(--rino-300)">' . e($d['price']) . '</span>';
  $html .= '      </div>';
  $html .= '      <span class="dcard__rating">' . ico('star') . e($d['rating']) . '</span>';
  $html .= '    </div>';
  $html .= '  </div>';
  $html .= '</article>';
  return $html;
}

/** Story / recently uploaded item */
function storyItem($s) {
  $html  = '<article class="story">';
  $html .= '  <div class="story__thumb">' . placeholder($s['tint'], $s['icon']) . '</div>';
  $html .= '  <div class="story__body">';
  $html .= '    <span class="story__when">' . e($s['when']) . '</span>';
  $html .= '    <div class="story__title">' . e($s['title']) . '</div>';
  $html .= '    <span class="story__by">Por ' . e($s['by']) . '</span>';
  $html .= '  </div>';
  $html .= '</article>';
  return $html;
}

/** Trip card (for Mis Viajes / Mis Planes) */
function tripCard($t) {
  $STATUS_TINT = [
    'Confirmado' => 'var(--olive-400)',
    'Planeando'  => 'var(--naples-400)',
    'Borrador'   => 'var(--rino-600)',
  ];
  $statusColor = $STATUS_TINT[$t['status']] ?? 'var(--rino-600)';

  // Build a destination-like object for the detail view
  $parts = explode(',', $t['name']);
  $destData = [
    'name'     => trim($parts[0]),
    'country'  => trim($parts[1] ?? ''),
    'category' => $t['status'],
    'tint'     => $t['tint'],
    'icon'     => $t['icon'],
    'desc'     => '',
    'price'    => $t['budget'] . ' MXN',
    'rating'   => '4.8',
  ];

  $html  = '<article class="dcard" data-dest-card data-dest-json=\'' . json_encode($destData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) . '\'>';
  $html .= '  <div class="dcard__media" style="aspect-ratio:16/7">';
  $html .= '    <span class="dcard__cat" style="background:' . $statusColor . ';color:var(--rino-100)">' . e($t['status']) . '</span>';
  $html .= placeholder($t['tint'], $t['icon'], $t['name']);
  $html .= '  </div>';
  $html .= '  <div class="dcard__body">';
  $html .= '    <div class="dcard__title">' . e($t['name']) . '</div>';
  $html .= '    <div class="dcard__meta">' . ico('calendar_month') . '<span class="data">' . e($t['dates']) . '</span></div>';
  $html .= '    <div class="dcard__foot" style="margin-top:10px">';
  $html .= '      <span class="badge-data">' . ico('group', false, '', 'font-size:14px') . e($t['people']) . ' viajeros</span>';
  $html .= '      <span class="dcard__price"><span class="lbl">Presupuesto</span><span class="data" style="font-weight:600;color:var(--rino-300)">' . e($t['budget']) . '</span></span>';
  $html .= '    </div>';
  $html .= '  </div>';
  $html .= '</article>';
  return $html;
}
