<?php
class SlideController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function index(): void {
        $only = isset($_GET['ativo']) ? "WHERE ativo=1" : "";
        Response::ok(DB::get()->query("SELECT * FROM slides {$only} ORDER BY ordem ASC")->fetchAll());
    }
    public function store(): void {
        Validator::make($this->body)->required('titulo','Título')->validate();
        $d = Validator::sanitize($this->body);
        DB::get()->prepare("INSERT INTO slides (titulo,subtitulo,imagem_url,tag,btn_texto,btn_link,ativo,ordem) VALUES (:t,:s,:i,:tag,:btn,:link,:a,:o)")
        ->execute([':t'=>$d['titulo'],':s'=>$d['subtitulo']??null,':i'=>$d['imagem_url']??null,':tag'=>$d['tag']??'Destaque',':btn'=>$d['btn_texto']??null,':link'=>$d['btn_link']??null,':a'=>(int)($d['ativo']??1),':o'=>(int)($d['ordem']??0)]);
        Response::created(['id'=>DB::get()->lastInsertId()],'Slide criado.');
    }
    public function update(): void {
        $d = Validator::sanitize($this->body);
        DB::get()->prepare("UPDATE slides SET titulo=:t,subtitulo=:s,imagem_url=:i,tag=:tag,btn_texto=:btn,btn_link=:link,ativo=:a,ordem=:o WHERE id=:id")
        ->execute([':t'=>$d['titulo']??'',':s'=>$d['subtitulo']??null,':i'=>$d['imagem_url']??null,':tag'=>$d['tag']??'Destaque',':btn'=>$d['btn_texto']??null,':link'=>$d['btn_link']??null,':a'=>(int)($d['ativo']??1),':o'=>(int)($d['ordem']??0),':id'=>$this->id]);
        Response::ok(['id'=>$this->id],'Slide atualizado.');
    }
    public function destroy(): void {
        $s = DB::get()->prepare("DELETE FROM slides WHERE id=?");
        $s->execute([$this->id]);
        if ($s->rowCount()===0) Response::notFound('Slide');
        Response::ok(null,'Slide removido.');
    }
}
