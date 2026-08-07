<?php
// ============================================================
//  includes/maps_config.sample.php — Plantilla | Ruta Nómada
//
//  CÓMO USARLO
//  1. Copia este archivo como  includes/maps_config.php
//  2. Pega tu clave de Google Maps donde dice PON_AQUI_TU_KEY
//
//  maps_config.php está en .gitignore y NUNCA se sube al repo.
//  Este .sample sí se versiona, para que cada quien sepa qué crear.
//
//  DÓNDE SACAR LA CLAVE
//  console.cloud.google.com → APIs y servicios → Credenciales →
//  Crear credenciales → Clave de API.
//  Hay que habilitar estas APIs en el proyecto:
//    · Maps JavaScript API   (el mapa)
//    · Places API            (buscar lugares, reseñas, horarios)
//    · Places API (New)      (la descripción "Acerca de" de cada sitio)
//    · Geocoding API         (convertir el destino en coordenadas)
//    · Routes API            (la ruta por carretera entre los lugares de
//                             un día; si falta, el mapa une los sitios con
//                             rectas PUNTEADAS en vez de seguir las calles)
//
//  Para comprobar de una vez si están todas:
//      php herramientas/diagnostico.php
//
//  ⚠ RESTRÍNGELA ANTES DE PUBLICAR NADA. Esta clave viaja en el HTML
//  y cualquiera la puede leer con Ctrl+U; eso es normal en una clave
//  de navegador, pero SIN restricciones cualquiera puede usarla y el
//  cargo llega a tu cuenta. En Credenciales → la clave:
//    · Restricción de aplicación → Sitios web → tu dominio completo,
//      por ejemplo  https://tusitio.example.com/*
//      (para XAMPP local:  http://localhost/*)
//      NUNCA uses un comodín en un hosting compartido, tipo
//      https://*.algun-hosting-gratis.net/*  — eso autoriza a todos
//      los demás usuarios de ese hosting a gastar con tu clave.
//    · Restricción de API → sólo las cinco de arriba.
//  Y ponle CUOTAS diarias (no sólo un presupuesto: el presupuesto
//  avisa por correo, la cuota sí detiene el gasto).
// ============================================================
return [
    'maps_key' => 'PON_AQUI_TU_KEY',
];
