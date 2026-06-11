<?php
class NoticiaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        $status = $_GET['status'] ?? 'published';
        $cat    = $_GET['categoria'] ?? null;
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        $offset = (int)($_GET['offset'] ?? 0);

        $where = ['1=1'];
        $params = [];

        if ($status !== 'all') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($cat) {
            $where[] = 'categoria = :cat';
            $params[':cat'] = $cat;
        }

        $whereStr = implode(' AND ', $where);
        $rows = DB::get()->prepare(
            "SELECT id, titulo, categoria, resumo, imagem_url, autor, status,
                    destaque, visualizacoes, publicado_em, criado_em
             FROM noticias WHERE {$whereStr}
             ORDER BY criado_em DESC LIMIT :lim OFFSET :off"
        );
        $rows->bindValue(':lim', $limit, PDO::PARAM_INT);
        $rows->bindValue(':off', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $rows->bindValue($k, $v);
        $rows->execute();

        $total = DB::get()->prepare("SELECT COUNT(*) FROM noticias WHERE {$whereStr}");
        foreach ($params as $k => $v) $total->bindValue($k, $v);
        $total->execute();

        Response::ok([
            'items'  => $rows->fetchAll(),
            'total'  => (int) $total->fetchColumn(),
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(): void {
        $row = DB::get()->prepare("SELECT * FROM noticias WHERE id = ?")->execute([$this->id]);
        $noticia = DB::get()->prepare("SELECT * FROM noticias WHERE id = ?");
        $noticia->execute([$this->id]);
        $row = $noticia->fetch();
        if (!$row) Response::notFound('Notícia');
        // Incrementa visualizações
        DB::get()->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?")->execute([$this->id]);
        Response::ok($row);
    }

    public function store(): void {
        Validator::make($this->body)
            ->required('titulo', 'Título')
            ->maxLength('titulo', 300, 'Título')
            ->in('status', ['published','draft'], 'Status')
            ->validate();

        $d    = Validator::sanitize($this->body);
        $slug = $this->makeSlug($d['titulo']);

        $stmt = DB::get()->prepare(
            "INSERT INTO noticias (titulo, slug, categoria, resumo, conteudo,
             imagem_url, autor, status, destaque, publicado_em)
             VALUES (:titulo, :slug, :cat, :resumo, :conteudo,
             :img, :autor, :status, :destaque, :pub)"
        );
        $stmt->execute([
            ':titulo'   => $d['titulo'],
            ':slug'     => $slug,
            ':cat'      => $d['categoria']  ?? 'Geral',
            ':resumo'   => $d['resumo']     ?? null,
            ':conteudo' => $d['conteudo']   ?? null,
            ':img'      => $d['imagem_url'] ?? null,
            ':autor'    => $d['autor']      ?? 'Redação',
            ':status'   => $d['status']     ?? 'draft',
            ':destaque' => (int)($d['destaque'] ?? 0),
            ':pub'      => $d['status'] === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        $id = DB::get()->lastInsertId();
        Response::created(['id' => $id], 'Notícia criada com sucesso.');
    }

    public function update(): void {
        Validator::make($this->body)->required('titulo','Título')->validate();
        $d = Validator::sanitize($this->body);

        DB::get()->prepare(
            "UPDATE noticias SET titulo=:titulo, categoria=:cat, resumo=:resumo,
             conteudo=:conteudo, imagem_url=:img, autor=:autor, status=:status,
             destaque=:destaque, publicado_em=IF(:status='published' AND publicado_em IS NULL, NOW(), publicado_em)
             WHERE id=:id"
        )->execute([
            ':titulo'   => $d['titulo'],
            ':cat'      => $d['categoria']  ?? 'Geral',
            ':resumo'   => $d['resumo']     ?? null,
            ':conteudo' => $d['conteudo']   ?? null,
            ':img'      => $d['imagem_url'] ?? null,
            ':autor'    => $d['autor']      ?? 'Redação',
            ':status'   => $d['status']     ?? 'draft',
            ':destaque' => (int)($d['destaque'] ?? 0),
            ':id'       => $this->id,
        ]);

        Response::ok(['id' => $this->id], 'Notícia atualizada.');
    }

    public function destroy(): void {
        $stmt = DB::get()->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->execute([$this->id]);
        if ($stmt->rowCount() === 0) Response::notFound('Notícia');
        Response::ok(null, 'Notícia removida.');
    }

    private function makeSlug(string $s): string {
        $s = mb_strtolower($s);
        $s = preg_replace('/[áàãâä]/u','a',$s);
        $s = preg_replace('/[éèêë]/u','e',$s);
        $s = preg_replace('/[íìîï]/u','i',$s);
        $s = preg_replace('/[óòõôö]/u','o',$s);
        $s = preg_replace('/[úùûü]/u','u',$s);
        $s = preg_replace('/[ç]/u','c',$s);
        $s = preg_replace('/[^a-z0-9]+/','-',$s);
        return trim($s,'-').'-'.time();
    }
}
