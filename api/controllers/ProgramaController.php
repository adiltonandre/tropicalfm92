<?php
class ProgramaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        $rows = DB::get()->query(
            "SELECT * FROM programas WHERE ativo=1 ORDER BY ordem ASC, horario_ini ASC"
        )->fetchAll();
        Response::ok($rows);
    }

    public function show(): void {
        $row = DB::get()->prepare("SELECT * FROM programas WHERE id=?")->execute([$this->id]);
        $prog = DB::get()->prepare("SELECT * FROM programas WHERE id=?");
        $prog->execute([$this->id]);
        $row = $prog->fetch();
        if (!$row) Response::notFound('Programa');
        Response::ok($row);
    }

    public function store(): void {
        Validator::make($this->body)->required('nome','Nome')->validate();
        $d = Validator::sanitize($this->body);
        DB::get()->prepare(
            "INSERT INTO programas (nome, host, horario_ini, horario_fim, dias, foto_url, descricao, ordem)
             VALUES (:nome,:host,:ini,:fim,:dias,:foto,:desc,:ordem)"
        )->execute([
            ':nome'  => $d['nome'],
            ':host'  => $d['host']      ?? null,
            ':ini'   => $d['horario_ini'] ?? null,
            ':fim'   => $d['horario_fim'] ?? null,
            ':dias'  => $d['dias']      ?? 'Diário',
            ':foto'  => $d['foto_url']  ?? null,
            ':desc'  => $d['descricao'] ?? null,
            ':ordem' => (int)($d['ordem'] ?? 0),
        ]);
        Response::created(['id' => DB::get()->lastInsertId()], 'Programa criado.');
    }

    public function update(): void {
        Validator::make($this->body)->required('nome','Nome')->validate();
        $d = Validator::sanitize($this->body);
        DB::get()->prepare(
            "UPDATE programas SET nome=:nome,host=:host,horario_ini=:ini,horario_fim=:fim,
             dias=:dias,foto_url=:foto,descricao=:desc,ordem=:ordem WHERE id=:id"
        )->execute([
            ':nome'=>$d['nome'],':host'=>$d['host']??null,':ini'=>$d['horario_ini']??null,
            ':fim'=>$d['horario_fim']??null,':dias'=>$d['dias']??'Diário',
            ':foto'=>$d['foto_url']??null,':desc'=>$d['descricao']??null,
            ':ordem'=>(int)($d['ordem']??0),':id'=>$this->id
        ]);
        Response::ok(['id'=>$this->id],'Programa atualizado.');
    }

    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM programas WHERE id=?");
        $s->execute([$this->id]);
        if ($s->rowCount()===0) Response::notFound('Programa');
        Response::ok(null,'Programa removido.');
    }
}
