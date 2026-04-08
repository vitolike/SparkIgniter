<?php
// app/core/Database.php (MySQL + PostgreSQL)
#[\AllowDynamicProperties]
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $driver = strtolower(Env::get('DB_DRIVER', 'pgsql'));
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', $driver === 'mysql' ? '3306' : '5432');
            $db   = Env::get('DB_NAME', 'postgres');
            $user = Env::get('DB_USER', 'postgres');
            $pass = Env::get('DB_PASS', '');

            if ($driver === 'mysql') {
                $charset = 'utf8mb4';
                $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
                $opt = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            } else { // pgsql (default)
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                $opt = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
            }

            try {
                self::$instance = new PDO($dsn, $user, $pass, $opt);
            } catch (Throwable $e) {
                log_message('ERROR', 'DB connect failed: ' . $e->getMessage());
                if (Env::get('APP_ENV') === 'dev') {
                    http_response_code(500);
                    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
                }
                throw $e;
            }
        }
        return self::$instance;
    }
}