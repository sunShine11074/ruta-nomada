<?php
// ============================================================
//  api/plan_miembros.php — Compañeros del viaje | Ruta Nómada
//
//  POST {plan_id, action:'quitar', usuario_id} → {ok, miembros:[...]}
//
//  Sólo el propietario, que es quien ve la pantalla «Gestiona
//  compañeros de viaje».
//
//  NO HAY ACCIÓN DE LISTAR, y es a propósito: planFullJson() ya manda
//  la lista de miembros en el arranque de plan.php, así que pedirla
//  otra vez sería una consulta de más para lo mismo. Quitar sí devuelve
//  la lista ya actualizada, para no obligar a recargar la página.
//
//  TAMPOCO HAY CAMBIO DE ROL: los frames no lo piden. Todo el que
//  entra por una invitación es 'editor'.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'propietario');
$planId = (int)$in['plan_id'];

if (($in['action'] ?? '') !== 'quitar') apiFail('Acción no reconocida.');

$uid = (int)($in['usuario_id'] ?? 0);
if ($uid <= 0) apiFail('No se dijo a quién quitar.');

$db = getDB();

$stmt = $db->prepare('SELECT rol FROM plan_miembros WHERE plan_id = ? AND usuario_id = ? LIMIT 1');
$stmt->execute([$planId, $uid]);
$fila = $stmt->fetch();
if (!$fila) apiFail('Esa persona ya no está en el viaje.');

// Ni a sí mismo ni a ningún otro propietario. El viaje se quedaría sin
// dueño, y planes.usuario_id y sp_borrar_plan cuentan con que exista.
if ($fila['rol'] === 'propietario') {
    apiFail('No puedes quitar a quien creó el viaje.');
}

// ── Qué se lleva por delante, y qué NO ───────────────────────
//
// plan_item_gasto y plan_item_reacciones apuntan a `usuarios`, no a
// `plan_miembros`: al borrar la membresía no salta ninguna cascada y
// sus filas se quedarían sueltas. Hay que decidir a mano.
//
//   · SE BORRAN sus reacciones. Un voto de quien ya no está en el
//     viaje no significa nada, y el contador de emojis quedaría mal.
//
//   · SE CONSERVA su reparto de gastos. El dinero ya gastado es un
//     hecho: borrarlo reescribiría en silencio el saldo de todos los
//     demás. La dona lo etiqueta como de alguien que ya no está.
$db->beginTransaction();
try {
    $db->prepare(
        'DELETE r FROM plan_item_reacciones r
           JOIN plan_items i ON i.id = r.item_id
          WHERE i.plan_id = ? AND r.usuario_id = ?'
    )->execute([$planId, $uid]);

    $db->prepare('DELETE FROM plan_miembros WHERE plan_id = ? AND usuario_id = ?')
       ->execute([$planId, $uid]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('plan_miembros quitar: ' . $e->getMessage());
    apiFail('No se pudo quitar a esa persona. Inténtalo de nuevo.', 500);
}

// Misma consulta y mismo orden que planFullJson(), para que el cliente
// reciba exactamente la forma que ya sabe leer.
$stmt = $db->prepare(
    'SELECT m.usuario_id, m.rol, u.nombre, u.apellidos, u.foto_perfil
       FROM plan_miembros m JOIN usuarios u ON u.id = m.usuario_id
      WHERE m.plan_id = ? ORDER BY FIELD(m.rol,"propietario","editor","lector"), m.joined_at'
);
$stmt->execute([$planId]);

apiJson(['ok' => true, 'miembros' => $stmt->fetchAll()]);
