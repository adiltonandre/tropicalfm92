<?php
/**
 * TROPICAL FM — ATUALIZADOR AUTOMÁTICO
 * Baixa os arquivos mais recentes do GitHub
 * Acesse: localhost/tropicalfm/update.php
 * DELETE após usar!
 */

$REPO  = 'adiltonandre/tropicalfm92';
$BASE  = "https://raw.githubusercontent.com/{$REPO}/main";
$DIR   = __DIR__ . '/';

// Arquivos para baixar
$files = [
    'admin.html',
    'index.html',
    'index_modelo5.html',
    'index_modelo6.html',
    'escolha.html',
    'api/index.php',
    'api/.htaccess',
    'api/install.php',
    'api/config/database.php',
    'api/config/response.php',
    'api/config/validator.php',
    'api/middleware/auth.php',
    'api/controllers/AuthController.php',
    'api/controllers/EmpresaController.php',
    'api/controllers/NoticiaController.php',
    'api/controllers/ProgramaController.php',
    'api/controllers/SlideController.php',
    'api/controllers/TopMusicaController.php',
    'api/controllers/PodcastController.php',
    'api/controllers/EnqueteController.php',
    'api/controllers/PublicidadeController.php',
    'api/controllers/ConfigController.php',
    'api/controllers/UploadController.php',
];

$results = [];
$ok = 0; $fail = 0;

if (isset($_GET['run'])) {
    foreach ($files as $file) {
        $url     = $BASE . '/' . $file;
        $dest    = $DIR . $file;
        $destDir = dirname($dest);

        // Cria subpastas se necessário
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Baixa o arquivo
        $content = @file_get_contents($url);
        if ($content === false) {
            // Tenta com curl
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT      => 'Mozilla/5.0',
                ]);
                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode !== 200) $content = false;
            }
        }

        if ($content !== false && strlen($content) > 0) {
            // Não sobrescreve database.php se já configurado
            if ($file === 'api/config/database.php' && file_exists($dest)) {
                $existing = file_get_contents($dest);
                if (strpos($existing, 'root') === false || strpos($existing, 'localhost') !== false) {
                    // Tem configuração real, pula
                    $results[] = ['file' => $file, 'status' => 'skip', 'msg' => 'Mantido (configurado)'];
                    continue;
                }
            }
            file_put_contents($dest, $content);
            $results[] = ['file' => $file, 'status' => 'ok', 'msg' => number_format(strlen($content)/1024, 1) . ' KB'];
            $ok++;
        } else {
            $results[] = ['file' => $file, 'status' => 'fail', 'msg' => 'Erro ao baixar'];
            $fail++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Atualizador — Tropical FM</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#F1F5FF;color:#0F172A;padding:24px;min-height:100vh}
.wrap{max-width:680px;margin:0 auto}
h1{font-size:22px;font-weight:700;color:#0D1E6B;margin-bottom:6px;display:flex;align-items:center;gap:10px}
.sub{font-size:13px;color:#64748B;margin-bottom:24px}
.card{background:#fff;border-radius:12px;padding:22px;margin-bottom:16px;border:1px solid #E4EBF8;box-shadow:0 2px 12px rgba(13,30,107,.05)}
.btn{display:inline-block;background:#2550CC;color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;font-size:14px;text-decoration:none;border:none;cursor:pointer;transition:.2s}
.btn:hover{background:#1A3A9E}
.btn-g{background:#0D9E6A}.btn-g:hover{background:#0a7a52}
.btn-r{background:#E8231A}.btn-r:hover{background:#B51912}
.result{margin-top:16px}
.row{display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:6px;font-size:13px;margin-bottom:4px}
.row.ok{background:rgba(13,158,106,.06);border:1px solid rgba(13,158,106,.15)}
.row.fail{background:rgba(232,35,26,.05);border:1px solid rgba(232,35,26,.15)}
.row.skip{background:rgba(217,119,6,.05);border:1px solid rgba(217,119,6,.15)}
.ico{font-size:15px;width:20px;text-align:center}
.fn{font-family:monospace;font-size:12px;flex:1;color:#334155}
.msg{font-size:11px;color:#64748B}
.summary{display:flex;gap:12px;margin-top:16px;font-size:14px;font-weight:700}
.s-ok{color:#0D9E6A}.s-fail{color:#E8231A}.s-skip{color:#D97706}
.steps{counter-reset:step}
.step{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #F1F5FF}
.step:last-child{border:none}
.sn{width:28px;height:28px;border-radius:50%;background:#2550CC;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0}
.st{font-size:13px;color:#334155;line-height:1.6}
.st code{background:#F1F5FF;padding:1px 6px;border-radius:4px;font-family:monospace;font-size:12px}
a{color:#2550CC}
.warn{background:rgba(232,35,26,.05);border:1px solid rgba(232,35,26,.2);border-radius:8px;padding:12px 16px;font-size:13px;color:#B51912;margin-top:14px;display:flex;gap:8px;align-items:flex-start}
</style>
</head>
<body>
<div class="wrap">
  <h1>📡 Atualizador — Tropical FM</h1>
  <p class="sub">Baixa os arquivos mais recentes do GitHub para o seu XAMPP</p>

  <?php if (!isset($_GET['run'])): ?>
  <div class="card">
    <h3 style="font-size:16px;margin-bottom:14px;color:#0D1E6B">O que será atualizado:</h3>
    <div class="steps">
      <div class="step"><div class="sn">1</div><div class="st"><strong>admin.html</strong> — painel administrativo completo</div></div>
      <div class="step"><div class="sn">2</div><div class="st"><strong>index_modelo5.html</strong> e <strong>index_modelo6.html</strong> — sites com integração API</div></div>
      <div class="step"><div class="sn">3</div><div class="st"><strong>api/*.php</strong> — todos os controllers e configs<br><span style="font-size:12px;color:#94A3B8">⚠️ database.php será mantido se já configurado</span></div></div>
      <div class="step"><div class="sn">4</div><div class="st"><strong>escolha.html</strong> — página de apresentação dos modelos</div></div>
    </div>
    <br>
    <a href="?run=1" class="btn btn-g">⬇️ Baixar e atualizar agora</a>
  </div>

  <div class="card">
    <h3 style="font-size:15px;margin-bottom:12px">Links rápidos</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="/tropicalfm/admin.html" class="btn" style="padding:8px 16px;font-size:12px">🔐 Admin</a>
      <a href="/tropicalfm/index.html" class="btn" style="padding:8px 16px;font-size:12px;background:#E8231A">📻 Site</a>
      <a href="/tropicalfm/api/health" class="btn" style="padding:8px 16px;font-size:12px;background:#0D9E6A">✅ API Health</a>
      <a href="/tropicalfm/escolha.html" class="btn" style="padding:8px 16px;font-size:12px;background:#334155">🎨 Modelos</a>
      <a href="/phpmyadmin" class="btn" style="padding:8px 16px;font-size:12px;background:#6B21A8">🗄️ phpMyAdmin</a>
    </div>
  </div>

  <?php else: ?>
  <div class="card">
    <h3 style="font-size:16px;color:#0D1E6B;margin-bottom:14px">Resultado da atualização</h3>
    <div class="result">
      <?php foreach ($results as $r): ?>
      <div class="row <?= $r['status'] ?>">
        <span class="ico"><?= $r['status']==='ok'?'✅':($r['status']==='skip'?'⏭️':'❌') ?></span>
        <span class="fn"><?= htmlspecialchars($r['file']) ?></span>
        <span class="msg"><?= htmlspecialchars($r['msg']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="summary">
      <span class="s-ok">✅ <?= $ok ?> atualizado(s)</span>
      <?php if ($fail): ?><span class="s-fail">❌ <?= $fail ?> falha(s)</span><?php endif; ?>
    </div>

    <?php if ($ok > 0): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid #F1F5FF">
      <h4 style="font-size:14px;margin-bottom:12px;color:#0D1E6B">Próximos passos:</h4>
      <div class="steps">
        <div class="step"><div class="sn">1</div><div class="st">Acesse o <a href="/tropicalfm/admin.html"><strong>Painel Admin</strong></a> → PIN: <code>1234</code></div></div>
        <div class="step"><div class="sn">2</div><div class="st">Menu <strong>Conexão API</strong> → URL: <code>localhost/tropicalfm/api</code> → Salvar → Conectar</div></div>
        <div class="step"><div class="sn">3</div><div class="st">Cadastre conteúdo — notícias, slides, programas — e veja o toast <strong>✅ Salvo no banco</strong></div></div>
        <div class="step"><div class="sn">4</div><div class="st">Acesse o <a href="/tropicalfm/index.html"><strong>site</strong></a> e veja os dados carregando do MySQL</div></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="warn">⚠️ <strong>Delete este arquivo após usar:</strong> <code>C:\xampp\htdocs\tropicalfm\update.php</code></div>
    <br>
    <a href="/tropicalfm/update.php" class="btn">← Voltar</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
