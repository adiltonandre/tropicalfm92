<?php
class TopMusicaController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function index(): void {
        Response::ok(DB::get()->query("SELECT * FROM top_musicas WHERE ativo=1 ORDER BY posicao ASC LIMIT 10")->fetchAll());
    }
    public function store(): void {
        Validator::make($this->body)->required('musica','Música')->required('artista','Artista')->validate();
        $count = (int)DB::get()->query("SELECT COUNT(*) FROM top_musicas WHERE ativo=1")->fetchColumn();
        if ($count >= 10) Response::error('limit_reached','Máximo de 10 músicas atingido.',422);
        $d = Validator::sanitize($this->body);
        DB::get()->prepare("INSERT INTO top_musicas (musica,artista,tendencia,capa_url,posicao) VALUES (:m,:a,:t,:c,:p)")
        ->execute([':m'=>$d['musica'],':a'=>$d['artista'],':t'=>$d['tendencia']??'same',':c'=>$d['capa_url']??null,':p'=>$count+1]);
        Response::created(['id'=>DB::get()->lastInsertId()],'Música adicionada.');
    }
    public function reorder(): void {
        if (!isset($this->body['ordem']) || !is_array($this->body['ordem'])) Response::error('invalid_data','Envie array "ordem" com IDs.',422);
        $stmt = DB::get()->prepare("UPDATE top_musicas SET posicao=:p WHERE id=:id");
        foreach ($this->body['ordem'] as $pos => $id) {
            $stmt->execute([':p'=>$pos+1,':id'=>$id]);
        }
        Response::ok(null,'Ordem atualizada.');
    }
    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM top_musicas WHERE id=?");
        $s->execute([$this->id]);
        if ($s->rowCount()===0) Response::notFound('Música');
        // Reposiciona
        DB::get()->query("SET @p:=0; UPDATE top_musicas SET posicao=(@p:=@p+1) WHERE ativo=1 ORDER BY posicao");
        Response::ok(null,'Música removida.');
    }
}
