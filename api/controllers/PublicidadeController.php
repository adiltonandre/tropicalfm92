<?php
class PublicidadeController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function show(): void {
        $rows = DB::get()->query("SELECT * FROM publicidade")->fetchAll();
        $data = [];
        foreach ($rows as $r) $data[$r['tipo']] = $r;
        Response::ok($data);
    }
    public function update(): void {
        $tipo = $this->id ?? $this->body['tipo'] ?? null;
        if (!in_array($tipo,['principal','flutuante'])) Response::error('invalid_type','Tipo deve ser principal ou flutuante.',422);
        $d = $this->body;
        DB::get()->prepare("UPDATE publicidade SET ativo=:a,imagem_url=:i,link_url=:l,texto=:t,delay_s=:d WHERE tipo=:tipo")
        ->execute([':a'=>(int)($d['ativo']??0),':i'=>$d['imagem_url']??null,':l'=>$d['link_url']??null,':t'=>$d['texto']??null,':d'=>(int)($d['delay_s']??8),':tipo'=>$tipo]);
        Response::ok(null,'Publicidade atualizada.');
    }
}
