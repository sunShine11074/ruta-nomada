<?php
// ============================================================
//  includes/destino.php — ¿A dónde mando a alguien que acaba
//  de entrar? | Ruta Nómada
//
//  Cuando alguien abre un enlace de invitación sin haber
//  iniciado sesión, plan_invitacion.php guarda a dónde quería ir
//  en $_SESSION['despues_de_login'] y lo manda a login.php. Al
//  entrar hay que devolverlo allí, no al panel de siempre.
//
//  POR QUÉ ESTO VIVE EN SU PROPIO ARCHIVO
//  Porque login.php lo hacía y register.php no, y el resultado
//  era que quien llegaba por una invitación SIN TENER CUENTA se
//  registraba y aterrizaba en inicio.php sin rastro del viaje al
//  que lo habían invitado. Parecía que la invitación había
//  fallado, y no había fallado.
//
//  La tentación era copiar el bloque de login.php a register.php.
//  No: en este proyecto ya se copió una lista una vez —la de
//  dominios de correo, que vivía en login.php y en register.php—
//  y las dos copias se desincronizaron, con el resultado de que
//  se podían crear cuentas con las que después no se podía
//  entrar. Una sola función y no puede volver a pasar.
//  Ver includes/email_dominio.php.
// ============================================================

// Saca el destino guardado, lo consume y lo devuelve ya validado.
// Siempre devuelve algo seguro a donde ir.
function destinoTrasEntrar(string $porDefecto = 'inicio.php'): string
{
    $destino = $_SESSION['despues_de_login'] ?? '';
    unset($_SESSION['despues_de_login']);

    return destinoInterno((string)$destino) ? (string)$destino : $porDefecto;
}

// ¿Es una ruta interna simple y sin sorpresas?
//
// Lo que se está impidiendo es el «open redirect»: que alguien
// prepare un enlace que, tras iniciar sesión, te deje en su
// propia página imitando a la nuestra. Por eso NO se aceptan
// esquemas (http:, javascript:), ni rutas que empiecen por '/'
// o por '//' —esta última la lee el navegador como un dominio
// ajeno—, ni saltos hacia arriba con '..'.
//
// El guion en [a-z0-9_-] NO es un adorno: sin él se caían en
// silencio reset-password.php y forgot-password.php, que son
// precisamente los dos archivos del proyecto con guion en el
// nombre. Era el fallo que tenía la copia de login.php.
function destinoInterno(string $destino): bool
{
    if ($destino === '') return false;
    if (strpos($destino, '..') !== false) return false;

    return (bool)preg_match('/^[a-z0-9_-]+\.php(\?[a-zA-Z0-9_=&%.-]*)?$/', $destino);
}
