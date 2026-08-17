<?php
// ============================================================
//  api/plan_tips.php — «Saber antes de ir» | Ruta Nómada
//  POST {plan_id, categoria, ciudad?, wiki?, nota?}
//       → {ok, consejos:[4], fuente:'ia'|'plantilla'}
//
//  CUATRO CONSEJOS PRÁCTICOS para la ficha del sitio, con una cascada
//  de dos niveles calcada de la del «Acerca de» (js/plan_logic.js:2306):
//    1. Gemini, si hay clave y el interruptor está encendido
//    2. Plantilla determinista, que SIEMPRE produce algo
//
//  ══ LO QUE NO SE MANDA A GEMINI, Y POR QUÉ ══════════════════
//
//  Este endpoint NO recibe ni reenvía nada de Google Places: ni el
//  nombre del negocio, ni reseñas, ni valoración, ni dirección, ni
//  horarios, ni nivel de precio.
//
//  El motivo no es de estilo. Son dos reglas que, juntas, lo prohíben:
//
//    · Los términos de Google Maps Platform prohíben usar su contenido
//      para «entrenar, probar, validar o afinar» modelos de IA.
//    · La capa GRATUITA de Gemini entrena con los prompts que recibe.
//
//  O sea que mandarle una reseña de Google a la clave gratuita es
//  entregarle contenido de Maps para entrenamiento. Por eso los
//  consejos salen del TIPO de sitio y del destino, y no del negocio
//  concreto: se pierde puntería —el frame enseñaba consejos sobre un
//  restaurante concreto y esto da consejos sobre comer en esa ciudad—
//  pero es lo que se puede hacer sin pisar los términos.
//
//  Si algún día Gemini pasa a capa de pago (donde los prompts NO se
//  usan para entrenar), aquí se pueden empezar a mandar las 5 reseñas
//  que el cliente YA tiene y los consejos pasan a ser del sitio.
//
//  Lo único que se admite del cliente:
//    · categoria  — taxonomía NUESTRA (hacer/rest/hotel/custom)
//    · ciudad     — un topónimo. Es un hecho geográfico, no un campo
//                   propio de Places: no es una reseña, ni un horario,
//                   ni una valoración. Es el único dato cuyo origen se
//                   remonta al autocompletado de Google, y se manda
//                   sólo porque sin él los consejos no valdrían nada.
//                   Con PLAN_TIPS_IA apagado no se manda ni eso.
//    · wiki       — extracto de Wikipedia, CC BY-SA (api/lugar_wiki.php)
//    · nota       — lo que escribió el propio usuario
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/ai_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
planAccess((int)($in['plan_id'] ?? 0), 'lector');

const TIPS_N = 4;

$cats = [
    'rest'   => ['un restaurante',        'comer fuera'],
    'hotel'  => ['un alojamiento',        'alojarse'],
    'hacer'  => ['un sitio que visitar',  'visitar lugares'],
    'custom' => ['un lugar del viaje',    'moverse'],
];
$cat    = isset($cats[$in['categoria'] ?? '']) ? $in['categoria'] : 'custom';
$ciudad = aiTexto((string)($in['ciudad'] ?? ''), 80);
$wiki   = aiTexto((string)($in['wiki'] ?? ''), 900);
$nota   = aiTexto((string)($in['nota'] ?? ''), 300);

// ── Nivel 2: la plantilla ────────────────────────────────────
// Va PRIMERO en el archivo porque es el piso: si algo falla más
// abajo, esto es lo que se devuelve. No inventa nada, y por eso los
// consejos son de sentido común y no del negocio concreto.
//
// La ciudad NO se pega al final de una frase ya escrita: cada tipo
// tiene su propia frase con hueco, porque «cierran un día entre semana
// en Ensenada» se lee mal y «en Ensenada, muchos sitios cierran un día
// entre semana» se lee bien.
function tipsPlantilla(string $cat, string $ciudad): array
{
    $c = $ciudad !== '';
    $base = [
        'rest' => [
            $c ? "En {$ciudad}, muchos restaurantes cierran un día entre semana: comprueba el horario antes de salir."
               : 'Comprueba el horario antes de salir: muchos sitios cierran un día entre semana.',
            'Las horas punta van de 14:00 a 16:00 y de 21:00 en adelante; fuera de ahí esperarás menos.',
            'Pregunta si aceptan tarjeta. En los sitios pequeños el efectivo sigue siendo lo seguro.',
            'Si sois más de cuatro, llama antes: no todos los locales admiten grupos sin reserva.',
        ],
        'hotel' => [
            $c ? "Confirma la hora de entrada y de salida; en {$ciudad} lo habitual son las 15:00 y las 12:00."
               : 'Confirma la hora de entrada y de salida; lo habitual son las 15:00 y las 12:00.',
            'Pregunta si el aparcamiento está incluido o se paga aparte.',
            'Guarda la dirección sin conexión por si llegas de noche y sin datos.',
            'Avisa si vas a llegar tarde: los alojamientos pequeños cierran recepción.',
        ],
        'hacer' => [
            $c ? "Mira el horario del día que vayas: en {$ciudad}, como en casi todas partes, el lunes es el cierre más habitual."
               : 'Mira el horario del día que vayas: el lunes es el cierre más habitual.',
            'Ve temprano si quieres evitar colas y hacer fotos sin gente.',
            'Comprueba si hay que comprar la entrada por internet antes de ir.',
            'Calcula tiempo de sobra: casi todo se disfruta más sin prisa.',
        ],
        'custom' => [
            $c ? "Confirma el horario antes de ir: en {$ciudad} puede cambiar según la temporada."
               : 'Confirma el horario antes de ir: puede cambiar según la temporada.',
            'Ten a mano la dirección sin conexión por si te quedas sin datos.',
            'Lleva algo de efectivo: no en todos los sitios se puede pagar con tarjeta.',
            'Deja margen entre una parada y la siguiente para no ir con prisa.',
        ],
    ];
    return array_slice($base[$cat] ?? $base['custom'], 0, TIPS_N);
}

$plantilla = fn() => apiJson(['ok' => true, 'consejos' => tipsPlantilla($cat, $ciudad), 'fuente' => 'plantilla']);

// ── Nivel 1: Gemini ──────────────────────────────────────────
// El interruptor vive en plan.php, como PLAN_ACERCA_GOOGLE. Apagado,
// esta sección funciona entera con la plantilla y NO SALE NADA hacia
// Gemini: es la configuración sin exposición ninguna.
if (empty($in['ia'])) $plantilla();

[$queEs, $actividad] = $cats[$cat];

// ⚠ EL PROMPT NO PUEDE SUPONER UN PAIS, y esto no es prudencia: es un
// fallo que ya paso. Antes empezaba con «Eres un guía de viaje que
// escribe en español de México», pensado como variedad del idioma, y el
// modelo lo leia como DESTINO: en viajes a Atenas y a Madrid salia
// «lleva efectivo en pesos mexicanos».
//
// Se separan las dos cosas a proposito: el IDIOMA es español neutro, y
// el PAIS es el del destino, sea cual sea. Y si no llega ciudad, la
// regla es callarse en vez de rellenar con lo primero que suene.
$sys = "Escribes consejos de viaje en español neutro, claro y sin regionalismos.\n"
     . "Te dan el TIPO de sitio y, si se sabe, la ciudad. Nunca el nombre del negocio.\n"
     . "Escribe EXACTAMENTE " . TIPS_N . " consejos prácticos para alguien que va a {$actividad} ahí.\n"
     . "REGLAS:\n"
     . "- Uno por línea, empezando cada línea con «- ». Nada más: ni títulos, ni numeración.\n"
     . "- Máximo 20 palabras cada uno.\n"
     . "- Consejos ÚTILES y concretos: horarios, dinero, reservas, cómo llegar, qué llevar.\n"
     . "- EL DESTINO PUEDE ESTAR EN CUALQUIER PAÍS DEL MUNDO. Nunca supongas México\n"
     . "  ni pesos mexicanos: la moneda, las propinas y los horarios son los del país\n"
     . "  de la ciudad que te den. Si te dan Atenas, son euros; si te dan Tokio, yenes.\n"
     . "- SI NO TE DAN CIUDAD, no menciones moneda, propinas ni costumbres de ningún\n"
     . "  país: da consejos que valgan en cualquier sitio.\n"
     . "- NO te inventes datos del sitio: no sabes su nombre, ni su carta, ni sus precios.\n"
     . "- Trata al lector de tú.";

$partes = ["Tipo de sitio: {$queEs}."];
if ($ciudad !== '') $partes[] = "Ciudad: {$ciudad}.";
if ($wiki !== '')   $partes[] = "Contexto enciclopédico del lugar (Wikipedia): {$wiki}";
if ($nota !== '')   $partes[] = "Nota que escribió el propio viajero: {$nota}";

$r = aiGenerar($sys, [['role' => 'user', 'parts' => [['text' => implode("\n", $partes)]]]], 400);
if (empty($r['ok'])) {
    error_log('plan_tips: ' . ($r['razon'] ?? '?') . ' ' . ($r['pista'] ?? ''));
    $plantilla();
}

// Una línea por consejo. Se limpia con la misma función que el resto
// del proyecto y se descarta cualquier cosa que no tenga forma de
// consejo: si Gemini devuelve un párrafo, se cae a la plantilla en vez
// de enseñar un churro.
$lineas = [];
foreach (preg_split('/\R/', aiSanitizar($r['texto'] ?? '')) as $l) {
    $l = trim(preg_replace('/^\s*[-*•]\s*|^\s*\d+[.)]\s*/u', '', $l));
    if ($l === '' || mb_strlen($l) < 12 || mb_strlen($l) > 200) continue;
    $lineas[] = $l;
    if (count($lineas) >= TIPS_N) break;
}
if (count($lineas) < TIPS_N) $plantilla();

apiJson(['ok' => true, 'consejos' => $lineas, 'fuente' => 'ia']);
