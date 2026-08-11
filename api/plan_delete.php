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
// 'lector' como mínimo: cualquier miembro puede llamar. planAccess()
// cubre sesión y CSRF; el rol que decide entre borrar y salir lo
// vuelve a resolver sp_borrar_plan DENTRO de la base con
// fn_rol_en_plan — dos capas, y ninguna se fía del navegador.
$acc = planAccess($planId, 'lector');
$userId = (int)$acc['user_id'];

// La lógica entera vive en sp_borrar_plan (basedatos/rutinas.sql):
// propietario → transacción que desvincula viajes_usuario y borra el
// plan (el trigger trg_plan_borrado deja constancia en planes_borrados
// antes de que la fila desaparezca); editor o lector → sólo quita su
// fila de plan_miembros. Devuelve la acción como result set.
try {
    $st = getDB()->prepare('CALL sp_borrar_plan(?,?)');
    $st->execute([$planId, $userId]);
    $accion = (string)($st->fetch()['accion'] ?? '');
    $st->closeCursor();
    if ($accion === '') throw new RuntimeException('sp_borrar_plan no devolvió acción');
} catch (Throwable $e) {
    error_log('[plan_delete] ' . $e->getMessage());
    apiFail('No se pudo eliminar el plan. Inténtalo de nuevo.', 500);
}

apiJson(['ok' => true, 'accion' => $accion]);
