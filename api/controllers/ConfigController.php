<?php
class ConfigController {
    public function __construct(private array $body, private ?string $id = null) {}
    public function show(): void {
        $rows = DB::get()->query("SELECT chave,valor,tipo FROM configuracoes")->fetchAll();
        $cfg  = [];
        foreach ($rows as $r) {
            $cfg[$r['chave']] = $r['tipo']==='json' ? json_decode($r['valor'],true) : $r['valor'];
        }
        Response::ok($cfg);
    }
    public function update(): void {
        // Troca de PIN
        if (isset($this->body['pin_novo'])) {
            Validator::make($this->body)->pin('pin_novo')->validate();
            $hash = Auth::hashPin($this->body['pin_novo']);
            DB::get()->prepare("INSERT INTO configuracoes (chave,valor,tipo) VALUES ('pin_hash',:v,'string') ON DUPLICATE KEY UPDATE valor=:v")
            ->execute([':v'=>$hash]);
        }
        // Cores e fontes
        foreach (['tema_cores','tema_fontes'] as $k) {
            if (isset($this->body[$k])) {
                $v = json_encode($this->body[$k]);
                DB::get()->prepare("INSERT INTO configuracoes (chave,valor,tipo) VALUES (:k,:v,'json') ON DUPLICATE KEY UPDATE valor=:v")
                ->execute([':k'=>$k,':v'=>$v]);
            }
        }
        // Chaves genéricas string/bool/int
        $allowed = ['player_vol','player_autoplay','site_ativo','manutencao'];
        foreach ($allowed as $key) {
            if (isset($this->body[$key])) {
                DB::get()->prepare("UPDATE configuracoes SET valor=:v WHERE chave=:k")
                ->execute([':v'=>$this->body[$key],':k'=>$key]);
            }
        }
        Response::ok(null,'Configurações salvas.');
    }
}
