# Dinamismo de Arquivos (Loader)

A classe `Loader` é o gerenciador de dependências dinâmicas do Controller. Ou seja, ela é a variável `$this->load` que te ajuda a instanciar arquivos, injetando eles diretamente nas variáveis e escopos do seu Controller vivo.

## Acionando nas Filhas

Ela normalmente vem injetada no BaseController e fica ativa pela sintaxe primária:

```php
$this->load->{o_que_carregar}('nome_do_arquivo');
```

## Loader de Modelos (`model`)

Efetua "require" da sua classe localizada em `app/models/` e injeta na controladora instanciando o modelo usando `$db` nativo:

```php
$this->load->model('UsuarioModel');

// E voalá, passa a existir:
$todos = $this->UsuarioModel->all(); 
```

## Loader de Bibliotecas (`library`)

Busca e estância classes locais em `app/libraries` ou até mesmo em pastas nativas de core files como em `app/core`.

```php
$this->load->library('GeradorPDF');
$this->GeradorPDF->exportar();
```

## Loader de Funções/Helpers (`helper`)

Útil para chamar arquivos que contêm apenas compilações de funcções normais não enclausuradas em orientação a objetos (`app/helpers/`). Eles buscam arquivos sempre terminados em `_helper.php`.

```php
$this->load->helper('url');
// Carregou "url_helper.php". As funções dali viram globais!
redirect('/home'); 
```

## Loader de Serviços (`service`)

Diferente das librarys, um file em `app/services/` já acorda com a injeção nativa do `$db` de Controller, ideal para rotinas massivas baseadas em dados abstrusos onde Models são insuficientes. 

```php
$this->load->service('EmailSender');
$this->EmailSender->notificar();
```

## Autoload de Array

Através do load múltiplo e simultâneo injetado de configs array listados em diretivas root:

```php
$this->load->autoload([
   'helpers' => ['url', 'text'],
   'models' => ['UsuarioModel']
]);
```

## Exemplo Prático Completo

Seu framework tem a estrutura MVC clássica. No entanto, sua View necessita disparar chamadas de biblioteca ou model sem um controller intermediário em um componente específico global.

```php
// app/controllers/RelatorioController.php
public function gerar() {
    // 1. Carrega uma library própria do app/libraries/MontadorPdf.php
    $this->load->library('MontadorPdf');
    
    // 2. Carrega um BaseModel customizado com injeção conectada
    $this->load->model('VendasModel');
    
    // Agora o controller pode usar e passar os dados pra frente
    $dados_vendas = $this->VendasModel->listarHoje();
    $this->MontadorPdf->escrever($dados_vendas);
    
    // 3. Usa um simples helper de string (app/helpers/texto_helper.php)
    $this->load->helper('texto');
    echo maiusculo('relatório gerado com sucesso!');
}
```
