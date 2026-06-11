Passo 1 — Suba os arquivos

Faça upload de toda a pasta via FTP ou cPanel File Manager para public_html/

Passo 2 — Execute o instalador
Acesse no navegador:
https://seusite.com.br/api/install.php?key=tropical2025
O instalador vai:

Criar as tabelas automaticamente no banco
Gerar o database.php configurado
Salvar o PIN com hash seguro
Confirmar tudo OK

Passo 3 — Conecte o painel admin

Abra admin.html → menu Conexão API
Digite a URL: seusite.com.br/api
Faça login com o PIN
Clique "Migrar dados" — tudo que você já cadastrou vai direto pro banco
