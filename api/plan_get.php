<?php
// ============================================================
//  api/plan_get.php — Todo el plan en un JSON | Ruta Nómada
//  GET ?id=N
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

$planId = (int)($_GET['id'] ?? 0);
$acc = planAccess($planId, 'lector');
apiJson(planFullJson($planId, $acc));
