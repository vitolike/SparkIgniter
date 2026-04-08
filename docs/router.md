# Roteamento Inteligente (Router)

O `Router` é o grande interceptador da sua URL, que extrai para onde o usuário quer visitar e magicamente dispara a ação pertinente do Controlador e Método na base da convenção, com suporte também à URLs separadas em `/api`.

Ele usa o estilo padrão estático em rotas segmentadas:  
**Url Base / NomeDoController / NomeDoMetodo / (Parâmetro1) / (Parâmetro2) ...**

## Como Acionar
A classe é estanciada e dá a voz de despacho `dispatch()` direto no index root:

```php
$router = new Router();
$router->dispatch();
```

## A Regra de Conexão (Controllers Comuns)

Se você clica em `meusite.com/usuarios/deletar/5`.

1. O `Router` pesquisa por `Usuarios.php` na sua pasta `app/controllers/`. (A primeira letra virará Maiúscula automaticamente via StudlyCase).
2. Ele executa a classe `Usuarios`.
3. Ele roda o método `deletar($id)` que consta no arquivo, repassando o valor `5` na função.

Se você omitir, o "padrão de fuga" da URL buscará por Controller `Home` e método `index`.

## Padrões de Suporte para APIS Rest

Se o primeiro segmento de uma URL for da forma reservada **`/api/`** o provedor automaticamente vai mudar o caminho de procura local de arquivos de controller da raiz de `controllers` para a sub-pasta `app/controllers/api/`.

```text
Acesso: /api/produtos/listar
```
Nessa chamada, o framework abre localmente o código em:  
`app/controllers/api/Produtos.php` executando no método `listar()`.

### Handler Embutido 404
Se ele descobrir que a rota (caminho digitado) não tem controller ou que não foi programado tal método ele gera automaticamente respotas `404 Not Found` transformadas em JSON emitindo a notificação exata da falha e gravando no log interno local.
