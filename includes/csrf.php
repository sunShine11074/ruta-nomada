<?php
// ============================================================
//  includes/csrf.php — Un token por sesión | Ruta Nómada
//
//  QUÉ EVITA
//  Que otra página consiga que tu navegador envíe un formulario
//  nuestro sin que tú lo pidas. El navegador manda tu cookie de sesión
//  con cualquier petición a este dominio, venga de donde venga; el
//  token no viaja en la cookie, así que una página ajena no puede
//  adivinarlo ni leerlo.
//
//  POR QUÉ ESTÁ AQUÍ Y NO EN plan_auth.php
//  El token nació en plan_auth.php para la API del planificador, que
//  responde JSON. Los formularios de sesión son HTML y necesitan otra
//  cosa: un campo oculto y un aviso legible en la página. Se separa la
//  generación del token —que es una— de las dos formas de comprobarlo:
//
//    · csrfValido()  → devuelve true/false; lo usan las páginas HTML
//    · csrfCheck()   → corta con JSON 403; sigue en plan_auth.php
//
//  Los dos leen la MISMA clave de sesión, así que una pestaña abierta
//  antes de este cambio sigue funcionando sin volver a iniciar sesión.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('csrfToken')) {
    // El token de la sesión. Se crea la primera vez que se pide.
    function csrfToken(): string
    {
        if (empty($_SESSION['csrf_plan'])) {
            $_SESSION['csrf_plan'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_plan'];
    }
}

if (!function_exists('csrfCampo')) {
    // El campo oculto que va dentro de cada <form method="POST">.
    function csrfCampo(): string
    {
        return '<input type="hidden" name="csrf" value="'
            . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrfValido')) {
    // ¿Trae la petición el token de esta sesión?
    //
    // hash_equals y no === : compara en tiempo constante, sin delatar
    // cuántos caracteres acertó quien lo intente a ciegas.
    function csrfValido(): bool
    {
        $tok = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['csrf'] ?? '');
        if (empty($_SESSION['csrf_plan']) || !is_string($tok) || $tok === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_plan'], $tok);
    }
}
