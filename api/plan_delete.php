<?php
// ============================================================
//  api/plan_delete.php — Borrar un plan o salir de él | Ruta Nómada
//  POST {plan_id}
//   →  {ok:true, accion:'borrado'}  si eras el propietario
//   →  {ok:true, accion:'salida'}   si eras editor o lector
//
//  DOS OPERACIONES DISTINTAS BAJO EL MISMO BOTÓN
//  Para el propietario, el botón dice "Eliminar plan" y borra el viaje
//  entero. Para quien fue invitado dice "Salir del plan" y sólo quita
//  su propia fila de plan_miembros: el viaje sigue existiendo para los
//  demás. Un invitado NUNCA puede borrar el viaje de otra persona, así
//  que el rol no se lee del cliente: se resuelve aquí con planAccess().
//
//  QUÉ SE LLEVA POR DELANTE UN BORRADO
//  Las claves foráneas van en cascada desde planes, así que una sola
//  sentencia arrastra: plan_items (y con ellos plan_item_gasto y
//  plan_item_reacciones), plan_gastos, plan_listas (y plan_lista_items),
//  plan_miembros, plan_invitaciones y plan_destinos. ai_uso conserva sus
//  filas con plan_id a NULL, que es lo que se quiere: el contador de
//  gasto del asistente no debe borrarse al borrar un viaje.
//
//  Es irreversible y no hay papelera. Por eso la interfaz obliga a
//  mantener pulsado cinco segundos en vez de un clic suelto.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);

$planId = (int)($in['plan_id'] ?? 0);
// 'lector' como mínimo: cualquier miembro puede llamar. Lo que ocurre
// después depende del rol REAL que devuelva planAccess, no de lo que
// diga el navegador.
$acc = planAccess($planId, 'lector');
$userId = (int)$acc['user_id'];
$db = getDB();

// ── Invitado: sale del plan y no toca nada más ──────────────
if ($acc['rol'] !== 'propietario') {
    $st = $db->prepare('DELETE FROM plan_miembros WHERE plan_id = ? AND usuario_id = ?');
    $st->execute([$planId, $userId]);
    apiJson(['ok' => true, 'accion' => 'salida']);
}

// ── Propietario: borrado completo ───────────────────────────
$db->beginTransaction();
try {
    // viajes_usuario.plan_id es RESTRICT: si alguna fila apunta a este
    // plan, el DELETE fallaría. Hoy nunca ocurre porque destino.php
    // guarda esa fila sin plan_id, pero desvincularlo antes cuesta una
    // sentencia y evita que el borrado empiece a fallar el día que esa
    // columna se empiece a rellenar.
    $db->prepare('UPDATE viajes_usuario SET plan_id = NULL WHERE plan_id = ?')->execute([$planId]);

    $st = $db->prepare('DELETE FROM planes WHERE id = ?');
    $st->execute([$planId]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('[plan_delete] ' . $e->getMessage());
    apiFail('No se pudo eliminar el plan. Inténtalo de nuevo.', 500);
}

apiJson(['ok' => true, 'accion' => 'borrado']);
