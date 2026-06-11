<?php
class PodcastController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function index(): void {
        $config = DB::get()->query("SELECT * FROM podcast_config WHERE id=1")->fetch();
        $eps    = DB::get()->query("SELECT * FROM podcast_episodios ORDER BY data_ep DESC, criado_em DESC LIMIT 20")->fetchAll();
        Response::ok(['config'=>$config,'episodios'=>$eps]);
    }
    public function setLive(): void {
        $d = $this->body;
        DB::get()->prepare("UPDATE podcast_config SET live_ativo=:a,live_url=:u,live_titulo=:t,live_desc=:d,canal_url=:c,channel_id=:ci WHERE id=1")
        ->execute([':a'=>(int)($d['live_ativo']??0),':u'=>$d['live_url']??null,':t'=>$d['live_titulo']??null,':d'=>$d['live_desc']??null,':c'=>$d['canal_url']??null,':ci'=>$d['channel_id']??null]);
        Response::ok(null,'Live atualizada.');
    }
    public function store(): void {
        Validator::make($this->body)->required('titulo','Título')->required('youtube_id','YouTube ID')->validate();
        $d = Validator::sanitize($this->body);
        DB::get()->prepare("INSERT INTO podcast_episodios (titulo,youtube_id,data_ep) VALUES (:t,:y,:d)")
        ->execute([':t'=>$d['titulo'],':y'=>$d['youtube_id'],':d'=>$d['data_ep']??null]);
        Response::created(['id'=>DB::get()->lastInsertId()],'Episódio adicionado.');
    }
    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM podcast_episodios WHERE id=?");
        $s->execute([$this->id]);
        if ($s->rowCount()===0) Response::notFound('Episódio');
        Response::ok(null,'Episódio removido.');
    }
}
