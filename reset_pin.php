<?php
require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/config/response.php';
require_once __DIR__ . '/api/middleware/auth.php';

$msg = ''; $tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin  = preg_replace('/\D/', '', $_POST['pin']  ?? '');
    $pin2 = preg_replace('/\D/', '', $_POST['pin2'] ?? '');

    if (strlen($pin) !== 4) {
        $msg = 'PIN deve ter exatamente 4 dígitos numéricos.'; $tipo = 'erro';
    } elseif ($pin !== $pin2) {
        $msg = 'Os PINs não coincidem.'; $tipo = 'erro';
    } else {
        try {
            $db   = DB::get();
            $hash = Auth::hashPin($pin);

            // Usa ? em vez de named params para evitar HY093
            $check = $db->prepare("SELECT COUNT(*) FROM configuracoes WHERE chave = ?");
            $check->execute(['pin_hash']);
            $exists = (int)$check->fetchColumn();

            if ($exists > 0) {
                $db->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?")
                   ->execute([$hash, 'pin_hash']);
            } else {
                $db->prepare("INSERT INTO configuracoes (chave, valor, tipo) VALUES (?, ?, ?)")
                   ->execute(['pin_hash', $hash, 'string']);
            }

            $db->prepare("INSERT INTO activity_log (acao, ip) VALUES (?, ?)")
               ->execute(['pin_reset_tool', $_SERVER['REMOTE_ADDR'] ?? '']);

            $msg = "PIN <strong>{$pin}</strong> salvo com sucesso!"; $tipo = 'ok';
        } catch (Exception $e) {
            $msg = 'Erro: ' . $e->getMessage(); $tipo = 'erro';
        }
    }
}

$dbOk = false; $pinInfo = 'desconhecido';
try {
    $db = DB::get(); $dbOk = true;
    $r  = $db->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
    $r->execute(['pin_hash']);
    $row = $r->fetch();
    $pinInfo = $row ? ($row['valor'] ? 'Hash salvo no banco' : 'Vazio') : 'Registro não encontrado';
} catch (Exception $e) { $pinInfo = 'Erro: '.$e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset PIN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:30px;width:100%;max-width:400px}
h1{font-size:20px;font-weight:700;color:#7dd3fc;margin-bottom:4px}
.sub{color:#64748b;font-size:12px;margin-bottom:20px}
.info{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px}
.ir{display:flex;justify-content:space-between;padding:4px 0}
.il{color:#64748b}.iv{color:#4ade80;font-family:monospace}.iv.e{color:#f87171}
.fg{margin-bottom:14px}
label{display:block;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:12px;color:#e2e8f0;font-size:20px;font-family:monospace;letter-spacing:8px;text-align:center;outline:none}
input:focus{border-color:#2550cc;box-shadow:0 0 0 3px rgba(37,80,204,.2)}
.btn{width:100%;padding:13px;background:#2550cc;color:#fff;font-weight:700;font-size:14px;border:none;border-radius:8px;cursor:pointer;margin-top:4px}
.btn:hover{background:#1a3a9e}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:14px;line-height:1.5}
.ok{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:#4ade80}
.erro{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171}
.warn{background:rgba(251,146,60,.08);border:1px solid rgba(251,146,60,.25);border-radius:8px;padding:10px;font-size:11px;color:#fb923c;margin-top:14px;text-align:center}
.links{display:flex;gap:8px;margin-bottom:14px}
.lnk{flex:1;padding:10px;border-radius:7px;font-weight:700;font-size:13px;text-align:center;text-decoration:none;color:#fff}
</style>
</head>
<body>
<div class="card">
  <h1>🔑 Reset de PIN</h1>
  <p class="sub">Redefine o PIN de acesso ao painel admin</p>

  <div class="info">
    <div class="ir"><span class="il">Banco MySQL</span><span class="iv <?= $dbOk?'':'e' ?>"><?= $dbOk?'✅ Conectado':'❌ Erro' ?></span></div>
    <div class="ir"><span class="il">PIN atual</span><span class="iv"><?= htmlspecialchars($pinInfo) ?></span></div>
  </div>

  <?php if ($msg): ?>
  <div class="alert <?= $tipo ?>"><?= $msg ?></div>
  <?php if ($tipo === 'ok'): ?>
  <div class="links">
    <a href="/tropicalfm/debug_api.html" class="lnk" style="background:#2550cc">🔧 Debug API</a>
    <a href="/tropicalfm/admin.html" class="lnk" style="background:#059669">🔐 Admin</a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <form method="POST">
    <div class="fg">
      <label>Novo PIN (4 dígitos)</label>
      <input type="password" name="pin" maxlength="4" placeholder="••••" autofocus oninput="this.value=this.value.replace(/\D/g,'')">
    </div>
    <div class="fg">
      <label>Confirmar PIN</label>
      <input type="password" name="pin2" maxlength="4" placeholder="••••" oninput="this.value=this.value.replace(/\D/g,'')">
    </div>
    <button type="submit" class="btn">🔐 Salvar Novo PIN</button>
  </form>

  <div class="warn">⚠️ Delete após usar:<br><code>C:\xampp\htdocs\tropicalfm\reset_pin.php</code></div>
</div>
</body>
</html>
