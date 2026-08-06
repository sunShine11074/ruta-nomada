<?php
// ============================================================
//  includes/mail_config.sample.php — Plantilla | Ruta Nómada
//
//  CÓMO USARLO
//  1. Copia este archivo como  includes/mail_config.php
//  2. Rellena los datos de tu cuenta de correo.
//
//  mail_config.php está en .gitignore y NUNCA se sube al repo.
//  Este .sample sí se versiona, para que cada quien sepa qué crear.
//
//  PARA QUÉ SIRVE
//  Sólo para dos cosas:
//    · forgot-password.php  → correo de recuperación de contraseña
//    · invitar compañeros a un plan de viaje
//  Si no lo configuras, el resto de la aplicación funciona igual;
//  esas dos funciones avisan de que el correo no está configurado.
//
//  CÓMO SACAR UNA CONTRASEÑA DE APLICACIÓN DE GMAIL
//  1. La cuenta necesita verificación en dos pasos activada:
//     myaccount.google.com → Seguridad → Verificación en 2 pasos
//  2. myaccount.google.com/apppasswords → crea una para "Correo"
//  3. Google te da 16 letras. Ésa es la contraseña de aquí,
//     NO la de tu cuenta de Google.
//
//  ⚠ NUNCA compartas este archivo ya relleno, ni lo mandes en un .zip.
//  Una contraseña de aplicación permite enviar correo EN TU NOMBRE
//  desde cualquier parte del mundo. Si se te escapa, revócala en la
//  misma página de apppasswords y crea otra.
//
//  Si no usas Gmail, cambia host y port por los de tu proveedor.
// ============================================================

return [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,                       // 587 = STARTTLS
    'username'  => 'tucorreo@gmail.com',
    'password'  => 'PON_AQUI_TU_APP_PASSWORD', // las 16 letras, sin espacios
    'from_addr' => 'tucorreo@gmail.com',
    'from_name' => 'Ruta Nómada',

    // Dirección base para los enlaces de los correos (invitaciones y
    // recuperación de contraseña). Déjalo vacío y se deduce solo de la
    // petición, que es lo correcto en XAMPP se llame como se llame tu
    // carpeta. Sólo hace falta ponerlo si el sitio vive en un dominio.
    'base_url'  => '',
];
