# REST Controller (APIs)

A classe `RestController` foi projetada para lidar perfeitamente com arquiteturas baseadas em microsserviços e respostas JSON estritas. Diferente do controlador padrão (`Controller`) focando em Views HTML, essa foca puramente em Payload HTTP limpo.

Ela herda tudo do BaseController (também te dando acesso livre ao QueryBuilder `$this->qb`, Input, Session).

## Criação

Ao criar um controlador de app/controllers/api, herde de `RestController`. Ele força cabeçalhos JSON globais e regras de CORS liberais na primeira leitura.

## Aliases de Input Modernos (`RESTful`)

Diferente de um Controller MVC convencional, você invoca o Verbo HTTP ativamente como captador de variáveis (podendo cair em querystrings get, formdata ou em _application/json body stream_ automaticamente parseados).

```php
// Tenta capturar o índice ID no corpo cru do Verbo HTTP DELETE:
$alvo = $this->delete('id');

// Tenta capturar corpo cru do Verbo PATCH
$mudancas = $this->patch('nome');

// GET e POST
$pesquisa = $this->get('termo');
```

## Auxiliares de Resposta

Use os métodos de Response embutidos (onde você não precisa encodar o erro JSON) ao invés de usar `echo json_encode()`. Ele encerra a execução do script instantaneamente (exit).

```php
// Envia "[]" em formato application/json finalizando com status 200 OK
$this->response(['status' => 'sucesso'], RestController::HTTP_OK); 
```

Você também pode blindar as rotas de aceitarem verbos não-previstos (Enviará código `405` automático):

```php
// O usuário tenta bater na endpoint num POST, mas só suporta GET E DELETE
$this->require_methods(['GET', 'DELETE']);
```

## JWT e Autenticação Nativa

Se sua API é fechada, apenas aplique:

```php
public function secret() {
    $this->require_auth(); 
    // Verifica header HTTP_AUTHORIZATION: Bearer {token}
    // E deixa exposto no $this->user todo o token!
    
    $this->response(['id' => $this->user['id']]);
}
```

## Exemplo Prático Completo

Imagine uma arquitetura onde o aplicativo FrontEnd (React Native) precisa enviar um pedido e ser cobrado pelo usuário, exigindo os verbos corretos de REST e token ativo:

```php
<?php

namespace App\Controllers\Api;

use Core\RestController;

class PedidoController extends RestController {

    public function __construct() {
        parent::__construct();
        // Nenhuma ação dessa API vai rodar se o Javascript não enviar JWT!
        $this->require_auth();
    }

    public function create() {
        // Força apenas aceitar metodos de Criação
        $this->require_methods(['POST']);
        
        $carrinho_vetor = $this->json(); // Lê cru pra ignorar o $_POST que não existe
        
        if (empty($carrinho_vetor)) {
            $this->response(['erro' => 'Corpo da requisição faltando.'], 400);
        }
        
        // Pega do JWT o usuário logado que solicitou isso e passa pro model com o array da requisição
        $this->load->model('LojaModel');
        $sucesso = $this->LojaModel->novoEnvio($this->user['id'], $carrinho_vetor);
        
        if ($sucesso) {
            $this->response([
                'status' => 'Concluído',
                'comprovante' => 'OK-2000'
            ], RestController::HTTP_CREATED); // 201 
        } else {
            $this->response(['erro' => 'Falha no processamento'], 500);
        }
    }
}
```
