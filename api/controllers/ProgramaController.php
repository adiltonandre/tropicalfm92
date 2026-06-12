<?php
class ProgramaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        $rows = DB::get()->query(
            "SELECT * FROM programas WHERE ativo = 1 ORDER BY ordem ASC, horario_ini ASC"
        )->fetchAll();
        Response::ok($rows);
    }

    public function show(): void {
        $stmt = DB::get()->prepare("SELECT * FROM programas WHERE id = ?");
        $stmt->execute([$this->id]);
        $row = $stmt->fetch();
        if (!$row) Response::notFound('Programa');
        Response::ok($row);
    }

    public function store(): void {
        $b = $this->body;
        // Normaliza campos
        if (empty($b['nome']) && !empty($b['nome_programa'])) $b['nome'] = $b['nome_programa'];
        if (empty($b['horario_ini']) && !empty($b['inicio'])) $b['horario_ini'] = $b['inicio'];
        if (empty($b['horario_ini']) && !empty($b['st']))     $b['horario_ini'] = $b['st'];
        if (empty($b['horario_fim']) && !empty($b['fim']))    $b['horario_fim'] = $b['fim'];
        if (empty($b['horario_fim']) && !empty($b['en']))     $b['horario_fim'] = $b['en'];
        if (empty($b['foto_url'])    && !empty($b['foto']))   $b['foto_url']    = $b['foto'];
        if (empty($b['foto_url'])    && !empty($b['img']))    $b['foto_url']    = $b['img'];
        if (empty($b['descricao'])   && !empty($b['desc']))   $b['descricao']   = $b['desc'];
        if (empty($b['dias'])        && !empty($b['days']))   $b['dias']        = $b['days'];

        Validator::make($b)->required('nome', 'Nome')->validate();
        $d = Validator::sanitize($b);

        DB::get()->prepare(
            "INSERT INTO programas (nome, host, horario_ini, horario_fim, dias, foto_url, descricao, ordem)
             VALUES (:nome, :host, :ini, :fim, :dias, :foto, :desc, :ordem)"
        )->execute([
            ':nome'  => $d['nome'],
            ':host'  => $d['host']       ?? null,
            ':ini'   => $d['horario_ini']?? null,
            ':fim'   => $d['horario_fim']?? null,
            ':dias'  => $d['dias']       ?? 'Diário',
            ':foto'  => $d['foto_url']   ?? null,
            ':desc'  => $d['descricao']  ?? null,
            ':ordem' => (int)($d['ordem']?? 0),
        ]);
        Response::created(['id' => DB::get()->lastInsertId()], 'Programa criado.');
    }

    public function update(): void {
        $b = $this->body;
        if (empty($b['nome']) && !empty($b['nome_programa'])) $b['nome'] = $b['nome_programa'];
        Validator::make($b)->required('nome','Nome')->validate();
        $d = Validator::sanitize($b);
        DB::get()->prepare(
            "UPDATE programas SET nome=:nome, host=:host, horario_ini=:ini, horario_fim=:fim,
             dias=:dias, foto_url=:foto, descricao=:desc, ordem=:ordem WHERE id=:id"
        )->execute([
            ':nome' => $d['nome'], ':host' => $d['host']??null,
            ':ini'  => $d['horario_ini']??$d['inicio']??$d['st']??null,
            ':fim'  => $d['horario_fim']??$d['fim']??$d['en']??null,
            ':dias' => $d['dias']??$d['days']??'Diário',
            ':foto' => $d['foto_url']??$d['foto']??$d['img']??null,
            ':desc' => $d['descricao']??$d['desc']??null,
            ':ordem'=> (int)($d['ordem']??0), ':id' => $this->id,
        ]);
        Response::ok(['id' => $this->id], 'Programa atualizado.');
    }

    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM programas WHERE id = ?");
        $s->execute([$this->id]);
        if ($s->rowCount() === 0) Response::notFound('Programa');
        Response::ok(null, 'Programa removido.');
    }
}
