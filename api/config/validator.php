<?php
// ══════════════════════════════════════════════
//  CLASSE VALIDATOR — valida dados de entrada
// ══════════════════════════════════════════════
class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    // Regras encadeáveis
    public function required(string $field, string $label = ''): self {
        $label = $label ?: $field;
        if (empty($this->data[$field]) && $this->data[$field] !== '0') {
            $this->errors[$field][] = "O campo {$label} é obrigatório.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = "{$label} deve ter ao menos {$min} caracteres.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = "{$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'E-mail'): self {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} inválido.";
        }
        return $this;
    }

    public function url(string $field, string $label = 'URL'): self {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "{$label} inválida.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self {
        $label = $label ?: $field;
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "{$label} deve ser numérico.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed)) {
            $this->errors[$field][] = "{$label} deve ser um dos valores: ".implode(', ', $allowed).".";
        }
        return $this;
    }

    public function cnpj(string $field): self {
        if (empty($this->data[$field])) return $this;
        $c = preg_replace('/\D/', '', $this->data[$field]);
        if (strlen($c) !== 14) {
            $this->errors[$field][] = "CNPJ deve ter 14 dígitos.";
        }
        return $this;
    }

    public function pin(string $field): self {
        if (!empty($this->data[$field]) && !preg_match('/^\d{4}$/', $this->data[$field])) {
            $this->errors[$field][] = "PIN deve ter exatamente 4 dígitos numéricos.";
        }
        return $this;
    }

    // ── Verificação ─────────────────────────
    public function fails(): bool {
        return !empty($this->errors);
    }
    public function errors(): array {
        return $this->errors;
    }
    public function validate(): void {
        if ($this->fails()) {
            Response::validationError($this->errors);
        }
    }

    // ── Helper estático ──────────────────────
    public static function make(array $data): self {
        return new self($data);
    }

    // ── Sanitização ─────────────────────────
    public static function sanitize(array $data): array {
        return array_map(function($v) {
            if (is_string($v)) return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
            return $v;
        }, $data);
    }
}
