# Arquitetura de Serviços (Services)

O Core `Service` tem uma finalidade extremamente clara e minimalista: atuar como um "Mini Controller" não-roteável para você separar regra de negócio massiva da sua Controller e garantir uma estrutura arquitetural SOA mais limpa no seu projeto.

## O Que É Um Service?

Controllers devem apenas gerenciar requisições (chamar validação, pedir aos parceiros os dados, emitir JWT). Caso precise emitir notas-fiscais interligando o HttpClient com a IdGenerator disparando inserts massivos na Model, **tudo vira espaguete.**

Na sua pasta padrão, crie seu "App/Services/GeradorNF.php" estendido da core class.

```php
class GeradorNF extends Service {
   // Tem tudo nativo! ($this->input, $this->db, $this->qb, $this->idGen...)

   public function gerarParaCliente($id) {
       $dados = $this->qb->get_where('clientes', ['id' => $id]);
       // Lógica pesada separada mantendo controller intacto
       return true;
   }
}
```

## Como injetamos no Controller de Execução?

Basta usar a core de carregamento ($this->load):

```php
$this->load->service('GeradorNF');
$sucesso = $this->GeradorNF->gerarParaCliente($this->user['id']);
```

O `Services.php` carrega os **Acessos Nativos Abstratos do Banco `PDO` / `QB`**, **Geração Mútlipla de Token (`IdGen`)** e as funções atreladas da RestController Response Maker (`set_response` / `response`) sem depender das rotas da Framework para interagir e dar Break Point/Exit pro Client!
