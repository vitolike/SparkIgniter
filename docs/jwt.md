# Controle de JSON Web Tokens (JWT)

A Classe `JWT` provê as travas e validações essenciais de Handshake (HS256) stateless do seu backend. Permite o ecossistema autenticar requisições de um cliente React/Vue, etc, ou proteger Controllers WebHooks.

As credenciais do `.env` atuam sobre o funcionamento livre da classe:
- `JWT_SECRET`: Senha mestre.
- `JWT_EXPIRE`: Life spam global em segundos (padrão access).
- `JWT_REFRESH_EXPIRE`: Life spam (padrão refresh).

## Emitindo um Payload Específico Custom (_encode_)

Para codificar livremente (Header, Payload, Signature) informando manual sua validade, passe como o segundo parâmetro em int (segundos):

```php
$token = $this->jwt->encode(['role' => 'admin', 'userid' => 123], 3600); // 1hr
```

## Emitindo Acesso com Base na Configuração (_issueTokens_)

A ferramenta mais prática. Invés de se preocupar com tokens de recarga no seu endpoint de Login, injete apenas as variáveis chaves. O algoritmo retornará um array perfeito.

```php
$resultado = $this->jwt->issueTokens(['userid' => 505]);

// var_dump do array devolvido:
/*
[
  'access_token'  => 'eyJh...',
  'refresh_token' => 'eyXF...',
  'expires_in'    => 3600
]
*/
```

## Decodificação de Validação (_decode_)

Esse método emite `exception` pesada de erro para uso rápido do Try/Catch ou de middleware root.
Ele não só verifica integridade matemática da Hash, como também checa se a `exp` listada dentro do payload atual é superior ao `time()` local do PHP.

```php
try {
   $payload_do_cliente = $this->jwt->decode($seu_token_string);
} catch (\Exception $e) {
   // Apanhado se Forjado Ou Tempo Excedido
   echo $e->getMessage();
}
```
