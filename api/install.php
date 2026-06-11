<?php
/**
 * ══════════════════════════════════════════════════════
 *  TROPICAL FM — INSTALADOR AUTOMÁTICO
 *  Acesse: https://seusite.com.br/api/install.php
 *  IMPORTANTE: delete este arquivo após instalar!
 * ══════════════════════════════════════════════════════
 */

// Senha de proteção do instalador (altere antes de fazer upload)
define('INSTALL_KEY', 'tropical2025');

session_start();

// ── Segurança básica ────────────────────────────────
if ($_GET['key'] ?? '' !== INSTALL_KEY) {
    if ($_POST['key'] ?? '' !== INSTALL_KEY) {
        http_response_code(403);
        die(renderPage('Acesso Negado', '<div class="alert alert-danger">❌ Chave de instalação incorreta.</div>'));
    }
}

$step   = (int)($_POST['step'] ?? $_GET['step'] ?? 0);
$errors = [];
$ok     = [];

// ── Processar formulário ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $name   = trim($_POST['db_name'] ?? '');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = $_POST['db_pass'] ?? '';
    $secret = trim($_POST['jwt_secret'] ?? '');
    $pin    = trim($_POST['admin_pin'] ?? '1234');

    if (!$name) $errors[] = 'Nome do banco é obrigatório.';
    if (!$user) $errors[] = 'Usuário do banco é obrigatório.';
    if (strlen($secret) < 16) $errors[] = 'Chave JWT deve ter ao menos 16 caracteres.';
    if (!preg_match('/^\d{4}$/', $pin)) $errors[] = 'PIN deve ter exatamente 4 dígitos.';

    if (empty($errors)) {
        // Testa conexão
        try {
            $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $ok[] = "✅ Conexão com MySQL OK";

            // Cria banco se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");
            $ok[] = "✅ Banco `{$name}` pronto";

            // Executa schema
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            if ($sql) {
                foreach (explode(';', $sql) as $query) {
                    $q = trim($query);
                    if ($q && !str_starts_with($q, '--') && !str_starts_with($q, '/*')) {
                        try { $pdo->exec($q); } catch (PDOException) {}
                    }
                }
                $ok[] = "✅ Tabelas criadas com sucesso";
            }

            // Grava database.php
            $configContent = <<<PHP
<?php
define('DB_HOST',    '{$host}');
define('DB_NAME',    '{$name}');
define('DB_USER',    '{$user}');
define('DB_PASS',    '{$pass}');
define('DB_CHARSET', 'utf8mb4');
define('JWT_SECRET', '{$secret}');
define('ADMIN_PIN',  '{$pin}');
define('APP_VERSION','1.0.0');

class DB {
    private static ?\PDO \$conn = null;
    public static function get(): \PDO {
        if (self::\$conn === null) {
            try {
                \$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
                self::\$conn = new \PDO(\$dsn, DB_USER, DB_PASS, [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (\PDOException \$e) {
                http_response_code(503);
                die(json_encode(['success'=>false,'error'=>'database_unavailable','message'=>'Banco indisponível.']));
            }
        }
        return self::\$conn;
    }
}
PHP;

            file_put_contents(__DIR__ . '/config/database.php', $configContent);
            $ok[] = "✅ database.php configurado";

            // Salva PIN hash
            $hash = hash_hmac('sha256', $pin, $secret);
            $pdo->prepare("INSERT INTO configuracoes (chave,valor,tipo) VALUES ('pin_hash',?,'string') ON DUPLICATE KEY UPDATE valor=?")->execute([$hash,$hash]);
            $ok[] = "✅ PIN configurado";

            $_SESSION['install_ok'] = true;
            $_SESSION['install_msgs'] = $ok;
            header('Location: ?step=2&key='.INSTALL_KEY);
            exit;

        } catch (PDOException $e) {
            $errors[] = "❌ Erro MySQL: " . $e->getMessage();
        }
    }
}

// ── Renderizar páginas ───────────────────────────────
$content = '';

if ($step === 2 && ($_SESSION['install_ok'] ?? false)) {
    $msgs = $_SESSION['install_msgs'] ?? [];
    unset($_SESSION['install_ok'], $_SESSION['install_msgs']);
    $items = implode('', array_map(fn($m) => "<div style='padding:6px 0;color:#0a7a52;font-weight:600'>$m</div>", $msgs));
    $content = <<<HTML
    <div class="card">
      <h2>🎉 Instalação concluída!</h2>
      <div class="alert alert-success">
        $items
      </div>
      <hr>
      <h3>⚠️ Próximos passos obrigatórios:</h3>
      <ol style="line-height:2.2;color:#334155">
        <li><strong>Delete este arquivo</strong> do servidor: <code>api/install.php</code></li>
        <li>Acesse <a href="../admin.html">admin.html</a> e entre com o PIN configurado</li>
        <li>No painel admin → <strong>Conexão API</strong> → configure a URL e faça login</li>
        <li>Use <strong>"Migrar dados"</strong> para enviar dados offline ao banco</li>
      </ol>
      <a href="../admin.html" class="btn-ir">Ir para o Painel Admin →</a>
    </div>
HTML;

} else {
    $errHtml = $errors ? '<div class="alert alert-danger">'.implode('<br>',$errors).'</div>' : '';
    $secret  = bin2hex(random_bytes(16));
    $content = <<<HTML
    <div class="card">
      <h2>⚙️ Configurar banco de dados</h2>
      <p style="color:#64748b;margin-bottom:20px">Preencha os dados do MySQL da sua hospedagem (encontre no cPanel → Banco de Dados MySQL).</p>
      $errHtml
      <form method="POST">
        <input type="hidden" name="key" value="{$_GET['key']}">
        <input type="hidden" name="step" value="1">
        <div class="fg">
          <label>Host do banco</label>
          <input class="fi" name="db_host" value="localhost" placeholder="localhost">
        </div>
        <div class="row2">
          <div class="fg">
            <label>Nome do banco <span class="req">*</span></label>
            <input class="fi" name="db_name" placeholder="tropicalfm" required>
          </div>
          <div class="fg">
            <label>Usuário MySQL <span class="req">*</span></label>
            <input class="fi" name="db_user" placeholder="usuario_cpanel" required>
          </div>
        </div>
        <div class="fg">
          <label>Senha MySQL</label>
          <input class="fi" name="db_pass" type="password" placeholder="Senha do usuário MySQL">
        </div>
        <div class="fg">
          <label>Chave JWT (gerada automaticamente) <span class="req">*</span></label>
          <input class="fi" name="jwt_secret" value="{$secret}" placeholder="Mínimo 16 caracteres — mude se quiser">
          <div class="field-hint">⚠️ Guarde esta chave. Ela assina os tokens de autenticação.</div>
        </div>
        <div class="fg">
          <label>PIN do Admin (4 dígitos) <span class="req">*</span></label>
          <input class="fi" name="admin_pin" type="password" maxlength="4" value="1234" pattern="\d{4}" placeholder="1234">
          <div class="field-hint">Altere o PIN padrão (1234) por um número de sua escolha.</div>
        </div>
        <button type="submit" class="btn-ir" style="background:#0D9E6A">⚡ Instalar agora</button>
      </form>
    </div>
HTML;
}

echo renderPage('Instalador — Tropical FM', $content);

function renderPage(string $title, string $body): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#F1F5FF;color:#0F172A;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.wrap{max-width:560px;width:100%}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-size:22px;font-weight:700;color:#0D1E6B;margin-top:8px}
.logo small{font-size:13px;color:#94A3B8}
.card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 24px rgba(13,30,107,.08);border:1px solid #E4EBF8}
h2{font-size:18px;font-weight:700;margin-bottom:16px;color:#0F172A}
h3{font-size:15px;font-weight:700;margin:18px 0 10px;color:#334155}
.fg{margin-bottom:14px}
label{display:block;font-size:11px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px}
.req{color:#E8231A}
.fi{width:100%;padding:9px 12px;background:#F8FAFF;border:1px solid #CBD5E1;border-radius:8px;font-size:13px;color:#0F172A;font-family:'Inter',sans-serif;outline:none}
.fi:focus{border-color:#2550CC;box-shadow:0 0 0 3px rgba(37,80,204,.08)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field-hint{font-size:11px;color:#94A3B8;margin-top:5px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;line-height:1.6;margin-bottom:16px}
.alert-danger{background:rgba(232,35,26,.06);border:1px solid rgba(232,35,26,.2);color:#B51912}
.alert-success{background:rgba(13,158,106,.06);border:1px solid rgba(13,158,106,.2);color:#0a7a52}
.btn-ir{display:inline-block;background:#2550CC;color:#fff;padding:11px 24px;border-radius:8px;font-weight:700;font-size:14px;border:none;cursor:pointer;margin-top:16px;text-decoration:none}
.btn-ir:hover{background:#1A3A9E}
hr{border:none;border-top:1px solid #E4EBF8;margin:18px 0}
ol{padding-left:20px}
code{background:#F1F5FF;padding:2px 7px;border-radius:4px;font-size:12px;font-family:monospace}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div style="font-size:32px">📡</div>
    <h1>Tropical FM — Instalador</h1>
    <small>Configure o banco de dados e coloque o site em produção</small>
  </div>
  {$body}
</div>
</body>
</html>
HTML;
}
