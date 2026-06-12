<?php
class TopMusicaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        Response::ok(DB::get()->query(
            "SELECT * FROM top_musicas WHERE ativo = 1 ORDER BY posicao ASC LIMIT 10"
        )->fetchAll());
    }

    public function store(): void {
        $b = $this->body;
        // Normaliza
        if (empty($b['musica'])    && !empty($b['song']))       $b['musica']    = $b['song'];
        if (empty($b['artista'])   && !empty($b['artist']))     $b['artista']   = $b['artist'];
        if (empty($b['tendencia']) && !empty($b['trend']))      $b['tendencia'] = $b['trend'];
        if (empty($b['capa_url'])  && !empty($b['cover']))      $b['capa_url']  = $b['cover'];

        Validator::make($b)->required('musica','Música')->required('artista','Artista')->validate();

        $count = (int)DB::get()->query("SELECT COUNT(*) FROM top_musicas WHERE ativo=1")->fetchColumn();
        if ($count >= 10) Response::error('limit_reached','Máximo de 10 músicas.',422);

        $d = Validator::sanitize($b);
        DB::get()->prepare(
            "INSERT INTO top_musicas (musica, artista, tendencia, capa_url, posicao) VALUES (:m,:a,:t,:c,:p)"
        )->execute([
            ':m' => $d['musica'],
            ':a' => $d['artista'],
            ':t' => $d['tendencia'] ?? 'same',
            ':c' => $d['capa_url']  ?? null,
            ':p' => $count + 1,
        ]);
        Response::created(['id' => DB::get()->lastInsertId()], 'Música adicionada.');
    }

    public function reorder(): void {
        if (!isset($this->body['ordem']) || !is_array($this->body['ordem']))
            Response::error('invalid_data','Envie array "ordem" com IDs.',422);
        $stmt = DB::get()->prepare("UPDATE top_musicas SET posicao=:p WHERE id=:id");
        foreach ($this->body['ordem'] as $pos => $id) $stmt->execute([':p'=>$pos+1,':id'=>$id]);
        Response::ok(null,'Ordem atualizada.');
    }

    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM top_musicas WHERE id=?");
        $s->execute([$this->id]);
        if ($s->rowCount()===0) Response::notFound('Música');
        // Reposiciona
        $all = DB::get()->query("SELECT id FROM top_musicas WHERE ativo=1 ORDER BY posicao ASC")->fetchAll();
        $stmt = DB::get()->prepare("UPDATE top_musicas SET posicao=? WHERE id=?");
        foreach ($all as $i => $r) $stmt->execute([$i+1, $r['id']]);
        Response::ok(null,'Música removida.');
    }
}

    public function update(): void {
        $b = $this->body;
        if (empty($b['musica']) && !empty($b['song']))    $b['musica']    = $b['song'];
        if (empty($b['artista']) && !empty($b['artist'])) $b['artista']   = $b['artist'];
        if (empty($b['tendencia']) && !empty($b['trend'])) $b['tendencia'] = $b['trend'];
        if (empty($b['capa_url']) && !empty($b['cover'])) $b['capa_url']  = $b['cover'];
        $d = Validator::sanitize($b);
        DB::get()->prepare(
            "UPDATE top_musicas SET musica=:m,artista=:a,tendencia=:t,capa_url=:c WHERE id=:id"
        )->execute([':m'=>$d['musica']??'',':a'=>$d['artista']??'',':t'=>$d['tendencia']??'same',':c'=>$d['capa_url']??null,':id'=>$this->id]);
        Response::ok(['id'=>$this->id],'Música atualizada.');
    }
