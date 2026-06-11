<?php
// ══════════════════════════════════════════════
//  CLASSE RESPONSE — padroniza todas as respostas
// ══════════════════════════════════════════════
class Response {

    // ── Sucesso ──────────────────────────────
    public static function ok(mixed $data = null, string $message = 'ok', int $status = 200): never {
        http_response_code($status);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Criado ───────────────────────────────
    public static function created(mixed $data = null, string $message = 'Registro criado com sucesso.'): never {
        self::ok($data, $message, 201);
    }

    // ── Erro com código semântico ─────────────
    public static function error(
        string $code,
        string $message,
        int $status = 400,
        array $errors = []
    ): never {
        http_response_code($status);
        $body = [
            'success' => false,
            'error'   => $code,
            'message' => $message,
        ];
        if ($errors) $body['errors'] = $errors;
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Não encontrado ───────────────────────
    public static function notFound(string $resource = 'Recurso'): never {
        self::error('not_found', "{$resource} não encontrado.", 404);
    }

    // ── Não autorizado ───────────────────────
    public static function unauthorized(string $message = 'Não autorizado.'): never {
        self::error('unauthorized', $message, 401);
    }

    // ── Proibido ─────────────────────────────
    public static function forbidden(string $message = 'Acesso negado.'): never {
        self::error('forbidden', $message, 403);
    }

    // ── Validação ────────────────────────────
    public static function validationError(array $errors): never {
        self::error('validation_error', 'Verifique os dados enviados.', 422, $errors);
    }

    // ── Erro interno ─────────────────────────
    public static function serverError(string $message = 'Erro interno do servidor.'): never {
        self::error('server_error', $message, 500);
    }
}
