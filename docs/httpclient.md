# HTTP Client (Requisições CUrl)

Esqueça linhas repetitivas do `curl_init()`. A `HttpClient` te ajuda a conversar através de REST API com scripts do mundo exterior (Gateway de pagamento, Integração webhook, Autenticador do GitHub, etc) utilizando os recursos performáticos modernos de PHP 8 sem repetição brutal em verbosidade.

## Como Usar (Instanciação)

Na injeção do controller você já a acessa como: `$this->httpClient`.
(Ou acione com `new HttpClient()`).

Por padrão garante retorno String e não imprime, Timeout de 30s bruto na placa de rede, SSL Verify acionado e Accept em JSON automático.

## Exemplos Reais: GET e POST

A library sempre retorna um Array associativo contendo o pacote: `['body' => string, 'status' => int, 'info' => array]` para te dar o controle granular da resposta.

### Puxando do Google (`GET`)
```php
$resultado = $this->httpClient->get('https://google.com', ['q' => 'Pesquisar CUrL']);
echo "O Header retornou: " . $resultado['status']; // 200
```

### Mandando Form Data (`POST`)
```php
$envia = $this->httpClient->post('https://api.teste.com/cadastrar', [
   'username' => 'fulano',
   'password' => 'abc1234'
]);
```

### JSON Array Payload Automático (`postJson`)
Ele auto-converte em `json_encode` antes de jogar na rede para você, e já seta os headers `Content-Length` do stream e `Content-Type: application/json`! Super prático!

```php
$gateway = $this->httpClient->postJson('https://api.stripe.com/transacao', [
   'amount' => 500,
   'card' => '123'
]);
```

## Outras Utilidades
### Inserir Opções Manuais do Curl
```php
$this->httpClient->setOption(CURLOPT_HTTPHEADER, ['Autorization: Token123']);
// Ele fará merge dessa regra com as originais
```
