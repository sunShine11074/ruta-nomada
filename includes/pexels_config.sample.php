<?php
// ============================================================
//  includes/pexels_config.sample.php — PLANTILLA | Ruta Nómada
//
//  QUÉ ES ESTO
//  El buscador de fotos de la ventana «Cambiar foto» pide las
//  imágenes a Pexels. Este archivo es la PLANTILLA: cópialo como
//  includes/pexels_config.php y pon tu clave dentro.
//
//        copy includes\pexels_config.sample.php includes\pexels_config.php
//
//  El archivo real está en .gitignore y NO se sube nunca. Si algún
//  día aparece en un `git status`, algo se hizo mal.
//
//  CÓMO CONSEGUIR LA CLAVE (gratis)
//    1. Crea una cuenta en https://www.pexels.com/join/
//    2. Entra en https://www.pexels.com/api/new/ y pide una clave
//    3. Cópiala tal cual en 'api_key', aquí abajo
//
//  LÍMITES DE LA CUENTA GRATUITA
//    200 peticiones por hora y 20.000 al mes. De sobra para el
//    proyecto: sólo se gasta una cuando alguien pulsa la lupa.
//
//  POR QUÉ PEXELS Y NO LAS FOTOS DE GOOGLE PLACES
//    Porque sus URLs NO caducan. Las de Google sí, y la portada de un
//    itinerario se guarda en la base para pintarla meses después.
//    El razonamiento completo está en Reportes_md/PLAN_cambiar_foto.md
//
//  ATRIBUCIÓN
//    Pexels pide enlazar a la foto original y nombrar a quien la hizo.
//    De eso se encarga la ficha de cada resultado en la rejilla.
// ============================================================

return [
    // Pega aquí tu clave. Es una cadena larga de letras y números.
    'api_key' => 'PON-AQUI-TU-CLAVE-DE-PEXELS',

    // Idioma de los resultados. Pexels admite es, en, pt, fr y otros.
    'idioma'  => 'es-ES',

    // Cuántas fotos pedir por búsqueda. La rejilla del diseño muestra
    // 3 columnas; 12 llena cuatro filas sin obligar a paginar.
    'por_pagina' => 12,
];
