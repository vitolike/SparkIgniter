# Controlador Base (Controller)

A classe `Controller` é o cérebro principal da relação MVC dentro do SparkIgniter. Todo controlador que você criar que pretenda renderizar páginas HTML (`views`) deve obrigatoriamente herdar dela.

Ao criar um Controller e estender dessa classe base, você ganha acesso global e instanciado imediatamente a todas as features do ecossistema, dispensando a necessidade de inicializar `new Input()`, `new Session()`, etc.

## Propriedades Injetadas Nativas

As seguintes variáveis já nascem instanciadas dentro de qualquer `$this` do seu controlador:
- **`$this->input`**: Acesso à classe sanitizadora `Input` (GET, POST, $_SERVER).
- **`$this->load`**: Gerenciador de injeções dinâmicas (Models, Library, Helper, Service).
- **`$this->db`**: Acesso cru direto ao Singleton do PDO.
- **`$this->qb`**: O **Query Builder** limpo e dedicado associado à mesma conexão do banco.
- **`$this->idGen`**: Acesso às rotinas de geração da classe `IdGenerator`.
- **`$this->jwt`**: Acesso direto de parse/criação via `JWT`.
- **`$this->httpClient`**: O Wrapper de requisições de API `HttpClient` embutido.
- **`$this->session`**: Gerenciador de Sessões estilo CI3.

## Autoload Global
É possível configurar componentes (como helpers ou Models de ampla utilidade) que sempre injetem globalmente antes de qualquer Controller executar via `Controller::setAutoload([...])`.

## Chamando Views
Para jogar o processamento para frente ao arquivo html/php correspondente:

```php
public function index() {
    $dados = [
        'titulo' => 'Página do Usuário',
        'logado' => true
    ];
    // Carrega app/views/painel.php com as variáveis soltas.
    $this->view('painel', $dados); 
}
```

## RequireAuth (Proteção de Rotas com Middleware embutido)

Se a sua página web exigir que o visitante mande um Cookie assinado, ou passe um Auth via Bearer Token Header (ideal para views construídas misturando JWT com WebHooks), é só evocar no método ou construtor:

```php
public function admin() {
    $this->requireAuth(); // Barra em HTTP 401 se não tiver token bearer
    // a partir daqui o array do usuário estará salvo globalmente no Controller
    var_dump($this->user); 
}
```

## Exemplo Prático Completo

Abaixo um exemplo do mundo real de um `UsuarioController` gerenciando o registro de um usuário e renderizando uma view:

```php
<?php

namespace App\Controllers;

use Core\Controller;

class UsuarioController extends Controller {

    public function __construct() {
        // Carrega model e helper que serão usados em todos os métodos
        $this->load->model('UsuarioModel');
        $this->load->helper('url');
    }

    public function cadastrar() {
        // Se a requisição for POST, tenta registrar
        if ($this->input->method() === 'POST') {
            $nome = $this->input->post('nome', '', true);
            $email = $this->input->post('email', '', true);

            if (!empty($nome) && !empty($email)) {
                $sucesso = $this->UsuarioModel->insert([
                    'nome' => $nome,
                    'email' => $email
                ]);

                if ($sucesso) {
                    $this->session->set_userdata('msg', 'Cadastrado com sucesso!');
                    redirect('/usuarios/login');
                }
            }
            $erro = "Preencha todos os campos!";
        }

        // Renderiza a view na tela de cadastro
        $this->view('usuarios/cadastro', [
            'erro' => $erro ?? null
        ]);
    }
}
```
