<?php
// ============================================================
//   db.php — Conexión a la Base de Datos | Ruta Nómada
// ============================================================

// ── La hora ─────────────────────────────────────────────────
// Va aquí porque db.php es el único archivo que incluyen TODAS las
// páginas y todos los endpoints de api/, así que es el único sitio
// donde ponerlo una vez sirve para todo.
//
// EL PROBLEMA QUE ARREGLA
// PHP usaba el valor de fábrica de XAMPP —Europe/Berlin— y MySQL su
// hora del sistema. El 13/08/2026 se midieron las dos en el mismo
// instante y salieron con NUEVE HORAS y un DÍA de diferencia:
//     PHP    2026-08-14 04:19:14
//     MySQL  2026-08-13 19:19:14
// No es un detalle: includes/planes_lib.php calcula $hoy con date()
// para decidir si un viaje está «Por suceder», «Sucediendo» o
// «Expirado», así que a partir de media tarde un viaje que terminaba
// hoy ya aparecía expirado. Y api/plan_gastos.php guardaba con la
// fecha del día siguiente los gastos apuntados de noche.
//
// POR QUÉ HERMOSILLO Y NO CIUDAD DE MÉXICO
// Porque es lo que marca este ordenador: Windows está en «(UTC-07:00)
// Arizona», sin horario de verano. América/Hermosillo es la zona de
// Sonora y tiene exactamente esas reglas —UTC-7 todo el año—, así que
// la aplicación coincide con el reloj que se ve en la pantalla y, de
// paso, con la hora bajo la que se escribieron los datos que ya hay.
//
// CONFIRMADO el 13/08/2026: todo el equipo está en el mismo estado, así
// que esta constante vale para las cuatro máquinas y no hace falta que
// sea configurable por persona. No la reabras sin ese dato.
//
// Si algún día el equipo se reparte o esto acaba en un servidor de otro
// país, es lo único que hay que tocar: el desfase de MySQL se calcula
// solo a partir de aquí. La mayor parte de México es
// 'America/Mexico_City' (UTC-6).
const RN_ZONA_HORARIA = 'America/Hermosillo';
date_default_timezone_set(RN_ZONA_HORARIA);

if (!function_exists('getDB')) {
    function getDB(): PDO
    {
        static $pdo;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        // Credenciales por variables de entorno, con valores por defecto para desarrollo local
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('DB_NAME') ?: 'ruta_nomada';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';

        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            // Y la misma hora del lado de MySQL, que hasta ahora iba con
            // la del sistema. El desfase NO va escrito a mano: se saca de
            // RN_ZONA_HORARIA, así que las dos mitades no pueden separarse
            // nunca — que es justo el fallo que se está arreglando. Si
            // algún día se pone una zona con horario de verano, esto lo
            // sigue solo.
            //
            // Se fija por conexión a propósito, para que NOW() y las
            // columnas TIMESTAMP sigan cuadrando con date() aunque la base
            // acabe viviendo en un servidor de otro país.
            $desfase = (new DateTime('now', new DateTimeZone(RN_ZONA_HORARIA)))->format('P');
            $pdo->exec("SET time_zone = '{$desfase}'");
            return $pdo;
        } catch (PDOException $e) {
            // No exponemos el detalle interno de MySQL al usuario final
            error_log('Error de conexión a la BD: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }
}

if (!function_exists('checkDBConnection')) {
    function checkDBConnection(): array
    {
        try {
            getDB();
            return ['ok' => true];
        } catch (RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}