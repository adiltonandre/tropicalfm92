<?php
class AuthController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function login(): void {
        Validator::make($this->body)
            ->required('pin', 'PIN')
            ->pin('pin')
            ->validate();

        $pin = $this->body['pin'];

        // Verifica hash armazenado ou PIN padrão
        $db = DB::get();
        $row = $db->query("SELECT valor FROM configuracoes WHERE chave = 'pin_hash'")->fetch();
        $storedHash = $row['valor'] ?? '';

        if ($storedHash) {
            $ok = hash_equals($storedHash, Auth::hashPin($pin));
        } else {
            $ok = ($pin === ADMIN_PIN);
        }

        if (!$ok) {
            $this->logAttempt(false);
            Response::error('invalid_pin', 'PIN incorreto.', 401);
        }

        $this->logAttempt(true);
        $token = Auth::generateToken(['role' => 'admin']);

        Response::ok([
            'token'      => $token,
            'expires_in' => 86400,
        ], 'Login realizado com sucesso.');
    }

    public function refresh(): void {
        $payload = Auth::require();
        $token   = Auth::generateToken(['role' => $payload['role']]);
        Response::ok(['token' => $token, 'expires_in' => 86400], 'Token renovado.');
    }

    private function logAttempt(bool $success): void {
        try {
            DB::get()->prepare(
                "INSERT INTO activity_log (acao, detalhes, ip) VALUES (?, ?, ?)"
            )->execute([
                $success ? 'login_success' : 'login_failed',
                'PIN login attempt',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception) {}
    }
}
