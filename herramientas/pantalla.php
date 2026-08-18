<?php
/**
 * herramientas/pantalla.php — Qué dice cada máquina | Ruta Nómada
 *
 * Un equipo de cuatro con cuatro portátiles distintos ve la misma página
 * de tamaños distintos, y averiguar por qué por la consola sale mal:
 *
 *   · `innerWidth` NO es el ancho de la pantalla. Con las DevTools
 *     acopladas al lateral mide sólo lo que le queda a la página, así
 *     que dos navegadores en el mismo portátil dan cifras distintas.
 *   · La raíz tipográfica depende de SI LA PÁGINA CARGA style.css.
 *     plan.php y creditos.php no la cargan, así que medir ahí devuelve
 *     el valor por defecto del navegador y no el del proyecto.
 *
 * Esta página evita las dos trampas: carga style.css a propósito, y
 * enseña `screen` y `outerWidth`, que no dependen de las DevTools.
 *
 * No toca la base de datos ni la sesión: es sólo lectura del navegador.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Qué dice tu pantalla — Ruta Nómada</title>
<!-- A PROPÓSITO: sin esta hoja la raíz medida sería la del navegador y
     no la del proyecto, que es justo el error que se quiere evitar. -->
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../style.css') ?: 1 ?>">
<style>
  .pt-caja { max-width: 760px; margin: 40px auto; padding: 0 22px; font-family: system-ui, sans-serif; }
  .pt-caja h1 { font-size: 26px; margin: 0 0 6px; }
  .pt-caja p.sub { margin: 0 0 26px; color: #6B7A83; font-size: 14px; }
  .pt-fila { display: flex; align-items: baseline; gap: 14px; padding: 11px 0; border-bottom: 1px solid #E9ECEF; }
  .pt-et { flex: 0 0 220px; color: #6B7A83; font-size: 13px; }
  .pt-val { font-size: 19px; font-weight: 700; color: #0D1F27; font-family: ui-monospace, monospace; }
  .pt-nota { font-size: 12px; color: #6B7A83; }
  .pt-btn { margin-top: 24px; border: none; border-radius: 10px; background: #0E2A33; color: #fff;
            padding: 12px 22px; font-size: 14px; font-weight: 600; cursor: pointer; }
  .pt-ok { color: #2E7D32; } .pt-mal { color: #C62828; }
</style>
</head>
<body>
<div class="pt-caja">
  <h1>Qué dice tu pantalla</h1>
  <p class="sub">Pulsa «Copiar» y pega el resultado en el chat del equipo. No hace falta abrir la consola.</p>
  <div id="pt-lista"></div>
  <button class="pt-btn" id="pt-copiar">Copiar todo</button>
  <p class="pt-nota" id="pt-aviso" style="margin-top:14px"></p>
</div>
<script>
(function () {
  var raiz = getComputedStyle(document.documentElement).fontSize;
  // Si style.css cargó, la raíz es la del proyecto. Si no, el navegador
  // pone la suya y la comparación entre máquinas no valdría nada.
  var hojaOk = false;
  for (var i = 0; i < document.styleSheets.length; i++) {
    try { if ((document.styleSheets[i].href || '').indexOf('style.css') >= 0) hojaOk = true; } catch (e) {}
  }
  var dpr = window.devicePixelRatio || 1;

  // Algunos navegadores empotrados devuelven 0 en screen y outerWidth.
  // Se usa el mejor dato disponible y se dice CUAL, en vez de enseñar un
  // cero que parece una medida.
  var anchoRef, origen;
  if (screen && screen.width > 0)      { anchoRef = screen.width;  origen = 'pantalla'; }
  else if (window.outerWidth > 0)      { anchoRef = outerWidth;    origen = 'ventana'; }
  else                                 { anchoRef = innerWidth;    origen = 'area de pagina (las DevTools la encogen)'; }

  function par(a, b) { return (a > 0 && b > 0) ? (a + ' x ' + b) : 'no disponible'; }

  var datos = [
    ['Raíz tipográfica', raiz, hojaOk ? 'style.css cargada: es la del proyecto' : 'ATENCION: style.css NO cargo'],
    ['Escala de Windows', Math.round(dpr * 100) + '%', 'devicePixelRatio = ' + dpr],
    ['Pantalla (px CSS)', par(screen.width, screen.height), 'lo que ve el navegador'],
    ['Pantalla (px reales)', par(Math.round(screen.width * dpr), Math.round(screen.height * dpr)), 'resolucion fisica'],
    ['Ventana', par(outerWidth, outerHeight), 'no le afectan las DevTools'],
    ['Área de la página', par(innerWidth, innerHeight), 'SI le afectan las DevTools'],
    ['Ancho de referencia', anchoRef + ' px CSS', 'medido en: ' + origen],
    ['Panel de mis_planes', anchoRef > 1180 ? 'al lado' : 'DEBAJO', 'el corte esta en 1180 px CSS'],
    ['Zoom de la página', (outerWidth > 0 ? Math.round(innerWidth / outerWidth * 100) + '% aprox.' : 'no disponible'), 'aproximado'],
    ['Navegador', navigator.userAgent.slice(0, 90), '']
  ];
  var cont = document.getElementById('pt-lista'), texto = [];
  datos.forEach(function (d) {
    var f = document.createElement('div');
    f.className = 'pt-fila';
    var et = document.createElement('span'); et.className = 'pt-et'; et.textContent = d[0];
    var vl = document.createElement('span'); vl.className = 'pt-val'; vl.textContent = d[1];
    var nt = document.createElement('span'); nt.className = 'pt-nota'; nt.textContent = d[2];
    f.appendChild(et); f.appendChild(vl); f.appendChild(nt);
    cont.appendChild(f);
    texto.push(d[0] + ': ' + d[1] + (d[2] ? '  (' + d[2] + ')' : ''));
  });
  if (!hojaOk) {
    var a = document.getElementById('pt-aviso');
    a.className = 'pt-nota pt-mal';
    a.textContent = 'style.css no se cargo: comprueba la ruta ../style.css.';
  }
  document.getElementById('pt-copiar').addEventListener('click', function () {
    var t = texto.join('\n');
    var btn = this;
    function ok() { btn.textContent = 'Copiado'; setTimeout(function () { btn.textContent = 'Copiar todo'; }, 1600); }
    if (navigator.clipboard) { navigator.clipboard.writeText(t).then(ok, function(){ pega(t, ok); }); }
    else pega(t, ok);
  });
  function pega(t, ok) {
    var ta = document.createElement('textarea');
    ta.value = t; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); ok(); } catch (e) {}
    ta.remove();
  }
})();
</script>
</body>
</html>
