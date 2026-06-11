<?php
class UploadController {
    private string $uploadDir = __DIR__ . '/../../uploads/';
    private array  $allowed   = ['image/jpeg','image/png','image/webp','image/gif'];
    private int    $maxSize   = 5 * 1024 * 1024; // 5 MB

    public function upload(): void {
        if (!isset($_FILES['file'])) Response::error('no_file','Nenhum arquivo enviado.',422);
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) Response::error('upload_error','Erro no upload: '.$file['error'],422);
        if ($file['size'] > $this->maxSize) Response::error('file_too_large','Arquivo muito grande. Máximo 5 MB.',422);

        // Verifica MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->allowed)) Response::error('invalid_type','Tipo não permitido. Use JPG, PNG ou WebP.',422);

        // Nome único
        $ext  = match($mime) { 'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif', default=>'jpg' };
        $name = uniqid('upload_', true) . '.' . $ext;

        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $name)) {
            Response::serverError('Falha ao salvar arquivo.');
        }

        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $url     = $baseUrl . '/uploads/' . $name;
        Response::created(['url' => $url, 'name' => $name], 'Upload realizado com sucesso.');
    }
}
