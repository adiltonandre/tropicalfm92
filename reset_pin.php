<?php
/**
 * TROPICAL FM — RESET DE PIN
 * Acesse: localhost/tropicalfm/reset_pin.php
 * DELETE após usar!
 */

require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/config/response.php';
require_once __DIR__ . '/api/middleware/auth.php';

$msg = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $pin2 = trim($_POST['pin2'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $msg = 'PIN deve ter exatamente 4 dígitos numéricos.';
        $tipo = 'erro';
    } elseif ($pin !== $pin2) {
        $msg = 'Os PINs não coincidem. Digite igual nos dois campos.';
        $tipo = 'erro';
    } else {
        try {
            $db = DB::get();

            // Gera hash do novo PIN
            $hash = Auth::hashPin($pin);

            // Atualiza na tabela configuracoes
            // Verifica se já existe
            $exists = $db->prepare("SELECT chave FROM configuracoes WHERE chave = 'pin_hash'");
            $exists->execute();
            if ($exists->fetch()) {
                $db->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'pin_hash'")
                   ->execute([$hash]);
            } else {
                $db->prepare("INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES ('pin_hash', ?, 'string', 'Hash SHA256 do PIN admin')")
                   ->execute([$hash]);
            }

            // Log
            $db->prepare("INSERT INTO activity_log (acao, detalhes, ip) VALUES (?,?,?)")
               ->execute(['pin_reset', 'PIN resetado via reset_pin.php', $_SERVER['REMOTE_ADDR'] ?? '']);

            $msg = "✅ PIN alterado para <strong>{$pin}</strong> com sucesso! Agora faça login no admin.";
            $tipo = 'ok';
        } catch (Exception $e) {
            $msg = 'Erro ao conectar ao banco: ' . $e->getMessage();
            $tipo = 'erro';
        }
    }
}

// Testa conexão atual
$dbOk = false;
$pinAtual = '';
try {
    $db = DB::get();
    $dbOk = true;
    $row = $db->query("SELECT valor FROM configuracoes WHERE chave = 'pin_hash'")->fetch();
    $pinAtual = $row ? ($row['valor'] ? 'Hash salvo no banco' : 'Sem hash — usa PIN padrão do database.php') : 'Sem hash — usa PIN padrão';
} catch (Exception $e) {
    $pinAtual = 'Erro: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset PIN — Tropical FM</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:30px;width:100%;max-width:420px}
h1{font-size:20px;font-weight:700;color:#7dd3fc;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.sub{color:#64748b;font-size:12px;margin-bottom:22px}
.info{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px;margin-bottom:18px;font-size:12px}
.info-row{display:flex;justify-content:space-between;padding:4px 0}
.info-lbl{color:#64748b}
.info-val{color:#4ade80;font-family:monospace}
.info-val.err{color:#f87171}
.fg{margin-bottom:14px}
label{display:block;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:11px 14px;color:#e2e8f0;font-size:16px;font-family:monospace;letter-spacing:4px;text-align:center;outline:none;transition:.2s}
input:focus{border-color:#2550cc;box-shadow:0 0 0 3px rgba(37,80,204,.2)}
.btn{width:100%;padding:13px;background:#2550cc;color:#fff;font-weight:700;font-size:14px;border:none;border-radius:8px;cursor:pointer;margin-top:4px;transition:.2s}
.btn:hover{background:#1a3a9e}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;line-height:1.5}
.alert.ok{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:#4ade80}
.alert.erro{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171}
.warn{background:rgba(251,146,60,.08);border:1px solid rgba(251,146,60,.25);border-radius:8px;padding:10px 14px;font-size:11px;color:#fb923c;margin-top:14px;text-align:center}
a{color:#60a5fa;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <h1>🔑 Reset de PIN</h1>
  <p class="sub">Redefina o PIN de acesso ao painel admin</p>

  <div class="info">
    <div class="info-row">
      <span class="info-lbl">Banco MySQL</span>
      <span class="info-val <?= $dbOk?'':'err' ?>"><?= $dbOk?'✅ Conectado':'❌ Sem conexão' ?></span>
    </div>
    <div class="info-row">
      <span class="info-lbl">PIN atual</span>
      <span class="info-val"><?= htmlspecialchars($pinAtual) ?></span>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert <?= $tipo ?>"><?= $msg ?></div>
  <?php if ($tipo === 'ok'): ?>
  <div style="text-align:center;margin-bottom:14px">
    <a href="/tropicalfm/admin.html" style="background:#059669;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;font-size:13px;display:inline-block">→ Abrir Admin</a>
    &nbsp;
    <a href="/tropicalfm/debug_api.html" style="background:#2550cc;color:#fff;padding:10px 24px;border-radius:8px;font-weight:700;font-size:13px;display:inline-block">→ Debug API</a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <form method="POST">
    <div class="fg">
      <label>Novo PIN (4 dígitos)</label>
      <input type="password" name="pin" maxlength="4" placeholder="••••" pattern="\d{4}" required autofocus
        oninput="this.value=this.value.replace(/\D/g,'')">
    </div>
    <div class="fg">
      <label>Confirmar PIN</label>
      <input type="password" name="pin2" maxlength="4" placeholder="••••" pattern="\d{4}" required
        oninput="this.value=this.value.replace(/\D/g,'')">
    </div>
    <button type="submit" class="btn">🔐 Salvar Novo PIN</button>
  </form>

  <div class="warn">⚠️ Delete este arquivo após usar:<br><code>C:\xampp\htdocs\tropicalfm\reset_pin.php</code></div>
</div>
</body>
</html>
