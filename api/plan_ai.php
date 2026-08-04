<?php
// ============================================================
//  api/plan_ai.php — Asistente de IA del plan | Ruta Nómada
//  POST {plan_id, mensaje}  →  {ok:true, respuesta:"..."}
//
//  Proxy a la API de Gemini. La clave vive en includes/ai_config.php
//  (gitignored) y NUNCA sale del servidor, igual que en geo.php.
//
//  El contrato con el front no cambió: js/plan_logic.js sigue
//  esperando {ok:true, respuesta:"..."} y sigue simulando el tecleo.
//  Si aquí algo falla, devolvemos ok:false y el front cae solo a su
//  aiReply() local, así que el chat nunca se queda mudo.
//
//  ⚠ La respuesta NO se pasa por htmlspecialchars() a propósito.
//  richBody() la pinta con React.createElement pasando el texto como
//  hijo, y React escapa eso solo. Escapar aquí además se vería como
//  &amp; y &lt; literales en pantalla. Lo que no se puede hacer nunca
//  es migrar richBody() a innerHTML / dangerouslySetInnerHTML.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';
require_once __DIR__ . '/../includes/ai_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);

$planId = (int)($in['plan_id'] ?? 0);
$acc    = planAccess($planId, 'lector');   // el guardián va ANTES de armar el prompt

// ── "Nuevo chat" ─────────────────────────────────────────────
// El botón limpia la lista en pantalla, pero el hilo también vive aquí
// (la API de Gemini no guarda estado, lo reenviamos nosotros). Sin esto,
// el usuario ve un chat vacío y el modelo sigue recordando lo anterior.
if (!empty($in['reset'])) {
    unset($_SESSION['ai_hist'][$planId]);
    apiJson(['ok' => true, 'reset' => true]);
}

// ── Entrada ──────────────────────────────────────────────────
$msg = (string)($in['mensaje'] ?? '');
if (!mb_check_encoding($msg, 'UTF-8')) apiFail('Mensaje inválido.');
$msg = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $msg));
if ($msg === '') apiFail('Escribe un mensaje.');
// 500 caracteres sobran para "¿qué hago en Guanajuato 3 días?" y cortan de
// raíz tanto los intentos largos de inyección como el inflado de tokens.
if (mb_strlen($msg) > 500) apiFail('El mensaje es demasiado largo (máximo 500 caracteres).');

$destino = aiTexto($acc['plan']['destino'] ?? '', 80) ?: 'tu destino';

// ── Sin clave configurada: modo demostración ─────────────────
// Un compañero que clone el repo sin crear su ai_config.php sigue
// teniendo un chat que responde, en vez de una app rota.
if (aiKey() === '') {
    apiJson(['ok' => true, 'respuesta' => rnAiReply($msg, $destino), 'demo' => true]);
}

// ── Tope de uso (por usuario, en BD) ─────────────────────────
$db  = getDB();
$lim = aiRateLimit($db, (int)$acc['user_id'], $planId);
if (!$lim['ok']) {
    // Se responde como mensaje del asistente para que se lea en el chat.
    apiJson(['ok' => true, 'respuesta' => $lim['motivo'], 'limite' => true]);
}
$usoId = (int)$lim['uso_id'];

// ── Contexto del viaje + historial de la conversación ────────
$boot     = planFullJson($planId, $acc);
$contexto = aiPlanContexto($boot);

// El historial vive en la sesión porque la API de Gemini no guarda
// estado: hay que reenviar la conversación entera en cada petición.
// Sólo los últimos turnos, para no pagar el hilo completo cada vez.
$hist = $_SESSION['ai_hist'][$planId] ?? [];
if (!is_array($hist)) $hist = [];
$hist = array_slice($hist, -8);

$contents = [];
foreach ($hist as $h) {
    // Roles válidos: 'user' y 'model'. 'assistant' NO existe en esta API
    // (es convención de OpenAI) y devuelve 400.
    $rol = ($h['role'] ?? '') === 'model' ? 'model' : 'user';
    $contents[] = ['role' => $rol, 'parts' => [['text' => (string)($h['text'] ?? '')]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $msg]]];

// ── Llamada ──────────────────────────────────────────────────
$r = aiGenerar(aiSystemPrompt($contexto), $contents);

if (!$r['ok']) {
    if ($r['razon'] === 'vacio') {
        // MAX_TOKENS con texto vacío casi siempre significa que el modelo
        // gastó el presupuesto razonando. Se distingue del bloqueo por
        // seguridad porque conviene acotar la pregunta, no reformularla.
        $aviso = (($r['finish'] ?? '') === 'MAX_TOKENS')
            ? 'Se me hizo muy larga la respuesta. Pregúntame algo más acotado, por ejemplo un día del viaje en vez del itinerario completo.'
            : 'No pude responder eso. Intenta preguntarlo de otra forma.';
        apiJson(['ok' => true, 'respuesta' => $aviso, 'vacio' => true]);
    }
    // Genérico a propósito: el código real de Google delata si la clave es
    // inválida o si se acabó la cuota. El front caerá a su respuesta local.
    apiFail('El asistente no está disponible en este momento. Inténtalo en un minuto.', 503);
}

$texto = aiDesmarcarDestino(aiSanitizar($r['texto']), $destino);

// ── Contabilidad y memoria ───────────────────────────────────
aiRegistrarUso($db, $usoId, $r['tokens_in'], $r['tokens_out']);

$hist[] = ['role' => 'user',  'text' => $msg];
$hist[] = ['role' => 'model', 'text' => $texto];
$_SESSION['ai_hist'][$planId] = array_slice($hist, -8);

apiJson(['ok' => true, 'respuesta' => $texto]);


// ── Respaldo sin clave (lo que era la Fase A) ────────────────
function rnAiReply(string $q, string $destino): string
{
    $ql = mb_strtolower($q);
    if (str_contains($ql, 'comer') || str_contains($ql, 'restaurante') || str_contains($ql, 'comida')) {
        return "Estos son los lugares más queridos para comer en {$destino}: los puestos del centro para " .
               "antojitos locales, la zona del malecón para mariscos frescos, y los mercados tradicionales " .
               "para probar la cocina de la región. Abre la pestaña **Explorar → Restaurantes** para verlos " .
               "con calificaciones reales y añadirlos a tu itinerario con un clic.";
    }
    if (str_contains($ql, 'itinerario') || str_contains($ql, 'días') || str_contains($ql, 'dias') || str_contains($ql, 'plan')) {
        return "Para armar un buen itinerario en {$destino} te sugiero: **Día 1** — centro histórico por la " .
               "mañana, comida típica al mediodía y mirador o malecón al atardecer. **Día 2** — la atracción " .
               "natural más famosa de la zona por la mañana y una experiencia local por la tarde. Ve a " .
               "**Explorar** y usa \"Añadir al plan de viaje\" en cada lugar para asignarlo a un día.";
    }
    if (str_contains($ql, 'atraccion') || str_contains($ql, 'atracción') || str_contains($ql, 'hacer') || str_contains($ql, 'visitar')) {
        return "Las atracciones imperdibles de {$destino} las encuentras en **Explorar → Cosas que hacer**, " .
               "ordenadas por calificación de Google. Pasa el cursor por una tarjeta para ubicarla en el mapa " .
               "y usa el botón para añadirla a tu itinerario.";
    }
    if (str_contains($ql, 'presupuesto') || str_contains($ql, 'gasto') || str_contains($ql, 'costo') || str_contains($ql, 'dinero')) {
        return "En la sección **Presupuesto** puedes definir cuánto planeas gastar y registrar cada gasto por " .
               "categoría (alojamiento, comida, actividades, transporte…). La barra verde te muestra cuánto " .
               "llevas del total y el desglose te dice en qué se va el dinero.";
    }
    return "Puedo ayudarte con ideas para tu viaje a {$destino}: pregúntame por lugares para comer, " .
           "atracciones, itinerarios por días o presupuesto. También puedes explorar lugares reales en " .
           "la pestaña **Explorar** y añadirlos a tu plan.\n\n" .
           "**Nota:** todavía no hay una clave de IA configurada en este servidor, así que ésta es una " .
           "respuesta de demostración.";
}
