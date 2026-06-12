<?php
class Auth {

    // base64url (sem + / =) — compatível com JS atob após substituição
    private static function b64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string {
        $pad = strlen($data) % 4;
        if ($pad) $data .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // Gera JWT com expiração longa (30 dias)
    public static function generateToken(array $payload = []): string {
        $header  = self::b64url(json_encode(['typ'=>'JWT','alg'=>'HS256']));
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + (86400 * 30), // 30 dias
        ]);
        $payload  = self::b64url(json_encode($payload));
        $sig      = self::b64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        return "$header.$payload.$sig";
    }

    // Valida JWT
    public static function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $sig] = $parts;
        $expected = self::b64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode(self::b64urlDecode($payload), true);
        if (!$data || $data['exp'] < time()) return null;
        return $data;
    }

    // Extrai Bearer token
    public static function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        // Suporte a getallheaders() para Apache
        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return $m[1];
        return null;
    }

    // Middleware — para se não autenticado
    public static function require(): array {
        $token = self::getBearerToken();
        if (!$token) Response::unauthorized('Token não fornecido.');
        $payload = self::verifyToken($token);
        if (!$payload) Response::unauthorized('Token inválido ou expirado.');
        return $payload;
    }

    // Hash do PIN para armazenamento
    public static function hashPin(string $pin): string {
        return hash_hmac('sha256', $pin, JWT_SECRET);
    }
}
