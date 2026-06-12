<?php
class SlideController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        $where = isset($_GET['ativo']) ? "WHERE ativo = 1" : "";
        $rows = DB::get()->query("SELECT * FROM slides {$where} ORDER BY ordem ASC, id ASC")->fetchAll();
        Response::ok($rows);
    }

    public function store(): void {
        $b = $this->body;
        // Normaliza campos: aceita titulo ou title, imagem_url ou img, etc.
        if (empty($b['titulo']) && !empty($b['title']))     $b['titulo']    = $b['title'];
        if (empty($b['subtitulo']) && !empty($b['sub']))    $b['subtitulo'] = $b['sub'];
        if (empty($b['imagem_url']) && !empty($b['img']))   $b['imagem_url']= $b['img'];
        if (empty($b['btn_texto']) && !empty($b['btn']))    $b['btn_texto'] = $b['btn'];
        if (empty($b['btn_link']) && !empty($b['link']))    $b['btn_link']  = $b['link'];

        Validator::make($b)->required('titulo', 'Título')->validate();
        $d = Validator::sanitize($b);

        DB::get()->prepare(
            "INSERT INTO slides (titulo, subtitulo, imagem_url, tag, btn_texto, btn_link, ativo, ordem)
             VALUES (:t, :s, :i, :tag, :btn, :link, :a, :o)"
        )->execute([
            ':t'    => $d['titulo'],
            ':s'    => $d['subtitulo']  ?? null,
            ':i'    => $d['imagem_url'] ?? null,
            ':tag'  => $d['tag']        ?? 'Destaque',
            ':btn'  => $d['btn_texto']  ?? null,
            ':link' => $d['btn_link']   ?? null,
            ':a'    => isset($d['ativo']) ? (int)$d['ativo'] : 1,
            ':o'    => (int)($d['ordem'] ?? 0),
        ]);
        Response::created(['id' => DB::get()->lastInsertId()], 'Slide criado.');
    }

    public function update(): void {
        $b = $this->body;
        if (empty($b['titulo']) && !empty($b['title'])) $b['titulo'] = $b['title'];
        $d = Validator::sanitize($b);
        DB::get()->prepare(
            "UPDATE slides SET titulo=:t, subtitulo=:s, imagem_url=:i, tag=:tag,
             btn_texto=:btn, btn_link=:link, ativo=:a, ordem=:o WHERE id=:id"
        )->execute([
            ':t'  => $d['titulo'] ?? '',
            ':s'  => $d['subtitulo'] ?? $d['sub'] ?? null,
            ':i'  => $d['imagem_url'] ?? $d['img'] ?? null,
            ':tag'=> $d['tag'] ?? 'Destaque',
            ':btn'=> $d['btn_texto'] ?? $d['btn'] ?? null,
            ':link'=> $d['btn_link'] ?? $d['link'] ?? null,
            ':a'  => isset($d['ativo']) ? (int)$d['ativo'] : 1,
            ':o'  => (int)($d['ordem'] ?? 0),
            ':id' => $this->id,
        ]);
        Response::ok(['id' => $this->id], 'Slide atualizado.');
    }

    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM slides WHERE id = ?");
        $s->execute([$this->id]);
        if ($s->rowCount() === 0) Response::notFound('Slide');
        Response::ok(null, 'Slide removido.');
    }
}
