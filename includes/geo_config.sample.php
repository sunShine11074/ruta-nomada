<?php
// ============================================================
//  includes/geo_config.sample.php — Plantilla | Ruta Nómada
//
//  CÓMO USARLO
//  1. Copia este archivo como  includes/geo_config.php
//  2. Pega tu clave donde dice PON_AQUI_TU_KEY
//
//  geo_config.php está en .gitignore y NUNCA se sube al repo.
//  Este .sample sí se versiona, para que cada quien sepa qué crear.
//
//  PARA QUÉ SIRVE
//  Rellena las listas de País / Estado / Ciudad del perfil y del
//  buscador (geo.php e includes/geo_lib.php). Sin la clave esas
//  listas salen vacías, pero el resto de la aplicación funciona.
//
//  DÓNDE SACAR LA CLAVE (gratuita)
//  1. Regístrate en  countrystatecity.in/
//  2. Panel → API Keys → copia la que te dan
//  El plan gratuito da un millón de peticiones al mes, de sobra para
//  este proyecto, y además geo_lib.php cachea las respuestas.
// ============================================================

return [
    'csc_key' => 'PON_AQUI_TU_KEY',
];
