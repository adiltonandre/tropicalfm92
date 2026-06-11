<?php
class EmpresaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function show(): void {
        $row = DB::get()->query("SELECT * FROM empresa WHERE id = 1")->fetch();
        if ($row && $row['redes']) $row['redes'] = json_decode($row['redes'], true);
        if ($row && $row['identidade']) $row['identidade'] = json_decode($row['identidade'], true);
        Response::ok($row ?: new stdClass());
    }

    public function update(): void {
        $v = Validator::make($this->body);
        $v->required('nome', 'Nome Fantasia')
          ->maxLength('nome', 120, 'Nome')
          ->email('email', 'E-mail principal')
          ->url('logo_url', 'URL do Logo')
          ->cnpj('cnpj')
          ->validate();

        $d = Validator::sanitize($this->body);

        $sql = "UPDATE empresa SET
            nome=:nome, razao=:razao, cnpj=:cnpj, slogan=:slogan,
            freq=:freq, tipo=:tipo, ano_fund=:ano_fund, stream_url=:stream_url,
            cep=:cep, rua=:rua, numero=:numero, bairro=:bairro,
            cidade=:cidade, estado=:estado, complemento=:complemento,
            telefone=:telefone, whatsapp=:whatsapp, wa_link=:wa_link,
            email=:email, email2=:email2,
            logo_url=:logo_url, favicon_url=:favicon_url,
            redes=:redes, identidade=:identidade
        WHERE id = 1";

        DB::get()->prepare($sql)->execute([
            ':nome'         => $d['nome']         ?? '',
            ':razao'        => $d['razao']        ?? null,
            ':cnpj'         => $d['cnpj']         ?? null,
            ':slogan'       => $d['slogan']        ?? null,
            ':freq'         => $d['freq']          ?? null,
            ':tipo'         => $d['tipo']          ?? 'fm',
            ':ano_fund'     => $d['ano_fund']      ?? null,
            ':stream_url'   => $d['stream_url']    ?? null,
            ':cep'          => $d['cep']           ?? null,
            ':rua'          => $d['rua']           ?? null,
            ':numero'       => $d['numero']        ?? null,
            ':bairro'       => $d['bairro']        ?? null,
            ':cidade'       => $d['cidade']        ?? null,
            ':estado'       => $d['estado']        ?? null,
            ':complemento'  => $d['complemento']   ?? null,
            ':telefone'     => $d['telefone']      ?? null,
            ':whatsapp'     => $d['whatsapp']      ?? null,
            ':wa_link'      => $d['wa_link']       ?? null,
            ':email'        => $d['email']         ?? null,
            ':email2'       => $d['email2']        ?? null,
            ':logo_url'     => $d['logo_url']      ?? null,
            ':favicon_url'  => $d['favicon_url']   ?? null,
            ':redes'        => isset($d['redes'])      ? json_encode($d['redes'])      : null,
            ':identidade'   => isset($d['identidade']) ? json_encode($d['identidade']) : null,
        ]);

        $this->log('empresa_updated');
        $this->show();
    }

    private function log(string $acao): void {
        try {
            DB::get()->prepare("INSERT INTO activity_log (acao, ip) VALUES (?, ?)")
                     ->execute([$acao, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception) {}
    }
}
