<?php
// ══════════════════════════════════════════════
//  MIDDLEWARE JWT — autenticação por token
// ══════════════════════════════════════════════
class Auth {

    // Gera um JWT simples (sem biblioteca externa)
    public static function generateToken(array $payload = []): string {
        $header  = base64_encode(json_encode(['typ'=>'JWT','alg'=>'HS256']));
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + 86400, // 24h
        ]);
        $payload  = base64_encode(json_encode($payload));
        $sig      = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        return "$header.$payload.$sig";
    }

    // Valida o token e retorna o payload
    public static function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $sig] = $parts;
        $expected = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode(base64_decode($payload), true);
        if (!$data || $data['exp'] < time()) return null;
        return $data;
    }

    // Extrai token do header Authorization: Bearer <token>
    public static function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return $m[1];
        return null;
    }

    // Middleware: protege rota — para se não autenticado
    public static function require(): array {
        $token = self::getBearerToken();
        if (!$token) Response::unauthorized('Token não fornecido.');
        $payload = self::verifyToken($token);
        if (!$payload) Response::unauthorized('Token inválido ou expirado.');
        return $payload;
    }

    // Verifica PIN e retorna token
    public static function loginWithPin(string $pin): string {
        if ($pin !== ADMIN_PIN) {
            Response::error('invalid_pin', 'PIN incorreto.', 401);
        }
        return self::generateToken(['role' => 'admin']);
    }

    // Hash simples para comparação de PINs armazenados
    public static function hashPin(string $pin): string {
        return hash_hmac('sha256', $pin, JWT_SECRET);
    }
}
