<?php
// ============================================================
//  api/imagenes.php — Buscar fotos en la web | Ruta Nómada
//  POST {plan_id, q}  →  {ok, fotos: [...]}
//
//  QUÉ HACE
//  Pregunta a Pexels por fotos que encajen con lo que se escribió en
//  la ventana «Cambiar foto» y devuelve sólo lo que la rejilla
//  necesita para pintarse.
//
//  POR QUÉ ES UN INTERMEDIARIO Y NO UNA LLAMADA DESDE EL NAVEGADOR
//  Porque la clave de Pexels no puede salir al navegador: quien abra
//  el inspector la vería y podría gastarse la cuota de la cuenta. El
//  mismo criterio que se aplicó con Gemini en api/plan_ai.php.
//
//  POR QUÉ PEXELS Y NO LAS FOTOS DE GOOGLE PLACES
//  Porque sus URLs no caducan, y esta URL se guarda en
//  planes.portada_url para pintarla meses después. El razonamiento
//  completo está en Reportes_md/PLAN_cambiar_foto.md
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);

// Cambiar la portada es editar el plan, así que se exige lo mismo que
// para guardarla. Además evita que una cuenta cualquiera use nuestra
// cuota de Pexels como buscador de imágenes gratis.
planAccess((int)($in['plan_id'] ?? 0), 'editor');

$q = trim((string)($in['q'] ?? ''));
if ($q === '')            apiFail('Escribe qué foto buscas.');
if (mb_strlen($q) > 80)   apiFail('La búsqueda es demasiado larga.');

require_once __DIR__ . '/../includes/pexels_lib.php';

if (pexelsConfig() === null) {
    // Le pasará a quien clone el repositorio y no haya creado su
    // archivo de configuración. Se le dice qué hacer, no sólo que falla.
    apiFail('Falta includes/pexels_config.php. Copia pexels_config.sample.php y pon tu clave de Pexels.', 500);
}

$fotos = pexelsBuscar($q, 12, 12);
if ($fotos === null) {
    apiFail('El buscador de fotos no respondió. Inténtalo de nuevo.', 502);
}

apiJson(['ok' => true, 'fotos' => $fotos, 'total' => count($fotos)]);
