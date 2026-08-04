<?php
// ============================================================
//  includes/ai_config.sample.php — Plantilla | Ruta Nómada
//
//  CÓMO USARLO
//  1. Copia este archivo como  includes/ai_config.php
//  2. Pega tu propia clave de Gemini donde dice PON_AQUI_TU_KEY_GEMINI
//
//  ai_config.php está en .gitignore y NUNCA se sube al repo.
//  Este .sample sí se versiona, para que cada quien sepa qué crear.
//  Cada colaborador usa SU propia clave: así una fuga se revoca
//  sin dejar sin asistente al resto del equipo.
//
//  DÓNDE SACAR LA CLAVE
//  · Créala en https://aistudio.google.com/apikey  ← NO desde Cloud Console.
//    Las claves nuevas de AI Studio nacen como "auth key"; las "standard"
//    que genera Cloud Console dejarán de ser aceptadas por la Gemini API.
//  · Debe quedar restringida SÓLO a "Generative Language API".
//  · NO reutilices la clave de Google Maps: una clave admite un único tipo
//    de restricción de cliente, y la de Maps necesita referente HTTP
//    (viaja en el HTML) mientras que ésta sale desde el servidor.
//
//  ⚠ COSTO: si el proyecto de Google Cloud tiene facturación activa,
//  la Gemini API se cobra desde el primer token — tener billing es
//  justamente lo que quita la capa gratuita. Un proyecto sin facturación
//  sí es gratis, pero entonces Google puede usar las conversaciones
//  para entrenar sus modelos y revisores humanos pueden leerlas.
// ============================================================
return [
    // Tu clave de Gemini.
    'gemini_key' => 'PON_AQUI_TU_KEY_GEMINI',

    // Modelo. Los IDs rotan cada pocos meses; por eso está aquí y no
    // incrustado en la URL. Ejecuta  php herramientas/ai_test.php
    // para ver cuáles acepta TU clave antes de cambiarlo.
    //   gemini-2.5-flash-lite → el más barato; no "razona", así que no
    //                           gasta tokens de pensamiento.
    //   gemini-3.5-flash-lite → generación 3.x, algo mejor, más caro.
    'modelo' => 'gemini-2.5-flash-lite',

    // Versión de la ruta de la API.
    'api_version' => 'v1beta',

    // Topes de uso por usuario (no por sesión: la sesión se reinicia
    // borrando una cookie). Bajos a propósito: es un proyecto escolar
    // y lo que se quiere evitar es la factura por un bucle accidental.
    'limite_minuto' => 5,
    'limite_dia' => 60,
];
