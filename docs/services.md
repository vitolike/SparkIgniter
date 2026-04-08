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

## Exemplo Prático Completo

Digamos que temos uma regra de renovação de assinatura que mexe com Banco de Dados e com API Externa (Stripe). Colocar isso no Controller ficaria caótico. Um `PagamentoService` seria ideal:

```php
// app/services/PagamentoService.php
namespace App\Services;

use Core\Service;

class PagamentoService extends Service {

    public function renovarAssinatura($id_usuario) {
        $usuario = $this->qb->get_where('usuarios', ['id' => $id_usuario])->fetchOne();
        
        // 1. Usa o HTTP Client do Base Service pra falar com o Stripe
        $this->httpClient->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer sk_test_...']);
        $resposta = $this->httpClient->post('https://api.stripe.com/v1/charges', [
            'amount' => 2990, // R$ 29,90
            'customer' => $usuario['stripe_id']
        ]);
        
        // 2. Se falhar, usa os métodos nativos de saída em JSON do Service para matar a requisição
        if ($resposta['status'] !== 200) {
            $this->response(['erro' => 'Falha ao cobrar o cartão na operadora'], 402);
        }

        // 3. Atualiza o banco usando o QueryBuilder embarcado
        $this->qb->update('usuarios', ['vencimento' => date('Y-m-d', strtotime('+30 days'))], ['id' => $id_usuario]);
        
        return true;
    }
}
```

E no seu Controller, você apenas chama assim:
```php
public function renovar() {
    $this->load->service('PagamentoService');
    $this->PagamentoService->renovarAssinatura($this->user['id']);
    
    $this->response(['status' => 'sucesso']);
}
```
