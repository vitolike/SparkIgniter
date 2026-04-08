# Escoltador de Inputs (Input)

A core class maravilhosa inspirada no glorioso design object do CodeIgniter 3 com poder máximo e match operator do PHP 8.4. Remove necessidade do projeto expor o perigoso $_POST globalmente e previne Null Referencing em scripts que consultam a query.

Geralmente usado via `$this->input` do `Controller`.

## Captores Universais de Strings/Raw

Ao invés de `$_POST['email']`, utilize `$this->input->post('email')`. O benefício é evitar null errors em caso de chaves que o usuário não preencheu, o valor de fail-forward natural é `null`.

```php
// Se não existir, emite 'Nenhum'
$nome = $this->input->post('nome', 'Nenhum'); 

$busca = $this->input->get('q'); // Tenta na key ?q=...
$idade = $this->input->cookie('idade'); // Em $_COOKIE
$referente = $this->input->server('REMOTE_ADDR'); // Equivalente ao $_SERVER seguro
```

Método Curinga Unificado:
```php
// Busca primeiro no array de POST. Se não achar, procura no $_GET global.
$filtro = $this->input->get_post('min-price');
```

## Sanitizações Nativas Embutidas

O script roda varredura bruta nativa para impedir XSS `FILTER_SANITIZE_SPECIAL_CHARS` caso acionadas.
Pode ser executado com o boolean final de todos os getters (`post('arg', default, $sanitize)`);

## Tipagens (Typed Fetchers do Input)

Essas sub-funções não retornam texto puro, já tipam castando e passando validações booleanas de PHP nativo evitando type-juggling:

```php
$idade = $this->input->getInt('idade_numerica', 18); // fallback: var inteira 18
$preco = $this->input->getFloat('produto_preco', 0.0); // garante type float
$isAdmin = $this->input->getBool('admin_auth', false);
```

## Dados JSON das Requisições REST Crúas
Com o advento das REST APIs, as requisições não vem do formulário convencional, e muitas quebram na montagem no array de $_POST porque o React.JS apenas atirou uma raw string JSON contínua na porta.
O `Input` decodifica, cacha a resposta e te fornece o array fácil:

```php
$array_payload = $this->input->json(); // Decodifica 1x da php://input stream
```
Se precisar ver que diabo a API parceira te mandou por Stream HTTP, use o input bruto: `$this->input->raw()`;

## Propriedades da Interação Web Atual

Métodos mágicos para extrair dados sem varrer do $_SERVER:

- `$this->input->method();` --> Retorna: POST, GET, DELETE...
- `$this->input->header('Authorization');` -> Busca a Header Customizada sem se importar com Capital Case.
- `$this->input->isAjax();` -> Booleano sobre Headers de requisição XMLHttpRequest.
- `$this->input->ip();` -> Filtra e resolve IPs proxied, valid range e IPV4/IPV6 com base em arrays estáticos confiáveis (`trusted_proxies`).
