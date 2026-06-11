<?php
class EnqueteController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function show(): void {
        $enq = DB::get()->query("SELECT * FROM enquetes WHERE ativa=1 ORDER BY id DESC LIMIT 1")->fetch();
        if (!$enq) Response::ok(null,'Nenhuma enquete ativa.');
        $opts = DB::get()->prepare("SELECT * FROM enquete_opcoes WHERE enquete_id=? ORDER BY ordem")->execute([$enq['id']]);
        $stmt = DB::get()->prepare("SELECT * FROM enquete_opcoes WHERE enquete_id=? ORDER BY ordem");
        $stmt->execute([$enq['id']]);
        Response::ok(['enquete'=>$enq,'opcoes'=>$stmt->fetchAll()]);
    }
    public function update(): void {
        Validator::make($this->body)->required('pergunta','Pergunta')->validate();
        $d = Validator::sanitize($this->body);
        $opts = array_filter($this->body['opcoes'] ?? [], fn($o) => !empty(trim($o)));
        if (count($opts) < 2) Response::error('validation_error','Mínimo 2 opções.',422);
        // Desativa anteriores
        DB::get()->query("UPDATE enquetes SET ativa=0");
        // Nova enquete
        DB::get()->prepare("INSERT INTO enquetes (pergunta) VALUES (?)")->execute([$d['pergunta']]);
        $enqId = DB::get()->lastInsertId();
        $stmt  = DB::get()->prepare("INSERT INTO enquete_opcoes (enquete_id,texto,ordem) VALUES (?,?,?)");
        foreach (array_values($opts) as $i => $op) {
            $stmt->execute([$enqId, htmlspecialchars(trim($op), ENT_QUOTES), $i]);
        }
        Response::created(['id'=>$enqId],'Enquete criada.');
    }
    public function votar(): void {
        Validator::make($this->body)->required('opcao_id','Opção')->validate();
        $opcaoId = (int)$this->body['opcao_id'];
        $check = DB::get()->prepare("SELECT id FROM enquete_opcoes WHERE id=?")->execute([$opcaoId]);
        $op = DB::get()->prepare("SELECT id FROM enquete_opcoes WHERE id=?");
        $op->execute([$opcaoId]);
        if (!$op->fetch()) Response::notFound('Opção');
        DB::get()->prepare("UPDATE enquete_opcoes SET votos=votos+1 WHERE id=?")->execute([$opcaoId]);
        Response::ok(null,'Voto registrado.');
    }
}
