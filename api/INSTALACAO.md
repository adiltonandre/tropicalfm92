# TROPICAL FM — GUIA DE INSTALAÇÃO EM PRODUÇÃO

## Stack
- PHP 8.0+ (funciona em qualquer hospedagem cPanel)
- MySQL 5.7+ / MariaDB 10.3+
- Apache com mod_rewrite

---

## PASSO 1 — Criar o banco de dados

1. Acesse o **phpMyAdmin** do seu cPanel
2. Crie um novo banco: `tropicalfm` (charset: `utf8mb4`, collation: `utf8mb4_unicode_ci`)
3. Selecione o banco criado → aba **SQL**
4. Cole e execute o conteúdo do arquivo `api/schema.sql`

---

## PASSO 2 — Configurar a API

Edite o arquivo `api/config/database.php`:

```php
define('DB_HOST', 'localhost');     // normalmente 'localhost'
define('DB_NAME', 'tropicalfm');    // nome do banco criado
define('DB_USER', 'usuario_db');    // usuário MySQL do cPanel
define('DB_PASS', 'senha_db');      // senha MySQL do cPanel
define('JWT_SECRET', 'COLOQUE_UMA_CHAVE_ALEATORIA_AQUI_2025');
define('ADMIN_PIN',  '1234');       // PIN padrão (altere pelo painel!)
```

**Dica de segurança:** Gere o JWT_SECRET com:
```
openssl rand -hex 32
```

---

## PASSO 3 — Fazer upload dos arquivos

Via **Gerenciador de Arquivos** do cPanel ou FTP:

```
public_html/
├── index.html          ← site principal
├── admin.html          ← painel admin
├── escolha.html        ← página de apresentação
├── index_modelo*.html  ← modelos alternativos
├── uploads/            ← criado automaticamente
└── api/
    ├── .htaccess
    ├── index.php
    ├── schema.sql
    ├── config/
    │   ├── database.php   ← EDITE AQUI
    │   ├── response.php
    │   └── validator.php
    ├── middleware/
    │   └── auth.php
    └── controllers/
        └── *.php
```

---

## PASSO 4 — Testar a API

Acesse no navegador:
```
https://seusite.com.br/api/health
```

Resposta esperada:
```json
{
  "success": true,
  "message": "API funcionando.",
  "data": { "status": "ok", "db": true }
}
```

---

## PASSO 5 — Conectar o painel admin à API

No arquivo `admin.html`, localize e edite a variável:
```javascript
const API_BASE = 'https://seusite.com.br/api';
```

---

## PASSO 6 — Alterar o PIN

1. Acesse `seusite.com.br/admin.html`
2. Entre com o PIN padrão: **1234**
3. Vá em **Segurança (PIN)** → Altere imediatamente

---

## Segurança adicional (recomendado)

```apache
# Bloquear acesso direto à pasta api/
# Adicione no .htaccess raiz:
RewriteRule ^api/config/ - [F,L]
RewriteRule ^api/.*\.php$ - [F,L]
```

---

## Hospedagens testadas

| Provedor    | Plano      | Preço/mês | PHP | MySQL |
|-------------|-----------|-----------|-----|-------|
| Hostinger   | Premium   | ~R$10     | 8.2 | ✅    |
| Locaweb     | Turbo     | ~R$20     | 8.1 | ✅    |
| HostGator   | Hatchling | ~R$12     | 8.0 | ✅    |
| GoDaddy     | Básico    | ~R$15     | 8.1 | ✅    |
| UOL Host    | Básico    | ~R$12     | 7.4 | ✅    |

---

## Suporte

Em caso de dúvidas, verifique:
- `php_error.log` no cPanel
- Headers da resposta da API (devem ser `application/json`)
- Se o `mod_rewrite` está ativo (cPanel → Apache → Módulos)
