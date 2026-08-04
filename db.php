<?php
// ============================================================
//   db.php — Conexión a la Base de Datos | Ruta Nómada
// ============================================================

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