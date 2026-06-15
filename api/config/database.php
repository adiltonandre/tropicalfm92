<?php
// ══════════════════════════════════════════════
//  TROPICAL FM — CONFIG BANCO DE DADOS
//  ⚠️ EDITE APENAS ESTE ARQUIVO NA HOSPEDAGEM
// ══════════════════════════════════════════════

// ── Dados do banco — altere na hospedagem ────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'tropicalfm');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── Segurança — TROQUE em produção! ──────────
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'TropicalFM_2025_SecretKey_X9z#mK');
define('APP_VERSION', '1.0.0');

// ── CORS — aceita qualquer origem em dev ─────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

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
                http_response_code(503);
                header('Content-Type: application/json');
                echo json_encode(['success'=>false,'error'=>'db_error','message'=>'Banco indisponível']);
                exit;
            }
        }
        return self::$conn;
    }
}
