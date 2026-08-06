<?php
// ============================================================
//  crear_plan.php — Redirección | Ruta Nómada
//
//  Aquí vivía el planificador viejo: una SPA de tres pantallas
//  (Crear → Invitar → Planificador) que guardaba el itinerario en
//  localStorage, con una lista de doce ciudades escrita a mano y
//  sin colaboración ni mapa real.
//
//  El itinerario de verdad es plan.php, y la creación de un viaje
//  vive ahora en plan.php sin ?id= (includes/plan_nuevo.php). Este
//  archivo sólo queda para que los enlaces y marcadores antiguos
//  sigan funcionando; el código anterior está en el historial de git.
//
//  302 y no 301 a propósito: el navegador guarda los 301 para siempre
//  y luego cuesta mucho deshacerlos si algún día se vuelve atrás.
// ============================================================
header('Location: plan.php', true, 302);
exit;
