<?php
// ══════════════════════════════════════════════
//  TROPICAL FM — CONFIG BANCO DE DADOS
//  Edite apenas este arquivo na hospedagem
// ══════════════════════════════════════════════
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')     ?: 'tropicalfm');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('DB_CHARSET',  'utf8mb4');
define('JWT_SECRET',  getenv('JWT_SECRET')  ?: 'troca_esta_chave_em_producao_2025');
define('ADMIN_PIN',   getenv('ADMIN_PIN')   ?: '1234');
define('APP_VERSION', '1.0.0');

class DB {
    private static ?PDO $conn = null;

    public static function get(): PDO {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
                self::$conn = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                Response::error('database_unavailable', 'Não foi possível conectar ao banco de dados.', 503);
            }
        }
        return self::$conn;
    }
}
