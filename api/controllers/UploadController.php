<?php
class UploadController {
    private string $uploadDir;
    private array  $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    private int    $maxSize = 5 * 1024 * 1024; // 5 MB

    public function __construct() {
        // Pasta uploads na raiz do projeto (dois níveis acima de api/controllers/)
        $this->uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
    }

    public function upload(): void {
        if (empty($_FILES['file'])) {
            Response::error('no_file', 'Nenhum arquivo enviado.', 422);
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [1=>'Arquivo muito grande',2=>'Arquivo muito grande',3=>'Upload incompleto',4=>'Nenhum arquivo',6=>'Pasta temporária ausente',7=>'Erro ao gravar'];
            Response::error('upload_error', $errors[$file['error']] ?? 'Erro no upload', 422);
        }

        if ($file['size'] > $this->maxSize) {
            Response::error('file_too_large', 'Arquivo muito grande. Máximo 5MB.', 422);
        }

        // Verifica MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->allowed)) {
            Response::error('invalid_type', 'Tipo não permitido. Use JPG, PNG, WebP ou GIF.', 422);
        }

        // Cria pasta se não existir
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                Response::serverError('Não foi possível criar a pasta de uploads.');
            }
        }

        // Cria .htaccess para permitir acesso às imagens mas bloquear PHP
        $htaccess = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess,
                "Options -Indexes\n<FilesMatch \"\\.php$\">\nRequire all denied\n</FilesMatch>\n"
            );
        }

        // Nome único seguro
        $ext  = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg'
        };
        $name = uniqid('img_', true) . '.' . $ext;
        $dest = $this->uploadDir . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::serverError('Falha ao salvar o arquivo.');
        }

        // URL pública
        $proto   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'];
        $baseUri = rtrim(dirname(dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))), '/');
        $url     = "{$proto}://{$host}{$baseUri}/uploads/{$name}";

        Response::created([
            'url'  => $url,
            'name' => $name,
            'size' => $file['size'],
            'mime' => $mime,
        ], 'Upload realizado com sucesso.');
    }
}
