<?php
// ══════════════════════════════════════════
//  DIAGNÓSTICO — delete após usar!
//  Acesse: localhost/tropicalfm/api/diag.php
// ══════════════════════════════════════════
header('Content-Type: text/html; charset=utf-8');

function ok($msg)  { echo "<div style='color:green;font-weight:bold'>✅ $msg</div>"; }
function err($msg) { echo "<div style='color:red;font-weight:bold'>❌ $msg</div>"; }
function warn($msg){ echo "<div style='color:orange;font-weight:bold'>⚠️ $msg</div>"; }
function info($msg){ echo "<div style='color:#333;margin:4px 0'>ℹ️ $msg</div>"; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Diagnóstico — Tropical FM API</title>
<style>
  body { font-family: monospace; padding: 24px; background: #f5f5f5; }
  h2   { color: #0D1E6B; border-bottom: 2px solid #E8231A; padding-bottom: 8px; }
  .box { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
  code { background: #eee; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
  .fix { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; margin-top: 8px; font-size: 13px; }
</style>
</head>
<body>
<h2>📡 Diagnóstico da API — Tropical FM</h2>

<div class="box">
<h3>1. PHP</h3>
<?php
$phpVer = PHP_VERSION;
if (version_compare($phpVer, '8.0.0', '>=')) {
    ok("PHP $phpVer (OK — requer 8.0+)");
} else {
    err("PHP $phpVer — requer 8.0 ou superior!");
}
info("Caminho: " . PHP_BINARY);
?>
</div>

<div class="box">
<h3>2. mod_rewrite</h3>
<?php
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    if (in_array('mod_rewrite', $mods)) {
        ok("mod_rewrite está ATIVO");
    } else {
        err("mod_rewrite NÃO está ativo!");
        echo '<div class="fix">
            <strong>Como ativar no XAMPP:</strong><br>
            1. Abra <code>C:\xampp\apache\conf\httpd.conf</code><br>
            2. Procure a linha: <code>#LoadModule rewrite_module modules/mod_rewrite.so</code><br>
            3. Remova o <code>#</code> do início<br>
            4. Procure <code>AllowOverride None</code> dentro do bloco <code>&lt;Directory "C:/xampp/htdocs"&gt;</code><br>
            5. Troque por <code>AllowOverride All</code><br>
            6. Reinicie o Apache no XAMPP Control Panel
        </div>';
    }
} else {
    warn("Não foi possível verificar módulos Apache (função apache_get_modules não disponível).");
    info("Isso é normal em algumas configurações. Tente acessar /api/health mesmo assim.");
}
?>
</div>

<div class="box">
<h3>3. Conexão MySQL</h3>
<?php
$dbOk = false;
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    ok("MySQL conectou com usuário 'root' (sem senha)");
    $dbOk = true;

    // Verifica se banco existe
    $dbs = $pdo->query("SHOW DATABASES LIKE 'tropicalfm'")->fetchAll();
    if ($dbs) {
        ok("Banco 'tropicalfm' existe");
        // Verifica tabelas
        $pdo->exec("USE tropicalfm");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) >= 10) {
            ok("Tabelas criadas: " . implode(', ', $tables));
        } elseif (count($tables) > 0) {
            warn("Apenas " . count($tables) . " tabela(s) encontrada(s). Execute o schema.sql completo.");
            info("Tabelas: " . implode(', ', $tables));
        } else {
            err("Banco existe mas está VAZIO — execute api/schema.sql no phpMyAdmin");
            echo '<div class="fix">
                1. Abra <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a><br>
                2. Clique no banco <code>tropicalfm</code><br>
                3. Aba <strong>SQL</strong><br>
                4. Cole o conteúdo de <code>api/schema.sql</code> e clique em Executar
            </div>';
        }
    } else {
        err("Banco 'tropicalfm' NÃO existe!");
        echo '<div class="fix">
            1. Abra <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a><br>
            2. Clique em <strong>Novo</strong> no lado esquerdo<br>
            3. Nome do banco: <code>tropicalfm</code><br>
            4. Cotejamento: <code>utf8mb4_unicode_ci</code><br>
            5. Clique em <strong>Criar</strong>
        </div>';
    }
} catch (PDOException $e) {
    err("MySQL: " . $e->getMessage());
    echo '<div class="fix">
        Verifique se o MySQL está iniciado no XAMPP Control Panel (botão <strong>Start</strong> ao lado de MySQL).
    </div>';
}
?>
</div>

<div class="box">
<h3>4. Arquivos da API</h3>
<?php
$files = [
    __DIR__ . '/index.php'           => 'Roteador principal',
    __DIR__ . '/config/database.php' => 'Config do banco',
    __DIR__ . '/config/response.php' => 'Classe Response',
    __DIR__ . '/config/validator.php'=> 'Classe Validator',
    __DIR__ . '/middleware/auth.php' => 'Middleware JWT',
    __DIR__ . '/schema.sql'          => 'Schema SQL',
];
foreach ($files as $path => $label) {
    if (file_exists($path)) {
        ok("$label <code>" . basename($path) . "</code>");
    } else {
        err("$label NÃO encontrado: <code>$path</code>");
    }
}
?>
</div>

<div class="box">
<h3>5. REQUEST_URI (diagnóstico de roteamento)</h3>
<?php
$uri = $_SERVER['REQUEST_URI'];
info("REQUEST_URI: <code>$uri</code>");
$clean = preg_replace('#^.*?/api#', '', $uri);
$clean = trim($clean, '/');
info("Após limpeza: <code>" . ($clean ?: '(vazio — rota raiz)') . "</code>");
info("SERVER_NAME: <code>" . $_SERVER['SERVER_NAME'] . "</code>");
info("DOCUMENT_ROOT: <code>" . $_SERVER['DOCUMENT_ROOT'] . "</code>");
?>
</div>

<div class="box">
<h3>6. Teste rápido de rotas</h3>
<?php
$base = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$routes = [
    '/api/health'     => 'Health check',
    '/api/noticias'   => 'Listar notícias (público)',
    '/api/programas'  => 'Listar programas (público)',
    '/api/slides'     => 'Listar slides (público)',
];
foreach ($routes as $path => $label) {
    $url = $base . $path;
    echo "<div style='margin:4px 0'>🔗 <a href='$url' target='_blank'><code>$url</code></a> — $label</div>";
}
echo "<br><div style='font-size:13px;color:#666'>Clique nos links acima para testar cada endpoint.</div>";
?>
</div>

<div class="box" style="border-color:#E8231A">
<strong style="color:#E8231A">⚠️ Delete este arquivo após o diagnóstico!</strong><br>
<code>C:\xampp\htdocs\tropicalfm\api\diag.php</code>
</div>

</body>
</html>
