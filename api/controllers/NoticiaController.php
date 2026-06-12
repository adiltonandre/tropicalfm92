<?php
class NoticiaController {
    public function __construct(private array $body, private ?string $id = null) {}

    public function index(): void {
        $status = $_GET['status'] ?? 'published';
        $cat    = $_GET['categoria'] ?? null;
        $limit  = min((int)($_GET['limit'] ?? 20), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $search = $_GET['q'] ?? null;

        $where  = ['1=1'];
        $params = [];

        if ($status !== 'all') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($cat) {
            $where[] = 'categoria = :cat';
            $params[':cat'] = $cat;
        }
        if ($search) {
            $where[] = '(titulo LIKE :q OR resumo LIKE :q)';
            $params[':q'] = "%{$search}%";
        }

        $whereStr = implode(' AND ', $where);
        $stmt = DB::get()->prepare(
            "SELECT id, titulo, categoria, resumo, imagem_url, autor, status,
                    destaque, visualizacoes, publicado_em, criado_em
             FROM noticias WHERE {$whereStr}
             ORDER BY criado_em DESC LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();

        $total = DB::get()->prepare("SELECT COUNT(*) FROM noticias WHERE {$whereStr}");
        foreach ($params as $k => $v) $total->bindValue($k, $v);
        $total->execute();

        Response::ok([
            'items'  => $stmt->fetchAll(),
            'total'  => (int) $total->fetchColumn(),
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    }

    public function show(): void {
        $stmt = DB::get()->prepare("SELECT * FROM noticias WHERE id = ?");
        $stmt->execute([$this->id]);
        $row = $stmt->fetch();
        if (!$row) Response::notFound('Notícia');
        DB::get()->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?")->execute([$this->id]);
        Response::ok($row);
    }

    public function store(): void {
        // Aceita tanto campos do admin (titulo/title) quanto do localStorage (title)
        $b = $this->body;

        // Normaliza: aceita 'titulo' ou 'title'
        if (empty($b['titulo']) && !empty($b['title'])) $b['titulo'] = $b['title'];
        // Normaliza: aceita 'categoria' ou 'cat'
        if (empty($b['categoria']) && !empty($b['cat'])) $b['categoria'] = $b['cat'];
        // Normaliza: aceita 'conteudo' ou 'content'
        if (empty($b['conteudo']) && !empty($b['content'])) $b['conteudo'] = $b['content'];
        // Normaliza: aceita 'imagem_url' ou 'img'
        if (empty($b['imagem_url']) && !empty($b['img'])) $b['imagem_url'] = $b['img'];
        // Normaliza: aceita 'autor' ou 'author'
        if (empty($b['autor']) && !empty($b['author'])) $b['autor'] = $b['author'];
        // Normaliza: aceita 'resumo' ou 'exc'
        if (empty($b['resumo']) && !empty($b['exc'])) $b['resumo'] = $b['exc'];
        // Status padrão
        if (empty($b['status'])) $b['status'] = 'draft';

        Validator::make($b)
            ->required('titulo', 'Título')
            ->maxLength('titulo', 300, 'Título')
            ->in('status', ['published','draft','archived'], 'Status')
            ->validate();

        $d    = Validator::sanitize($b);
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
            ':status'   => $d['status'],
            ':destaque' => (int)($d['destaque'] ?? 0),
            ':pub'      => $d['status'] === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        $id = DB::get()->lastInsertId();
        $this->log('noticia_criada', "ID {$id}: {$d['titulo']}");
        Response::created(['id' => $id], 'Notícia criada com sucesso.');
    }

    public function update(): void {
        $b = $this->body;
        if (empty($b['titulo']) && !empty($b['title'])) $b['titulo'] = $b['title'];
        Validator::make($b)->required('titulo','Título')->validate();
        $d = Validator::sanitize($b);

        DB::get()->prepare(
            "UPDATE noticias SET titulo=:titulo, categoria=:cat, resumo=:resumo,
             conteudo=:conteudo, imagem_url=:img, autor=:autor, status=:status,
             destaque=:destaque,
             publicado_em=CASE WHEN :status2='published' AND publicado_em IS NULL THEN NOW() ELSE publicado_em END
             WHERE id=:id"
        )->execute([
            ':titulo'   => $d['titulo'],
            ':cat'      => $d['categoria'] ?? $d['cat'] ?? 'Geral',
            ':resumo'   => $d['resumo']    ?? null,
            ':conteudo' => $d['conteudo']  ?? $d['content'] ?? null,
            ':img'      => $d['imagem_url'] ?? $d['img'] ?? null,
            ':autor'    => $d['autor']     ?? 'Redação',
            ':status'   => $d['status']    ?? 'draft',
            ':status2'  => $d['status']    ?? 'draft',
            ':destaque' => (int)($d['destaque'] ?? 0),
            ':id'       => $this->id,
        ]);

        Response::ok(['id' => $this->id], 'Notícia atualizada.');
    }

    public function destroy(): void {
        $stmt = DB::get()->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->execute([$this->id]);
        if ($stmt->rowCount() === 0) Response::notFound('Notícia');
        $this->log('noticia_deletada', "ID {$this->id}");
        Response::ok(null, 'Notícia removida.');
    }

    private function makeSlug(string $s): string {
        $s = mb_strtolower($s);
        $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
                'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-') . '-' . time();
    }

    private function log(string $acao, string $det = ''): void {
        try {
            DB::get()->prepare("INSERT INTO activity_log (acao, detalhes, ip) VALUES (?,?,?)")
                ->execute([$acao, $det, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception) {}
    }
}
